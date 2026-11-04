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
 * Reports & Settings — sales reports, system settings, admin accounts, audit log.
 * Tabs: reports | settings | admins | logs
 */
final class ReportsController extends Controller
{


public static function mod_reports(string $action, int $id): void
{
    $tab = getq('tab', 'reports');
    switch ($action) {
        case 'user_save':
            require_module('settings', 'edit');
            self::reports_user_save();
            break;
        case 'user_delete':
            require_module('settings', 'delete');
            self::reports_user_delete($id);
            break;
        case 'setting_save':
            require_module('settings', 'edit');
            self::reports_settings_save();
            break;
        default:
            if ($tab === 'settings' && can('settings')) { self::reports_settings_tab(); }
            elseif ($tab === 'admins' && can('settings')) { self::reports_admins_tab(); }
            elseif ($tab === 'logs' && can('settings')) { self::reports_logs_tab(); }
            else { self::reports_main(); }
    }
}

public static function reports_main(): void
{
    $days = pint(getq('days', '30'));
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $since = date('Y-m-d 00:00:00', time() - ($days - 1) * 86400);

    $totals = qrow(
        "SELECT COUNT(*) orders, COALESCE(SUM(total),0) revenue, COALESCE(AVG(total),0) avg_order
         FROM orders WHERE created_at >= ? AND status <> 'cancelled'",
        [$since]
    );
    $byDay = qall(
        "SELECT DATE(created_at) d, COUNT(*) c, COALESCE(SUM(total),0) s
         FROM orders WHERE created_at >= ? AND status <> 'cancelled'
         GROUP BY DATE(created_at) ORDER BY d",
        [$since]
    );
    $byCategory = qall(
        "SELECT COALESCE(c.name,'Uncategorised') cat, COUNT(*) items, SUM(oi.price * oi.qty) revenue
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id AND o.status <> 'cancelled' AND o.created_at >= ?
         LEFT JOIN products p ON p.id = oi.product_id
         LEFT JOIN categories c ON c.id = p.category_id
         GROUP BY c.name ORDER BY revenue DESC LIMIT 10",
        [$since]
    );
    $topProducts = qall(
        "SELECT oi.product_name, SUM(oi.qty) qty, SUM(oi.price * oi.qty) revenue
         FROM order_items oi JOIN orders o ON o.id = oi.order_id AND o.status <> 'cancelled' AND o.created_at >= ?
         GROUP BY oi.product_name ORDER BY qty DESC LIMIT 10",
        [$since]
    );
    $doctorPerf = qall(
        "SELECT d.name, d.reference_code, COUNT(r.id) refs,
                SUM(r.discount_amount) discount, SUM(r.order_amount) attributed
         FROM doctors d JOIN doctor_referrals r ON r.doctor_id = d.id AND r.status <> 'cancelled' AND r.referred_at >= ?
        GROUP BY d.id, d.name, d.reference_code ORDER BY attributed DESC",
        [date('Y-m-d', time() - ($days - 1) * 86400)]
    );

    $tabs = '<div class="adm-tabs"><a href="' . e(aurl('reports')) . '" class="on">Reports</a>';
    if (can('settings')) {
        $tabs .= '<a href="' . e(aurl('reports', ['tab' => 'settings'])) . '">System settings</a>'
            . '<a href="' . e(aurl('reports', ['tab' => 'admins'])) . '">Admin accounts</a>'
            . '<a href="' . e(aurl('reports', ['tab' => 'logs'])) . '">Audit log</a>';
    }
    $tabs .= '</div>';

    $rangeLinks = '<div class="adm-tabs" style="border:0;margin-bottom:12px">';
    foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '1 year'] as $d => $lbl) {
        $rangeLinks .= '<a href="' . e(aurl('reports', ['days' => $d])) . '" class="' . ($d === $days ? 'on' : '') . '">' . $lbl . '</a>';
    }
    $rangeLinks .= '</div>';

    $html = page_head('Reports & Settings', 'Sales performance, referral attribution and system configuration.');
    $html .= $tabs . $rangeLinks;

    $html .= '<div class="adm-metrics">'
        . metric_card('Revenue (' . $days . 'd)', amoney($totals['revenue'] ?? 0), 'reports', 'green')
        . metric_card('Orders', (string)($totals['orders'] ?? 0), 'orders', 'blue')
        . metric_card('Avg order value', amoney($totals['avg_order'] ?? 0), 'offer', 'purple')
        . metric_card('Doctor referrals', (string)(int)qval('SELECT COUNT(*) FROM doctor_referrals WHERE referred_at >= ?', [date('Y-m-d', time() - ($days - 1) * 86400)]), 'doctor', 'amber')
        . '</div>';

    $html .= '<div class="adm-grid-2">';
    /* daily chart (CSS bars) */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Orders &amp; revenue by day</h3></div><div class="adm-card-pad">';
    if (!$byDay) {
        $html .= '<p style="color:var(--a-muted);font-size:13px">No orders in this period.</p>';
    } else {
        $maxS = max(1.0, ...array_map(static fn($r) => (float)$r['s'], $byDay));
        $html .= '<div style="display:flex;align-items:flex-end;gap:4px;height:150px">';
        foreach (array_slice($byDay, -30) as $row) {
            $h = max(4, (int)round($row['s'] / $maxS * 130));
            $html .= '<div title="' . e($row['d']) . ': ' . (int)$row['c'] . ' orders, ' . amoney($row['s']) . '" style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">'
                . '<div style="width:100%;height:' . $h . 'px;background:var(--a-brand);border-radius:4px 4px 0 0"></div>'
                . '<small style="font-size:9px;color:var(--a-faint)">' . e(date('d', strtotime((string)$row['d']))) . '</small></div>';
        }
        $html .= '</div>';
    }
    $html .= '</div></div>';

    /* top products */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Top products (' . $days . 'd)</h3></div>';
    if (!$topProducts) {
        $html .= '<div class="adm-empty" style="padding:28px"><p>No product sales in this period.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Product</th><th class="num">Qty</th><th class="num">Revenue</th></tr></thead><tbody>';
        foreach ($topProducts as $p) {
            $html .= '<tr><td>' . e($p['product_name']) . '</td><td class="num">' . (int)$p['qty'] . '</td><td class="num">' . amoney($p['revenue']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div></div></div>';

    $html .= '<div class="adm-grid-2 mt-3">';
    /* doctor performance */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Doctor referral performance</h3></div>';
    if (!$doctorPerf) {
        $html .= '<div class="adm-empty" style="padding:28px"><p>No referrals in this period.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Doctor</th><th class="num">Refs</th><th class="num">Attributed</th><th class="num">Discount</th></tr></thead><tbody>';
        foreach ($doctorPerf as $d) {
            $html .= '<tr><td><a href="' . e(aurl('doctors/view/' . (int)qval('SELECT id FROM doctors WHERE reference_code = ?', [$d['reference_code']]))) . '">' . e($d['name']) . '</a></td>'
                . '<td class="num">' . (int)$d['refs'] . '</td><td class="num">' . amoney($d['attributed']) . '</td><td class="num">' . amoney($d['discount']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div>';

    /* category revenue */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Revenue by category</h3></div>';
    if (!$byCategory) {
        $html .= '<div class="adm-empty" style="padding:28px"><p>No category sales in this period.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Category</th><th class="num">Items</th><th class="num">Revenue</th></tr></thead><tbody>';
        foreach ($byCategory as $c) {
            $html .= '<tr><td>' . e($c['cat']) . '</td><td class="num">' . (int)$c['items'] . '</td><td class="num">' . amoney($c['revenue']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div></div></div>';

    render_layout('Reports', 'reports', $html, true);
}

/* ---------- system settings tab ---------- */
public static function reports_settings_tab(): void
{
    $rows = qall('SELECT * FROM settings ORDER BY skey');
    $map = [];
    foreach ($rows as $r) { $map[$r['skey']] = $r['svalue']; }

    $fields = [
        'store_name' => ['Store name', 'Shown on invoices and emails'],
        'store_email' => ['Support email', 'Customer support contact'],
        'store_phone' => ['Support phone', 'Customer support phone'],
        'delivery_charge' => ['Default delivery charge', 'Fallback courier fee'],
        'free_delivery_min' => ['Free delivery minimum', 'Order subtotal for free delivery'],
        'currency_symbol' => ['Currency symbol', 'e.g. ৳ or $'],
        'low_stock_email' => ['Low-stock alert email', 'Optional notification address'],
    ];

    $html = page_head('Reports & Settings', 'System-wide key/value settings used across the admin and storefront.');
    $html .= '<div class="adm-tabs"><a href="' . e(aurl('reports')) . '">Reports</a><a href="' . e(aurl('reports', ['tab' => 'settings'])) . '" class="on">System settings</a>'
        . '<a href="' . e(aurl('reports', ['tab' => 'admins'])) . '">Admin accounts</a>'
        . '<a href="' . e(aurl('reports', ['tab' => 'logs'])) . '">Audit log</a></div>';

    $html .= '<div class="adm-card" style="max-width:680px"><div class="adm-card-pad"><form method="post" action="' . e(aurl('reports/setting_save')) . '">' . csrf_field();
    foreach ($fields as $k => [$label, $hint]) {
        $html .= '<div class="afield"><label>' . e($label) . '</label><input class="ainput" name="s[' . e($k) . ']" value="' . e($map[$k] ?? '') . '">'
            . '<div class="hint">' . e($hint) . '</div></div>';
    }
    $html .= '<div class="form-actions"><button class="abtn primary">Save settings</button></div></form></div></div>';
    render_layout('System settings', 'reports', $html);
}

public static function reports_settings_save(): void
{
    verify_csrf_post();
    $allowed = ['store_name', 'store_email', 'store_phone', 'delivery_charge', 'free_delivery_min', 'currency_symbol', 'low_stock_email'];
    $saved = 0;
    foreach ((request()->post('s') ?? []) as $k => $v) {
        if (!in_array($k, $allowed, true)) { continue; }
        $v = mb_substr(trim((string)$v), 0, 128);
        $exists = qval('SELECT skey FROM settings WHERE skey = ?', [$k]);
        if ($exists) {
            q('UPDATE settings SET svalue = ? WHERE skey = ?', [$v, $k]);
        } else {
            q('INSERT INTO settings (skey, svalue) VALUES (?,?)', [$k, $v]);
        }
        $saved++;
    }
    log_action('settings.save', 'settings', null, $saved . ' keys updated');
    flash_redirect('success', $saved . ' setting(s) saved.', 'reports', ['tab' => 'settings']);
}

/* ---------- admin accounts tab ---------- */
public static function reports_admins_tab(): void
{
    $rows = qall(
        "SELECT u.*, (SELECT COUNT(*) FROM admin_logs l WHERE l.user_id = u.id) AS actions
         FROM users u WHERE u.role IN ('admin','manager','staff') ORDER BY u.role, u.name"
    );

    $html = page_head('Reports & Settings', 'Staff accounts with role-based access (staff < manager < admin).');
    $html .= '<div class="adm-tabs"><a href="' . e(aurl('reports')) . '">Reports</a><a href="' . e(aurl('reports', ['tab' => 'settings'])) . '">System settings</a>'
        . '<a href="' . e(aurl('reports', ['tab' => 'admins'])) . '" class="on">Admin accounts</a>'
        . '<a href="' . e(aurl('reports', ['tab' => 'logs'])) . '">Audit log</a></div>';

    $html .= '<div class="adm-card"><div class="adm-toolbar"><b style="font-size:13px">Create staff account</b><span class="spacer"></span></div>';
    $html .= '<div class="adm-card-pad"><form method="post" action="' . e(aurl('reports/user_save')) . '" class="row g-2 align-items-end">' . csrf_field()
        . '<div class="col-md-3"><label class="form-label" style="font-size:12px;font-weight:700">Name</label><input class="ainput" name="name" required></div>'
        . '<div class="col-md-3"><label class="form-label" style="font-size:12px;font-weight:700">Email</label><input class="ainput" type="email" name="email" required></div>'
        . '<div class="col-md-2"><label class="form-label" style="font-size:12px;font-weight:700">Role</label><select class="aselect" name="role"><option value="staff">Staff</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>'
        . '<div class="col-md-2"><label class="form-label" style="font-size:12px;font-weight:700">Password</label><input class="ainput" type="password" name="password" minlength="8" required></div>'
        . '<div class="col-md-2"><button class="abtn primary w-100">Create</button></div>'
        . '</form></div></div>';

    $html .= '<div class="adm-card mt-3"><div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th class="num">Actions</th><th></th></tr></thead><tbody>';
    foreach ($rows as $u) {
        $html .= '<tr><td><span class="cell-main">' . e($u['name']) . '</span></td><td>' . e($u['email']) . '</td>'
            . '<td><span class="abadge ' . ($u['role'] === 'admin' ? 'purple' : ($u['role'] === 'manager' ? 'blue' : 'gray')) . '">' . e(ucfirst((string)$u['role'])) . '</span></td>'
            . '<td>' . status_badge((string)$u['status']) . '</td>'
            . '<td>' . e(dt($u['last_login_at'])) . '</td>'
            . '<td class="num">' . (int)$u['actions'] . '</td>'
            . '<td class="actions-cell">';
        if ((int)$u['id'] !== (int)session('admin_user.id') && $u['role'] !== 'admin') {
            $html .= '<form method="post" action="' . e(aurl('reports/user_delete/' . (int)$u['id'])) . '" class="d-inline"' . confirm_delete_js() . '>' . csrf_field()
                . '<button class="abtn danger-soft xs">Remove</button></form>';
        } else {
            $html .= '<span class="cell-sub">protected</span>';
        }
        $html .= '</td></tr>';
    }
    $html .= '</tbody></table></div></div>';

    render_layout('Admin accounts', 'reports', $html, true);
}

public static function reports_user_save(): void
{
    verify_csrf_post();
    $name = post('name');
    $email = post('email');
    $role = post('role');
    $password = (string)(request()->post('password') ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['admin', 'manager', 'staff'], true) || mb_strlen($password) < 8) {
        flash('error', 'Provide a name, valid email, role and a password of at least 8 characters.');
        aredirect('reports', ['tab' => 'admins']);
    }
    if ((int)qval('SELECT id FROM users WHERE email = ?', [$email])) {
        flash('error', 'A user with this email already exists.');
        aredirect('reports', ['tab' => 'admins']);
    }
    $id = insert_row('users', [
        'name' => $name, 'email' => $email, 'phone' => post('phone') ?: null,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role, 'status' => 'active',
    ]);
    log_action('user.create', 'user', $id, $email . ' (' . $role . ')');
    flash_redirect('success', 'Account created for ' . $name . ' (' . $role . ').', 'reports', ['tab' => 'admins']);
}

public static function reports_user_delete(int $id): void
{
    verify_csrf_post();
    if ($id === (int)session('admin_user.id')) {
        flash('error', 'You cannot remove your own account.');
        aredirect('reports', ['tab' => 'admins']);
    }
    $u = qrow("SELECT * FROM users WHERE id = ? AND role IN ('admin','manager','staff')", [$id]);
    if (!$u) {
        flash('error', 'Staff account not found.');
        aredirect('reports', ['tab' => 'admins']);
    }
    q("UPDATE users SET role = 'customer', status = 'active' WHERE id = ?", [$id]);
    log_action('user.demote', 'user', $id, $u['email'] . ' → customer');
    flash_redirect('success', $u['name'] . ' was demoted to customer (history preserved).', 'reports', ['tab' => 'admins']);
}

/* ---------- audit log tab ---------- */
public static function reports_logs_tab(): void
{
    $page = pint(getq('page', '1'));
    $total = (int)qval('SELECT COUNT(*) FROM admin_logs');
    [$limitSql, , $pager] = paginate($total, $page, 40);
    $rows = qall(
        "SELECT l.*, u.name, u.email FROM admin_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC $limitSql"
    );

    $html = page_head('Reports & Settings', 'Audit trail of every admin action.');
    $html .= '<div class="adm-tabs"><a href="' . e(aurl('reports')) . '">Reports</a><a href="' . e(aurl('reports', ['tab' => 'settings'])) . '">System settings</a>'
        . '<a href="' . e(aurl('reports', ['tab' => 'admins'])) . '">Admin accounts</a>'
        . '<a href="' . e(aurl('reports', ['tab' => 'logs'])) . '" class="on">Audit log</a></div>';

    $html .= '<div class="adm-card">';
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('reports') . '</div><h3>No activity yet</h3></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Detail</th><th>IP</th></tr></thead><tbody>';
        foreach ($rows as $l) {
            $html .= '<tr><td>' . e(dt($l['created_at'])) . '</td>'
                . '<td>' . e($l['name'] ?: 'system') . '</td>'
                . '<td><span class="code-chip">' . e($l['action']) . '</span></td>'
                . '<td>' . e($l['entity'] ?: '—') . ($l['entity_id'] ? ' #' . (int)$l['entity_id'] : '') . '</td>'
                . '<td class="cell-sub">' . e($l['detail'] ?: '') . '</td>'
                . '<td class="cell-sub">' . e($l['ip'] ?: '') . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, ['tab' => 'logs']);
    }
    $html .= '</div>';

    render_layout('Audit log', 'reports', $html, true);
}
}
