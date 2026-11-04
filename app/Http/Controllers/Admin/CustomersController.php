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
 * Customers — user accounts with role=customer: list, detail, orders, status, password reset.
 */
final class CustomersController extends Controller
{


public static function mod_customers(string $action, int $id): void
{
    switch ($action) {
        case 'view':
            self::customers_view(self::customers_find($id));
            break;
        case 'toggle':
            require_module('customers', 'edit');
            self::customers_toggle($id);
            break;
        case 'password':
            require_module('customers', 'edit');
            self::customers_password($id);
            break;
        case 'reset_form':
            require_module('customers', 'edit');
            self::customers_password_form(self::customers_find($id));
            break;
        default:
            self::customers_list();
    }
}

public static function customers_find(int $id): array
{
    $row = qrow('SELECT * FROM users WHERE id = ? AND role = ?', [$id, 'customer']);
    if (!$row) {
        adm_status(404);
        render_layout('Customer not found', 'customers', '<div class="adm-card adm-card-pad adm-empty"><h3>Customer not found</h3><a class="abtn primary mt-3" href="' . e(aurl('customers')) . '">Back</a></div>');
    }
    return $row;
}

public static function customers_list(): void
{
    $q = getq('q');
    $status = getq('status');
    $page = pint(getq('page', '1'));

    $where = "u.role = 'customer'";
    $params = [];
    if ($q !== '') {
        $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        $like = like($q);
        array_push($params, $like, $like, $like);
    }
    if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
        $where .= ' AND u.status = ?';
        $params[] = $status;
    }

    $total = (int)qval("SELECT COUNT(*) FROM users u WHERE $where", $params);
    [$limitSql, , $pager] = paginate($total, $page, (int)config('admin.per_page'));
    $rows = qall(
        "SELECT u.*,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.status <> 'cancelled') AS total_spent
         FROM users u WHERE $where ORDER BY u.created_at DESC, u.id DESC $limitSql",
        $params
    );

    $html = page_head('Customers', 'Registered storefront accounts and their order activity.');
    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([
        filter_select('status', 'Status', ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'], $status),
    ], 'Search name, email or phone…', $q);

    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('customers') . '</div><h3>No customers found</h3><p>' . ($q || $status ? 'Nothing matches the current filters.' : 'Customers who register on the storefront will appear here.') . '</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Customer</th><th>Contact</th><th class="num">Orders</th><th class="num">Spent</th><th>Status</th><th>Joined</th><th></th></tr></thead><tbody>';
        foreach ($rows as $u) {
            $html .= '<tr><td><a href="' . e(aurl('customers/view/' . (int)$u['id'])) . '"><span class="cell-main">' . e($u['name']) . '</span></a></td>'
                . '<td>' . e($u['email']) . '<div class="cell-sub">' . e($u['phone'] ?: '—') . '</div></td>'
                . '<td class="num"><b>' . (int)$u['order_count'] . '</b></td>'
                . '<td class="num">' . amoney($u['total_spent']) . '</td>'
                . '<td>' . status_badge((string)$u['status']) . '</td>'
                . '<td>' . e(d($u['created_at'])) . '</td>'
                . '<td class="actions-cell"><a class="abtn secondary xs" href="' . e(aurl('customers/view/' . (int)$u['id'])) . '">View</a></td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, array_filter(['q' => $q, 'status' => $status]));
    }
    $html .= '</div>';
    render_layout('Customers', 'customers', $html);
}

public static function customers_view(array $u): void
{
    $custId = (int)$u['id'];
    $orders = qall('SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS item_count FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 50', [$custId]);
    $stats = [
        'orders' => (int)qval('SELECT COUNT(*) FROM orders WHERE user_id = ?', [$custId]),
        'spent' => (float)qval("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id = ? AND status <> 'cancelled'", [$custId]),
        'pending' => (int)qval("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'", [$custId]),
        'referrals' => (int)qval('SELECT COUNT(*) FROM doctor_referrals WHERE customer_id = ?', [$custId]),
    ];
    $referrals = qall(
        'SELECT r.*, d.name AS doctor FROM doctor_referrals r JOIN doctors d ON d.id = r.doctor_id WHERE r.customer_id = ? ORDER BY r.referred_at DESC LIMIT 20',
        [$custId]
    );

    $actions = '<a class="abtn ghost" href="' . e(aurl('customers')) . '">← All customers</a>';
    if (can('customers', 'edit')) {
        $actions .= '<a class="abtn secondary" href="' . e(aurl('customers/reset_form/' . $custId)) . '">Reset password</a>';
        $actions .= '<form method="post" action="' . e(aurl('customers/toggle/' . $custId)) . '" class="d-inline">' . csrf_field()
            . '<button class="abtn ' . ($u['status'] === 'active' ? 'danger-soft' : 'primary') . '">' . ($u['status'] === 'active' ? 'Deactivate' : 'Activate') . '</button></form>';
    }

    $html = page_head($u['name'], 'Customer since ' . d($u['created_at']), $actions);

    $html .= '<div class="adm-metrics">'
        . metric_card('Orders', (string)$stats['orders'], 'orders', 'blue', $stats['pending'] . ' pending')
        . metric_card('Total spent', amoney($stats['spent']), 'reports', 'green')
        . metric_card('Referrals used', (string)$stats['referrals'], 'coupon', 'purple')
        . '</div>';

    $html .= '<div class="adm-grid-2">';
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Account</h3>' . status_badge((string)$u['status']) . '</div><div class="adm-card-pad"><dl class="dl-grid">';
    $html .= '<div><dt>Email</dt><dd>' . e($u['email']) . '</dd></div>';
    $html .= '<div><dt>Phone</dt><dd>' . e($u['phone'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Role</dt><dd>' . e(ucfirst((string)$u['role'])) . '</dd></div>';
    $html .= '<div><dt>Last login</dt><dd>' . e(dt($u['last_login_at'])) . '</dd></div>';
    $html .= '</dl></div></div>';

    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Orders</h3></div>';
    if (!$orders) {
        $html .= '<div class="adm-empty" style="padding:28px"><p>No orders yet.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Order</th><th class="num">Items</th><th class="num">Total</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        foreach ($orders as $o) {
            $html .= '<tr><td><a href="' . e(aurl('orders/view/' . (int)$o['id'])) . '">' . e($o['order_number'] ?: '#' . $o['id']) . '</a></td>'
                . '<td class="num">' . (int)$o['item_count'] . '</td><td class="num">' . amoney($o['total']) . '</td>'
                . '<td>' . status_badge((string)$o['status']) . '</td><td>' . e(d($o['created_at'])) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div></div>';

    if ($referrals) {
        $html .= '<div class="adm-card mt-3"><div class="adm-card-head"><h3>Referral usage</h3></div><div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Date</th><th>Doctor</th><th>Coupon</th><th class="num">Discount</th><th>Status</th></tr></thead><tbody>';
        foreach ($referrals as $r) {
            $html .= '<tr><td>' . e(d($r['referred_at'])) . '</td><td>' . e($r['doctor']) . '</td>'
                . '<td><span class="code-chip">' . e($r['coupon_code'] ?: '—') . '</span></td>'
                . '<td class="num">' . amoney($r['discount_amount']) . '</td><td>' . status_badge((string)$r['status']) . '</td></tr>';
        }
        $html .= '</tbody></table></div></div>';
    }

    render_layout($u['name'], 'customers', $html);
}

public static function customers_toggle(int $id): void
{
    verify_csrf_post();
    $u = self::customers_find($id);
    $new = $u['status'] === 'active' ? 'inactive' : 'active';
    q('UPDATE users SET status = ? WHERE id = ?', [$new, $id]);
    log_action('customer.toggle', 'user', $id, $u['email'] . ' → ' . $new);
    flash_redirect('success', $u['name'] . ' is now ' . $new . '.', 'customers');
}

public static function customers_password_form(array $u): void
{
    $html = page_head('Reset password', 'Set a new password for ' . $u['name'] . ' (' . $u['email'] . ').',
        '<a class="abtn ghost" href="' . e(aurl('customers/view/' . (int)$u['id'])) . '">← Back</a>');
    $html .= '<div class="adm-card" style="max-width:520px"><div class="adm-card-pad"><form method="post" action="' . e(aurl('customers/password/' . (int)$u['id'])) . '">' . csrf_field();
    $html .= '<div class="afield"><label>New password <span class="hint">(min 8 characters)</span></label><input class="ainput" type="password" name="password" minlength="8" required autocomplete="new-password"></div>';
    $html .= '<div class="afield"><label>Confirm password</label><input class="ainput" type="password" name="password2" minlength="8" required autocomplete="new-password"></div>';
    $html .= '<div class="form-actions"><button class="abtn primary">Update password</button></div></form></div></div>';
    render_layout('Reset password', 'customers', $html);
}

public static function customers_password(int $id): void
{
    verify_csrf_post();
    $u = self::customers_find($id);
    $p1 = (string)(request()->post('password') ?? '');
    $p2 = (string)(request()->post('password2') ?? '');
    if (mb_strlen($p1) < 8) {
        flash('error', 'Password must be at least 8 characters.');
        aredirect('customers/reset_form/' . $id);
    }
    if ($p1 !== $p2) {
        flash('error', 'Passwords do not match.');
        aredirect('customers/reset_form/' . $id);
    }
    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($p1, PASSWORD_DEFAULT), $id]);
    log_action('customer.password', 'user', $id, $u['email']);
    flash_redirect('success', 'Password updated for ' . $u['name'] . '.', 'customers/view/' . $id);
}
}
