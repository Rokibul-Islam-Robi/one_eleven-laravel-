<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminValidator as Validator;
use App\Support\AdminAuth;
use App\Services\OrderService;
use DateTime;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Prescriptions — review patient prescription submissions: upload, approve/reject with notes.
 */
final class PrescriptionsController extends Controller
{
    public const RX_STATUSES = ['pending', 'reviewing', 'approved', 'rejected'];

public static function mod_prescriptions(string $action, int $id): void
{
    switch ($action) {
        case 'new':
            self::prescriptions_form();
            break;
        case 'create':
            self::prescriptions_create();
            break;
        case 'view':
            self::prescriptions_view(self::rx_find($id));
            break;
        case 'file':
            require_module('prescriptions', 'view');
            self::prescriptions_file(self::rx_find($id));
            break;
        case 'status':
            require_module('prescriptions', 'edit');
            self::prescriptions_status($id);
            break;
        case 'delete':
            require_module('prescriptions', 'delete');
            self::prescriptions_delete($id);
            break;
        default:
            self::prescriptions_list();
    }
}

public static function rx_find(int $id): array
{
    $row = qrow(
        'SELECT rx.*, d.name AS doctor_name, o.order_number
         FROM prescriptions rx
         LEFT JOIN doctors d ON d.id = rx.doctor_id
         LEFT JOIN orders o ON o.id = rx.order_id
         WHERE rx.id = ?',
        [$id]
    );
    if (!$row) {
        adm_status(404);
        render_layout('Prescription not found', 'prescriptions', '<div class="adm-card adm-card-pad adm-empty"><h3>Prescription not found</h3><a class="abtn primary mt-3" href="' . e(aurl('prescriptions')) . '">Back</a></div>');
    }
    return $row;
}

public static function prescriptions_file(array $rx): void
{
    $rel = (string)($rx['file_path'] ?? '');
    if ($rel === '') {
        adm_die(404, 'No file attached');
    }
    // private disk: storage/app/private/uploads/prescriptions (never web-accessible)
    $root = \Illuminate\Support\Facades\Storage::disk('local')->path('');
    $base = realpath($root . 'uploads/prescriptions');
    $full = realpath($root . ltrim($rel, '/'));
    if ($base === false || $full === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR) || !is_file($full)) {
        adm_die(404, 'File not found');
    }
    $mime = (string)($rx['mime_type'] ?: 'application/octet-stream');
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (!in_array($mime, $allowed, true)) {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($full);
        if (!in_array($mime, $allowed, true)) {
            adm_die(415, 'Unsupported file type');
        }
    }
    throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->file($full, [
        'Content-Type' => $mime,
        'X-Content-Type-Options' => 'nosniff',
        'Content-Disposition' => 'inline; filename="' . rawurlencode((string)($rx['original_name'] ?: basename($full))) . '"',
    ]));
}

public static function prescriptions_list(): void
{
    $q = getq('q');
    $status = getq('status');
    $page = pint(getq('page', '1'));

    $where = '1=1';
    $params = [];
    if ($q !== '') {
        $where .= ' AND (rx.customer_name LIKE ? OR rx.phone LIKE ? OR rx.notes LIKE ? OR d.name LIKE ?)';
        $like = like($q);
        array_push($params, $like, $like, $like, $like);
    }
    if ($status !== '' && in_array($status, self::RX_STATUSES, true)) {
        $where .= ' AND rx.status = ?';
        $params[] = $status;
    }

    $total = (int)qval("SELECT COUNT(*) FROM prescriptions rx LEFT JOIN doctors d ON d.id = rx.doctor_id WHERE $where", $params);
    [$limitSql, , $pager] = paginate($total, $page, (int)config('admin.per_page'));
    $rows = qall(
        "SELECT rx.*, d.name AS doctor_name
         FROM prescriptions rx LEFT JOIN doctors d ON d.id = rx.doctor_id
         WHERE $where ORDER BY rx.created_at DESC $limitSql",
        $params
    );
    $counts = [];
    foreach (qall('SELECT status, COUNT(*) c FROM prescriptions GROUP BY status') as $r) { $counts[$r['status']] = (int)$r['c']; }

    $actions = can('prescriptions', 'edit')
        ? '<a class="abtn primary" href="' . e(aurl('prescriptions/new')) . '">' . icon('plus') . ' Record prescription</a>' : '';

    $html = page_head('Prescriptions', 'Patient prescription submissions linked to doctors and orders.', $actions);
    $tabs = '<div class="adm-tabs"><a href="' . e(aurl('prescriptions')) . '" class="' . ($status === '' ? 'on' : '') . '">All <span class="cnt">' . array_sum($counts) . '</span></a>';
    foreach (self::RX_STATUSES as $s) {
        $tabs .= '<a href="' . e(aurl('prescriptions', ['status' => $s])) . '" class="' . ($status === $s ? 'on' : '') . '">' . e(ucfirst($s)) . ' <span class="cnt">' . ($counts[$s] ?? 0) . '</span></a>';
    }
    $html .= $tabs . '</div>';

    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([], 'Search patient, phone or doctor…', $q);
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('rx') . '</div><h3>No prescriptions found</h3><p>' . ($q || $status ? 'Nothing matches the current filters.' : 'Prescription uploads and staff entries will appear here.') . '</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Patient</th><th>Doctor</th><th>Notes</th><th>File</th><th>Status</th><th>Submitted</th><th></th></tr></thead><tbody>';
        foreach ($rows as $rx) {
            $html .= '<tr><td><span class="cell-main">' . e($rx['customer_name']) . '</span><div class="cell-sub">' . e($rx['phone'] ?: '—') . '</div></td>'
                . '<td>' . e($rx['doctor_name'] ?: '—') . '</td>'
                . '<td style="max-width:260px"><span class="cell-sub">' . e(mb_substr((string)$rx['notes'], 0, 90)) . '</span></td>'
                . '<td>' . ($rx['file_path'] ? '<a href="' . e(aurl('prescriptions/file/' . (int)$rx['id'])) . '" target="_blank">View file</a>' : '<span class="cell-sub">none</span>') . '</td>'
                . '<td>' . status_badge((string)$rx['status']) . '</td>'
                . '<td>' . e(dt($rx['created_at'])) . '</td>'
                . '<td class="actions-cell"><a class="abtn secondary xs" href="' . e(aurl('prescriptions/view/' . (int)$rx['id'])) . '">Review</a></td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, array_filter(['q' => $q, 'status' => $status]));
    }
    $html .= '</div>';
    render_layout('Prescriptions', 'prescriptions', $html);
}

public static function prescriptions_form(array $errors = [], array $old = []): void
{
    $doctors = qall("SELECT id, name FROM doctors WHERE status='active' ORDER BY name");
    $customers = qall("SELECT id, name, phone FROM users WHERE role='customer' ORDER BY name LIMIT 200");
    $v = static fn(string $k, string $d = '') => (string)($old[$k] ?? $d);

    $html = page_head('Record prescription', 'Add a prescription received offline or on behalf of a patient.',
        '<a class="abtn ghost" href="' . e(aurl('prescriptions')) . '">← Back</a>');
    $html .= '<div class="adm-card" style="max-width:680px"><div class="adm-card-pad"><form method="post" action="' . e(aurl('prescriptions/create')) . '" enctype="multipart/form-data" novalidate>' . csrf_field();
    $html .= '<div class="afield"><label>Link to customer <span class="hint">(optional)</span></label><select class="aselect" name="customer_id"><option value="">— guest / walk-in —</option>';
    foreach ($customers as $c) { $html .= '<option value="' . (int)$c['id'] . '"' . ($v('customer_id') === (string)$c['id'] ? ' selected' : '') . '>' . e($c['name']) . ' · ' . e($c['phone'] ?: $c['id']) . '</option>'; }
    $html .= '</select></div>';
    $html .= '<div class="afield"><label>Patient name *</label><input class="ainput' . (isset($errors['customer_name']) ? ' err' : '') . '" name="customer_name" value="' . e($v('customer_name')) . '" required>'
        . (isset($errors['customer_name']) ? '<div class="aerr">' . e($errors['customer_name']) . '</div>' : '') . '</div>';
    $html .= '<div class="agrid-2"><div class="afield"><label>Phone</label><input class="ainput" name="phone" value="' . e($v('phone')) . '"></div>';
    $html .= '<div class="afield"><label>Prescribing doctor</label><select class="aselect" name="doctor_id"><option value="">— none —</option>';
    foreach ($doctors as $d) { $html .= '<option value="' . (int)$d['id'] . '"' . ($v('doctor_id') === (string)$d['id'] ? ' selected' : '') . '>' . e($d['name']) . '</option>'; }
    $html .= '</select></div></div>';
    $html .= '<div class="afield"><label>Notes / medicine list</label><textarea class="atextarea" name="notes" rows="4">' . e($v('notes')) . '</textarea></div>';
    $html .= '<div class="afield"><label>Upload file <span class="hint">(JPG/PNG/WEBP/PDF, max ' . (int)config('admin.max_upload_mb') . ' MB)</span></label><input class="ainput" type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf">'
        . (isset($errors['file']) ? '<div class="aerr">' . e($errors['file']) . '</div>' : '') . '</div>';
    $html .= '<div class="form-actions"><button class="abtn primary">Save prescription</button></div></form></div></div>';
    render_layout('Record prescription', 'prescriptions', $html);
}

public static function prescriptions_create(): void
{
    verify_csrf_post();
    $name = post('customer_name');
    $errors = [];
    if ($name === '') { $errors['customer_name'] = 'Patient name is required.'; }

    $filePath = null;
    try {
        $filePath = upload_file('file', 'prescriptions');
    } catch (RuntimeException $ex) {
        $errors['file'] = $ex->getMessage();
    }

    if ($errors) {
        flash('error', 'Please correct the highlighted fields.');
        self::prescriptions_form($errors, request()->post());
        return;
    }

    $doctorId = (int)(request()->post('doctor_id') ?? 0) ?: null;
    $customerId = (int)(request()->post('customer_id') ?? 0) ?: null;
    $id = insert_row('prescriptions', [
        'customer_id' => $customerId,
        'doctor_id' => $doctorId,
        'customer_name' => $name,
        'phone' => post('phone') ?: null,
        'notes' => post('notes') ?: null,
        'file_path' => $filePath,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    log_action('rx.create', 'prescription', $id, $name);
    flash_redirect('success', 'Prescription for ' . $name . ' recorded.', 'prescriptions');
}

public static function prescriptions_view(array $rx): void
{
    $actions = '<a class="abtn ghost" href="' . e(aurl('prescriptions')) . '">← All prescriptions</a>';
    if (can('prescriptions', 'delete')) {
        $actions .= '<form method="post" action="' . e(aurl('prescriptions/delete/' . (int)$rx['id'])) . '" class="d-inline"' . confirm_delete_js() . '>' . csrf_field()
            . '<button class="abtn danger">Delete</button></form>';
    }

    $html = page_head('Prescription #' . (int)$rx['id'], $rx['customer_name'] . ' · ' . dt($rx['created_at']), $actions);
    $html .= '<div class="adm-grid-2" style="grid-template-columns:1.4fr 1fr">';

    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Details</h3>' . status_badge((string)$rx['status']) . '</div><div class="adm-card-pad"><dl class="dl-grid">';
    $html .= '<div><dt>Patient</dt><dd>' . e($rx['customer_name']) . '</dd></div>';
    $html .= '<div><dt>Phone</dt><dd>' . e($rx['phone'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Doctor</dt><dd>' . ($rx['doctor_id'] ? '<a href="' . e(aurl('doctors/view/' . (int)$rx['doctor_id'])) . '">' . e($rx['doctor_name']) . '</a>' : '—') . '</dd></div>';
    $html .= '<div><dt>Linked order</dt><dd>' . e($rx['order_number'] ?: '—') . '</dd></div>';
    $html .= '<div style="grid-column:1/-1"><dt>Notes</dt><dd>' . nl2br(e($rx['notes'] ?: '—')) . '</dd></div>';
    $html .= '<div style="grid-column:1/-1"><dt>File</dt><dd>' . ($rx['file_path'] ? '<a class="code-chip" href="' . e(aurl('prescriptions/file/' . (int)$rx['id'])) . '" target="_blank">Open attachment</a>' : 'No file attached') . '</dd></div>';
    $html .= '</dl></div></div>';

    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Review action</h3></div><div class="adm-card-pad">';
    if (can('prescriptions', 'edit')) {
        $html .= '<form method="post" action="' . e(aurl('prescriptions/status/' . (int)$rx['id'])) . '">' . csrf_field();
        $html .= '<div class="afield"><label>Status</label><select class="aselect" name="status">';
        foreach (self::RX_STATUSES as $s) { $html .= '<option value="' . $s . '"' . ($rx['status'] === $s ? ' selected' : '') . '>' . e(ucfirst($s)) . '</option>'; }
        $html .= '</select></div>';
        $html .= '<div class="afield"><label>Admin notes</label><textarea class="atextarea" name="admin_notes" rows="4" placeholder="Verification notes, advice given…">' . e($rx['admin_notes']) . '</textarea></div>';
        $html .= '<button class="abtn primary">Save review</button></form>';
    } else {
        $html .= '<p style="color:var(--a-muted);font-size:13px">Your role can view prescriptions but not change their status.</p>';
    }
    if ($rx['admin_notes']) {
        $html .= '<div class="mt-3" style="font-size:13px"><b>Saved notes:</b> ' . nl2br(e($rx['admin_notes'])) . '</div>';
    }
    $html .= '</div></div></div>';

    render_layout('Prescription #' . (int)$rx['id'], 'prescriptions', $html);
}

public static function prescriptions_status(int $id): void
{
    verify_csrf_post();
    $rx = self::rx_find($id);
    $status = post('status');
    if (!in_array($status, self::RX_STATUSES, true)) {
        flash('error', 'Invalid status.');
        aredirect('prescriptions/view/' . $id);
    }
    q('UPDATE prescriptions SET status = ?, admin_notes = ? WHERE id = ?', [$status, post('admin_notes') ?: null, $id]);
    log_action('rx.status', 'prescription', $id, $rx['customer_name'] . ' → ' . $status);
    flash_redirect('success', 'Prescription #' . $id . ' marked as ' . $status . '.', 'prescriptions/view/' . $id);
}

public static function prescriptions_delete(int $id): void
{
    verify_csrf_post();
    $rx = self::rx_find($id);
    q('DELETE FROM prescriptions WHERE id = ?', [$id]);
    log_action('rx.delete', 'prescription', $id, $rx['customer_name']);
    flash_redirect('success', 'Prescription deleted.', 'prescriptions');
}
}
