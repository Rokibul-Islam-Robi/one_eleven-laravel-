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
 * Dashboard — live statistics, alerts, recent activity.
 */
final class DashboardController extends Controller
{


public static function mod_dashboard(string $action, int $id): void
{
    $stats = [
        'products'   => (int)qval('SELECT COUNT(*) FROM products'),
        'orders'     => (int)qval('SELECT COUNT(*) FROM orders'),
        'pending'    => (int)qval("SELECT COUNT(*) FROM orders WHERE status='pending'"),
        'revenue'    => (float)qval("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'"),
        'customers'  => (int)qval("SELECT COUNT(*) FROM users WHERE role='customer'"),
        'doctors'    => (int)qval('SELECT COUNT(*) FROM doctors'),
        'active_docs'=> (int)qval("SELECT COUNT(*) FROM doctors WHERE status='active'"),
        'referrals'  => (int)qval('SELECT COUNT(*) FROM doctor_referrals'),
        'rx_pending' => (int)qval("SELECT COUNT(*) FROM prescriptions WHERE status IN ('pending','reviewing')"),
        'coupons'    => (int)qval('SELECT COUNT(*) FROM coupons WHERE active=1'),
        'low_stock'  => (int)qval('SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= low_stock_threshold'),
        'expiring'   => (int)qval("SELECT COUNT(*) FROM inventory_batches WHERE qty_remaining > 0 AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"),
        'expired'    => (int)qval("SELECT COUNT(*) FROM inventory_batches WHERE qty_remaining > 0 AND expiry_date IS NOT NULL AND expiry_date < CURDATE()"),
        'couriers'   => (int)qval("SELECT COUNT(*) FROM courier_methods WHERE status='active'"),
    ];

    $recentOrders = qall(
        'SELECT o.*, u.name AS customer
         FROM orders o LEFT JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC LIMIT 6'
    );
    $lowStock = qall(
        'SELECT id, name, stock, low_stock_threshold FROM products
         WHERE stock <= low_stock_threshold ORDER BY stock ASC LIMIT 6'
    );
    $expiring = qall(
        "SELECT b.*, p.name AS product FROM inventory_batches b
         JOIN products p ON p.id = b.product_id
         WHERE b.qty_remaining > 0 AND b.expiry_date IS NOT NULL
         ORDER BY b.expiry_date ASC LIMIT 6"
    );
    $topDoctors = qall(
        'SELECT d.id, d.name, d.specialty, d.reference_code,
                COUNT(r.id) AS refs, COALESCE(SUM(r.discount_amount),0) AS total_discount
         FROM doctors d LEFT JOIN doctor_referrals r ON r.doctor_id = d.id AND r.status <> "cancelled"
        GROUP BY d.id, d.name, d.specialty, d.reference_code
        ORDER BY refs DESC, total_discount DESC LIMIT 5'
    );
    $statusRows = qall('SELECT status, COUNT(*) c, COALESCE(SUM(total),0) s FROM orders GROUP BY status');

    $u = current_user();
    $html = page_head(
        'Dashboard',
        'Welcome back, ' . $u['name'] . '. Here is what is happening at ' . config('admin.name') . ' today.',
        (can('reports') ? '<a class="abtn secondary" href="' . e(aurl('reports')) . '">' . icon('reports') . ' Reports</a>' : '')
        . (can('doctors', 'edit') ? '<a class="abtn primary" href="' . e(aurl('doctors/new')) . '">' . icon('plus') . ' Add Doctor</a>' : '')
    );

    $html .= '<div class="adm-metrics">'
        . metric_card('Revenue (all orders)', amoney($stats['revenue']), 'reports', 'green')
        . metric_card('Orders', (string)$stats['orders'], 'orders', 'blue', $stats['pending'] . ' pending')
        . metric_card('Products', (string)$stats['products'], 'products', 'purple', $stats['low_stock'] . ' low stock')
        . metric_card('Customers', (string)$stats['customers'], 'customers', 'amber')
        . metric_card('Doctors', (string)$stats['doctors'], 'doctor', 'green', $stats['active_docs'] . ' active')
        . metric_card('Referrals', (string)$stats['referrals'], 'coupon', 'blue')
        . metric_card('Prescriptions', (string)$stats['rx_pending'], 'rx', 'amber', 'awaiting review')
        . metric_card('Expiry alerts', (string)($stats['expired'] + $stats['expiring']), 'alert', 'red', $stats['expired'] . ' already expired')
        . '</div>';

    /* alerts row */
    $alerts = [];
    if ($stats['expired'] > 0) {
        $alerts[] = '<div class="alert alert-danger adm-alert mb-3"><b>' . $stats['expired'] . ' expired batch(es)</b> still have remaining stock. <a href="' . e(aurl('inventory', ['tab' => 'expiry'])) . '">Review inventory</a></div>';
    }
    if ($stats['expiring'] > 0) {
        $alerts[] = '<div class="alert alert-warning adm-alert mb-3"><b>' . $stats['expiring'] . ' batch(es)</b> expire within 30 days. <a href="' . e(aurl('inventory', ['tab' => 'expiry'])) . '">See expiring stock</a></div>';
    }
    if ($stats['rx_pending'] > 0) {
        $alerts[] = '<div class="alert alert-info adm-alert mb-3"><b>' . $stats['rx_pending'] . ' prescription(s)</b> await review. <a href="' . e(aurl('prescriptions')) . '">Open prescriptions</a></div>';
    }
    if ($stats['pending'] > 0) {
        $alerts[] = '<div class="alert alert-info adm-alert mb-3"><b>' . $stats['pending'] . ' order(s)</b> are pending processing. <a href="' . e(aurl('orders', ['status' => 'pending'])) . '">Open orders</a></div>';
    }
    $html .= implode('', $alerts);

    $html .= '<div class="adm-grid-2">';

    /* recent orders */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Recent orders</h3><a class="abtn ghost sm" href="' . e(aurl('orders')) . '">View all</a></div>';
    if (!$recentOrders) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('orders') . '</div><h3>No orders yet</h3><p>Orders placed on the storefront will appear here.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="num">Total</th></tr></thead><tbody>';
        foreach ($recentOrders as $o) {
            $html .= '<tr><td><a href="' . e(aurl('orders/view/' . (int)$o['id'])) . '"><span class="cell-main">' . e($o['order_number'] ?: '#' . $o['id']) . '</span></a>'
                . '<div class="cell-sub">' . e(dt($o['created_at'])) . '</div></td>'
                . '<td>' . e($o['customer'] ?: $o['shipping_name']) . '<div class="cell-sub">' . e($o['shipping_phone']) . '</div></td>'
                . '<td>' . status_badge((string)$o['status']) . '</td>'
                . '<td class="num">' . amoney($o['total']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div>';

    /* inventory alerts */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Inventory &amp; expiry alerts</h3><a class="abtn ghost sm" href="' . e(aurl('inventory')) . '">Open inventory</a></div><div class="adm-card-pad">';
    $html .= '<ul class="mini-list">';
    foreach ($expiring as $b) {
        $exp = (string)$b['expiry_date'];
        $isExpired = $exp !== '' && $exp < date('Y-m-d');
        $soon = !$isExpired && $exp !== '' && strtotime($exp) <= strtotime('+30 days');
        $cls = $isExpired ? 'exp-expired' : ($soon ? 'exp-soon' : 'exp-ok');
        $label = $isExpired ? 'Expired' : ($soon ? 'Expiring soon' : 'OK');
        $html .= '<li><span class="grow"><span class="t">' . e($b['product']) . '</span><span class="s">Batch ' . e($b['batch_number']) . ' · ' . (int)$b['qty_remaining'] . ' left</span></span>'
            . '<span class="' . $cls . '" style="font-size:12px">' . e($label) . ' · ' . e(d($exp)) . '</span></li>';
    }
    foreach ($lowStock as $p) {
        $html .= '<li><span class="grow"><span class="t">' . e($p['name']) . '</span><span class="s">Low stock threshold: ' . (int)$p['low_stock_threshold'] . '</span></span>'
            . '<span class="exp-expired" style="font-size:12px">' . (int)$p['stock'] . ' in stock</span></li>';
    }
    if (!$expiring && !$lowStock) {
        $html .= '<li class="justify-content-center" style="color:var(--a-muted)">No inventory alerts. All stock levels and expiry dates look healthy.</li>';
    }
    $html .= '</ul></div></div>';

    $html .= '</div>'; // grid-2

    $html .= '<div class="adm-grid-2 mt-3">';

    /* order status breakdown */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Orders by status</h3></div><div class="adm-card-pad">';
    if (!$statusRows) {
        $html .= '<p style="color:var(--a-muted);font-size:13px">No orders recorded yet.</p>';
    } else {
        $totalOrders = max(1, array_sum(array_column($statusRows, 'c')));
        $html .= '<ul class="mini-list">';
        foreach ($statusRows as $s) {
            $pct = (int)round($s['c'] / $totalOrders * 100);
            $html .= '<li><span class="grow"><span class="t">' . e(ucfirst((string)$s['status'])) . ' <span class="s">(' . (int)$s['c'] . ' · ' . amoney($s['s']) . ')</span></span>'
                . '<span class="stock-bar"><i style="width:' . $pct . '%"></i></span></span></li>';
        }
        $html .= '</ul>';
    }
    $html .= '</div></div>';

    /* top referring doctors */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Top referring doctors</h3><a class="abtn ghost sm" href="' . e(aurl('doctors')) . '">All doctors</a></div><div class="adm-card-pad">';
    $html .= '<ul class="mini-list">';
    foreach ($topDoctors as $d) {
        $html .= '<li><span class="grow"><span class="t">' . e($d['name']) . '</span><span class="s">' . e($d['specialty'] ?: 'General') . ' · ' . e($d['reference_code']) . '</span></span>'
            . '<span style="text-align:right"><b style="font-size:13px">' . (int)$d['refs'] . ' refs</b><div class="s">' . amoney($d['total_discount']) . '</div></span></li>';
    }
    if (!$topDoctors) {
        $html .= '<li style="color:var(--a-muted)">No doctors registered yet.</li>';
    }
    $html .= '</ul></div></div>';

    $html .= '</div>';

    render_layout('Dashboard', 'dashboard', $html);
}
}
