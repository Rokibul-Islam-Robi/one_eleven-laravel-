<?php
/**
 * Admin CMS helper layer (port of admin/includes/{bootstrap,helpers,auth,layout}.php).
 *
 * Same helper names/behaviour as the original admin so the module controllers keep
 * identical queries and markup, but everything runs on Laravel: the shared PDO
 * connection, the Laravel session, Request, Storage and HttpResponseException
 * (instead of header()/exit). Autoloaded through composer.json "files".
 *
 * Name changes (to avoid clashing with Laravel / storefront helpers):
 *   redirect() -> aredirect(),  money() -> amoney(),  csrf_field() = Laravel's.
 */

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

const ROLE_RANK = ['staff' => 1, 'manager' => 2, 'admin' => 3];
const ACTION_IDX = ['view' => 0, 'edit' => 1, 'delete' => 2];

/** module => [view, edit, delete] minimum roles (positional) */
const MODULE_ACCESS = [
    'dashboard'      => ['staff', 'staff', 'admin'],
    'products'       => ['staff', 'manager', 'admin'],
    'categories'     => ['staff', 'manager', 'admin'],
    'brands'         => ['staff', 'manager', 'admin'],
    'doctors'        => ['staff', 'manager', 'admin'],
    'doctor_coupons' => ['staff', 'manager', 'admin'],
    'offers'         => ['staff', 'manager', 'admin'],
    'courier'        => ['staff', 'manager', 'admin'],
    'prescriptions'  => ['staff', 'staff', 'manager'],
    'inventory'      => ['staff', 'manager', 'admin'],
    'orders'         => ['staff', 'staff', 'manager'],
    'customers'      => ['staff', 'manager', 'admin'],
    'sellers'         => ['admin', 'admin', 'admin'],
    'content'        => ['manager', 'manager', 'admin'],
    'reports'        => ['manager', 'manager', 'admin'],
    'settings'       => ['admin', 'admin', 'admin'],
    'search'         => ['staff', 'staff', 'staff'],
];

/* ------------------------------------------------------------------ responses */

/** Remember the HTTP status the next admin page/response should use (was http_response_code()). */
function adm_status(?int $code = null): int
{
    static $status = 200;
    if ($code !== null) { $status = $code; }
    return $status;
}

/** Stop the request with a plain body (was http_response_code(); exit('...')). */
function adm_die(int $code, string $body = ''): never
{
    throw new HttpResponseException(response($body, $code));
}

/** Admin URL builder: aurl('doctors/edit', ['id'=>3]) */
function aurl(string $path = '', array $query = []): string
{
    $url = rtrim(request()->getBasePath(), '/') . '/admin/' . ltrim($path, '/');
    if ($query) { $url .= '?' . http_build_query($query); }
    return $url;
}

/** Storefront URL (for "view site" links). */
function site_url(string $path = ''): string
{
    return rtrim(request()->getBasePath(), '/') . '/' . ltrim($path, '/');
}

/** Redirect and stop (original: redirect()). */
function aredirect(string $path, array $query = []): never
{
    $to = str_starts_with($path, 'http') ? $path : aurl($path, $query);
    throw new HttpResponseException(new RedirectResponse($to));
}

function flash_redirect(string $type, string $message, string $path, array $query = []): never
{
    flash($type, $message);
    aredirect($path, $query);
}

/** Format money using the admin currency (original: money()). */
function amoney($v, bool $symbol = true): string
{
    return ($symbol ? config('admin.currency') : '') . number_format((float)$v, 2);
}

/* -------------------------------------------------------------------- flash */
function flash(string $type, string $message): void
{
    session()->push('admin_flash', ['type' => $type, 'message' => $message]);
}

function get_flashes(): array
{
    return (array)session()->pull('admin_flash', []);
}

/* ---------------------------------------------------------- request helpers */
function post(string $key, string $default = ''): string
{
    return trim((string)(request()->post($key) ?? $default));
}

function getq(string $key, string $default = ''): string
{
    return trim((string)(request()->query($key) ?? $default));
}

/** Sanitise an integer query/route param. */
function pint($v): int
{
    return max(0, (int)$v);
}

function client_ip(): string
{
    return substr((string)(request()->server('REMOTE_ADDR') ?: '0.0.0.0'), 0, 45);
}

/* ----------------------------------------------------------------- database */
function db(): PDO
{
    $pdo = DB::connection()->getPdo();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

/**
 * Make the few MySQL-only date functions of the original queries portable
 * (MySQL, MariaDB and SQLite): the dates are computed in PHP.
 */
function adm_sql(string $sql): string
{
    if (stripos($sql, 'CURDATE') !== false) {
        $sql = preg_replace_callback(
            '/DATE_ADD\(\s*CURDATE\(\)\s*,\s*INTERVAL\s+(\d+)\s+DAY\s*\)/i',
            fn ($m) => "'" . date('Y-m-d', strtotime('+' . $m[1] . ' days')) . "'",
            $sql
        );
        $sql = preg_replace('/\bCURDATE\(\)/i', "'" . date('Y-m-d') . "'", $sql);
    }
    if (stripos($sql, 'NOW()') !== false) {
        $sql = preg_replace('/\bNOW\(\)/i', "'" . date('Y-m-d H:i:s') . "'", $sql);
    }
    return $sql;
}

/** Run a query with bound params, return PDOStatement. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare(adm_sql($sql));
    $st->execute($params);
    return $st;
}

function qrow(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch(PDO::FETCH_ASSOC);
    return $r === false ? null : $r;
}

function qall(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
}

function qval(string $sql, array $params = [])
{
    return q($sql, $params)->fetchColumn();
}

function insert_row(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES ('
         . implode(',', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int)db()->lastInsertId();
}

function update_row(string $table, array $data, int $id): void
{
    $sets = [];
    foreach (array_keys($data) as $col) { $sets[] = "`$col`=?"; }
    q('UPDATE `' . $table . '` SET ' . implode(',', $sets) . ' WHERE `id`=?', array_merge(array_values($data), [$id]));
}

/* --------------------------------------------------------------------- auth */
function current_user(): ?array
{
    $u = session('admin_user');
    return is_array($u) ? $u : null;
}

function is_logged_in(): bool
{
    return isset(session('admin_user')['id']);
}

/** Can the current user perform $action ('view'|'edit'|'delete') on $module? */
function can(string $module, string $action = 'view'): bool
{
    $u = current_user();
    if (!$u) { return false; }
    $rank = ROLE_RANK[$u['role']] ?? 0;
    $need = MODULE_ACCESS[$module][ACTION_IDX[$action] ?? 0] ?? 'admin';
    return $rank >= (ROLE_RANK[$need] ?? 99);
}

function require_module(string $module, string $action = 'view'): void
{
    if (!can($module, $action)) {
        adm_die(403, '<h1>403 — Access denied</h1><p>Your role (<b>' . e(current_user()['role'] ?? 'guest')
            . '</b>) does not have permission for this area. <a href="' . e(aurl('dashboard')) . '">Back to dashboard</a></p>');
    }
}

/** Audit log entry. */
function log_action(string $action, ?string $entity = null, ?int $entityId = null, ?string $detail = null): void
{
    try {
        q('INSERT INTO admin_logs (user_id, action, entity, entity_id, detail, ip) VALUES (?,?,?,?,?,?)',
            [session('admin_user.id'), $action, $entity, $entityId, $detail, client_ip()]);
    } catch (Throwable $e) {
        error_log('log_action failed: ' . $e->getMessage());
    }
}

/**
 * Mutating admin actions must be POST. (The original only checked the CSRF token *if* the request
 * happened to be a POST, so a plain GET link could trigger delete/toggle actions; Laravel's
 * VerifyCsrfToken middleware validates every POST, and GET is now refused.)
 */
function verify_csrf_post(): void
{
    if (!request()->isMethod('post')) {
        adm_die(405, '<h1>Method not allowed</h1><p>This action must be submitted from its form button. <a href="' . e(aurl('dashboard')) . '">Back to dashboard</a></p>');
    }
}

/* ------------------------------------------------------------------- upload */
/** Handle a validated file upload (images/pdf). Returns stored relative path or null. */
function upload_file(string $field, string $subdir): ?string
{
    $f = request()->file($field);
    if ($f === null) { return null; }
    if (!$f->isValid()) {
        throw new RuntimeException('File upload failed (error code ' . (int)$f->getError() . ').');
    }
    $maxMb = (int)config('admin.max_upload_mb');
    if ($f->getSize() > $maxMb * 1024 * 1024) {
        throw new RuntimeException('File is larger than ' . $maxMb . ' MB.');
    }
    $allowed = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f->getPathname());
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WEBP or PDF files are allowed.');
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    // private disk (storage/app/private): never web-accessible, served only through an authorised route
    if (!\Illuminate\Support\Facades\Storage::disk('local')->putFileAs('uploads/' . $subdir, $f, $name)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return 'uploads/' . $subdir . '/' . $name;
}

/* --------------------------------------------------------------- pagination */
/** Build a LIMIT clause + pagination UI data. Returns [limitSql, offset, pager]. */
function paginate(int $total, int $page, int $perPage): array
{
    $perPage = max(5, min(100, $perPage));
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    return ['LIMIT ' . $perPage . ' OFFSET ' . $offset, $offset, [
        'page' => $page, 'pages' => $pages, 'per_page' => $perPage,
        'total' => $total, 'offset' => $offset,
    ]];
}

/** Render Bootstrap-style pagination preserving current filters. */
function render_pager(array $pager, array $keep = []): string
{
    if ($pager['pages'] <= 1) { return ''; }
    $from = $pager['offset'] + 1;
    $to = min($pager['total'], $pager['offset'] + $pager['per_page']);
    $html = '<nav class="adm-pagination"><div>Showing <b>' . e($from) . '–' . e($to) . '</b> of <b>' . e(number_format($pager['total'])) . '</b></div><ul class="pagination mb-0">';

    $link = function (int $p, string $label, bool $disabled = false, bool $active = false) use ($keep): string {
        if ($disabled) {
            return '<li class="page-item disabled"><span class="page-link">' . $label . '</span></li>';
        }
        if ($active) {
            return '<li class="page-item active"><span class="page-link">' . $label . '</span></li>';
        }
        $q = array_merge($keep, ['page' => $p]);
        return '<li class="page-item"><a class="page-link" href="' . e(aurl(current_admin_path(), $q)) . '">' . $label . '</a></li>';
    };

    $html .= $link(max(1, $pager['page'] - 1), '&laquo;', $pager['page'] <= 1);

    $start = max(1, $pager['page'] - 2);
    $end = min($pager['pages'], $pager['page'] + 2);
    if ($start > 1) {
        $html .= $link(1, '1');
        if ($start > 2) { $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= $link($i, (string)$i, false, $i === $pager['page']);
    }
    if ($end < $pager['pages']) {
        if ($end < $pager['pages'] - 1) { $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
        $html .= $link($pager['pages'], (string)$pager['pages']);
    }

    $html .= $link(min($pager['pages'], $pager['page'] + 1), '&raquo;', $pager['page'] >= $pager['pages']);
    return $html . '</ul></nav>';
}

/** Current module path for pager links, e.g. "doctors". */
function current_admin_path(): string
{
    return $GLOBALS['ADMIN_MODULE'] ?? 'dashboard';
}

/* ------------------------------------------------------- badges & formatting */
function status_badge(string $status): string
{
    $map = [
        'active' => 'green', 'inactive' => 'gray',
        'pending' => 'amber', 'processing' => 'blue', 'shipped' => 'purple',
        'delivered' => 'green', 'cancelled' => 'red',
        'awaiting_prescription' => 'amber', 'failed_delivery' => 'red',
        'return_requested' => 'amber', 'returned' => 'gray',
        'unpaid' => 'amber', 'paid' => 'green', 'failed' => 'red', 'refunded' => 'purple',
        'requested' => 'amber', 'approved' => 'green', 'received' => 'blue',
        'verified' => 'green', 'unverified' => 'gray', 'rejected' => 'red',
        'reviewing' => 'blue',
        'credited' => 'green',
        'expired' => 'red', 'expiring' => 'amber', 'ok' => 'green',
    ];
    $cls = $map[$status] ?? 'gray';
    return '<span class="abadge ' . $cls . '">' . e(ucfirst($status)) . '</span>';
}

function yn(bool $v, string $yes = 'Yes', string $no = 'No'): string
{
    return $v ? '<span class="abadge green">' . e($yes) . '</span>' : '<span class="abadge gray">' . e($no) . '</span>';
}

function dt(?string $s, string $fmt = 'd M Y, h:i A'): string
{
    if (!$s) { return '—'; }
    $t = strtotime($s);
    return $t ? date($fmt, $t) : '—';
}

function d(?string $s): string
{
    return dt($s, 'd M Y');
}

/** Slug generator. */
function slugify(string $text): string
{
    $text = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $text), '-'));
    return $text !== '' ? $text : 'item-' . substr(md5((string)mt_rand()), 0, 6);
}

/** Pattern for a bound LIKE parameter. */
function like(string $s): string
{
    return '%' . addcslashes($s, '%_\\') . '%';
}

/** Unique-reference-code generator for doctors. */
function generate_ref_code(string $prefix = 'DOC'): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < 25; $i++) {
        $code = $prefix . '-';
        for ($j = 0; $j < 6; $j++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $exists = qval('SELECT COUNT(*) FROM doctors WHERE reference_code = ?', [$code]);
        if (!(int)$exists) { return $code; }
    }
    return $prefix . '-' . substr(md5((string)random_int(1, PHP_INT_MAX)), 0, 6);
}

function confirm_delete_js(): string
{
    return " onsubmit=\"return confirm('This action cannot be undone. Continue?');\"";
}

/* ------------------------------------------------------------------- layout */
/** Sidebar navigation definition: key => [label, icon, url path, permission module] */
function admin_nav(): array
{
    return [
        ['', 'MAIN', null, null],
        ['dashboard', 'Dashboard', 'dashboard', 'dashboard'],
        ['orders', 'Orders & Shipping', 'orders', 'orders'],
        ['prescriptions', 'Prescriptions', 'prescriptions', 'prescriptions'],
        ['', 'CATALOGUE', null, null],
        ['products', 'Products & Categories', 'products', 'products'],
        ['inventory', 'Inventory & Expiry', 'inventory', 'inventory'],
        ['', 'HEALTHCARE NETWORK', null, null],
        ['doctors', 'Doctors', 'doctors', 'doctors'],
        ['doctor_coupons', 'Doctor Reference Coupons', 'doctor-coupons', 'doctor_coupons'],
        ['offers', 'Offers & Discounts', 'offers', 'offers'],
        ['', 'OPERATIONS', null, null],
        ['customers', 'Customers', 'customers', 'customers'],
        ['sellers', 'Sellers', 'sellers', 'sellers'],
        ['courier', 'Courier Settings', 'courier', 'courier'],
        ['content', 'Website Content', 'content', 'content'],
        ['', 'ANALYTICS', null, null],
        ['reports', 'Reports & Settings', 'reports', 'reports'],
    ];
}

/** Live counters for sidebar badges. */
function nav_badges(): array
{
    $badges = [];
    try {
        $badges['orders'] = (int)qval("SELECT COUNT(*) FROM orders WHERE status='pending'");
        $badges['prescriptions'] = (int)qval("SELECT COUNT(*) FROM prescriptions WHERE status IN ('pending','reviewing')");
        $badges['sellers'] = (int)qval("SELECT COUNT(*) FROM sellers WHERE application_status = 'pending'");
        $badges['inventory'] = (int)qval(
            "SELECT COUNT(*) FROM inventory_batches
             WHERE qty_remaining > 0 AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        ) + (int)qval(
            "SELECT COUNT(*) FROM products p WHERE p.stock > 0 AND p.stock <= p.low_stock_threshold"
        );
    } catch (Throwable $e) {
        $badges = ['orders' => 0, 'prescriptions' => 0, 'inventory' => 0];
    }
    return $badges;
}

/** Render the full admin page (Blade layout) and stop. $content is the page body HTML. */
function render_layout(string $title, string $activeKey, string $content, bool $wide = false): never
{
    $html = view('admin.layout', [
        'title' => $title, 'activeKey' => $activeKey, 'content' => $content, 'wide' => $wide,
    ])->render();
    throw new HttpResponseException(response($html, adm_status()));
}

/** Page header partial. */
function page_head(string $title, string $subtitle = '', string $actionsHtml = ''): string
{
    return '<div class="adm-page-head"><div><h1>' . e($title) . '</h1>'
        . ($subtitle !== '' ? '<p>' . e($subtitle) . '</p>' : '')
        . '</div>' . ($actionsHtml !== '' ? '<div class="d-flex flex-wrap gap-2">' . $actionsHtml . '</div>' : '') . '</div>';
}

/** KPI metric card. */
function metric_card(string $label, string $value, string $iconKey, string $tone = 'green', string $sub = ''): string
{
    return '<div class="adm-metric"><span class="m-ic tone-' . e($tone) . '">' . icon($iconKey) . '</span><div>'
        . '<div class="m-val">' . $value . '</div><div class="m-lbl">' . e($label) . '</div>'
        . ($sub !== '' ? '<div class="m-delta up">' . e($sub) . '</div>' : '')
        . '</div></div>';
}

/** Standard CRUD toolbar (search + filters) — emits a GET form. */
function filter_toolbar(array $fieldsHtml, string $searchPlaceholder = 'Search…', string $searchValue = ''): string
{
    $html = '<form method="get" class="adm-toolbar">';
    $html .= '<div class="adm-search-box">' . icon('search')
        . '<input class="ainput" name="q" value="' . e($searchValue) . '" placeholder="' . e($searchPlaceholder) . '"></div>';
    foreach ($fieldsHtml as $f) { $html .= $f; }
    $html .= '<button class="abtn secondary sm" type="submit">' . icon('filter', 'ic-sm') . ' Filter</button>';
    $html .= '<a class="abtn ghost sm" href="' . e(aurl(current_admin_path())) . '">Reset</a>';
    $html .= '<span class="spacer"></span></form>';
    return $html;
}

/** Select field for filter toolbar. */
function filter_select(string $name, string $label, array $options, string $current): string
{
    $h = '<label class="adm-filter-label">' . e($label) . ': <select class="aselect adm-filter-select" name="' . e($name) . '">';
    foreach ($options as $val => $lbl) {
        $h .= '<option value="' . e((string)$val) . '"' . ((string)$current === (string)$val ? ' selected' : '') . '>' . e($lbl) . '</option>';
    }
    return $h . '</select></label>';
}

/** Plain redirect response to an admin path (for middleware, which returns instead of throwing). */
function redirect_admin(string $path, array $query = []): RedirectResponse
{
    return new RedirectResponse(aurl($path, $query));
}
