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
 * Orders & Shipping — list, detail, status flow, courier assignment,
 * prescription gate, payment status, stock release and returns.
 */
final class OrdersController extends Controller
{
    public const ORDER_STATUSES = [
        'pending', 'awaiting_prescription', 'processing', 'shipped',
        'delivered', 'failed_delivery', 'return_requested', 'returned', 'cancelled',
    ];
    public const ORDER_TRANSITIONS = [
        'pending'               => ['processing', 'awaiting_prescription', 'cancelled'],
        'awaiting_prescription' => ['processing', 'cancelled'],
        'processing'            => ['shipped', 'cancelled'],
        'shipped'               => ['delivered', 'failed_delivery', 'cancelled'],
        'delivered'             => ['return_requested'],
        'failed_delivery'       => ['returned', 'cancelled', 'processing'],
        'return_requested'      => ['returned', 'delivered'],
        'returned'              => [],
        'cancelled'             => [],
    ];
    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'failed', 'refunded'];

public static function mod_orders(string $action, int $id): void
{
    switch ($action) {
        case 'view':
            self::orders_view(self::orders_find($id));
            break;
        case 'print':
            self::orders_print(self::orders_find($id));
            break;
        case 'update':
            require_module('orders', 'edit');
            self::orders_update($id);
            break;
        case 'return':
            require_module('orders', 'edit');
            self::orders_return($id);
            break;
        default:
            self::orders_list();
    }
}

public static function orders_find(int $id): array
{
    $row = qrow(
        'SELECT o.*, u.name AS customer, u.email AS customer_email, cm.name AS courier_name, cm.tracking_url
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         LEFT JOIN courier_methods cm ON cm.id = o.courier_id
         WHERE o.id = ?',
        [$id]
    );
    if (!$row) {
        adm_status(404);
        render_layout('Order not found', 'orders', '<div class="adm-card adm-card-pad adm-empty"><h3>Order not found</h3><a class="abtn primary mt-3" href="' . e(aurl('orders')) . '">Back to orders</a></div>');
    }
    return $row;
}

public static function orders_list(): void
{
    $q = getq('q');
    $status = getq('status');
    $page = pint(getq('page', '1'));

    $where = '1=1';
    $params = [];
    if ($q !== '') {
        $where .= ' AND (o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ? OR o.tracking_code LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
        $like = like($q);
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    if ($status !== '' && in_array($status, self::ORDER_STATUSES, true)) {
        $where .= ' AND o.status = ?';
        $params[] = $status;
    }

    $total = (int)qval("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE $where", $params);
    [$limitSql, , $pager] = paginate($total, $page, (int)config('admin.per_page'));
    $rows = qall(
        "SELECT o.*, u.name AS customer, cm.name AS courier_name,
                (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS item_count
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         LEFT JOIN courier_methods cm ON cm.id = o.courier_id
         WHERE $where ORDER BY o.created_at DESC, o.id DESC $limitSql",
        $params
    );
    $counts = [];
    foreach (qall('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $r) { $counts[$r['status']] = (int)$r['c']; }

    $html = page_head('Orders & Shipping', 'Process orders, assign couriers and update delivery status.');
    $tabs = '<div class="adm-tabs"><a href="' . e(aurl('orders')) . '" class="' . ($status === '' ? 'on' : '') . '">All <span class="cnt">' . (int)qval('SELECT COUNT(*) FROM orders') . '</span></a>';
    foreach (self::ORDER_STATUSES as $s) {
        $tabs .= '<a href="' . e(aurl('orders', ['status' => $s])) . '" class="' . ($status === $s ? 'on' : '') . '">' . e(ucfirst(str_replace('_', ' ', $s))) . ' <span class="cnt">' . ($counts[$s] ?? 0) . '</span></a>';
    }
    $html .= $tabs . '</div>';

    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([], 'Search order no, customer, phone or tracking…', $q);
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('orders') . '</div><h3>No orders found</h3><p>' . ($q || $status ? 'Nothing matches the current filters.' : 'Orders placed on the storefront will appear here.') . '</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Order</th><th>Customer</th><th>Courier</th><th class="num">Items</th><th class="num">Total</th><th>Status</th><th>Payment</th><th></th></tr></thead><tbody>';
        foreach ($rows as $o) {
            $html .= '<tr><td><a href="' . e(aurl('orders/view/' . (int)$o['id'])) . '"><span class="cell-main">' . e($o['order_number'] ?: '#' . $o['id']) . '</span></a>'
                . '<div class="cell-sub">' . e(dt($o['created_at'])) . '</div></td>'
                . '<td>' . e($o['customer'] ?: $o['shipping_name']) . '<div class="cell-sub">' . e($o['shipping_phone']) . '</div></td>'
                . '<td>' . e($o['courier_name'] ?: '—') . ($o['tracking_code'] ? '<div class="cell-sub code-chip">' . e($o['tracking_code']) . '</div>' : '') . '</td>'
                . '<td class="num">' . (int)$o['item_count'] . '</td>'
                . '<td class="num"><b>' . amoney($o['total']) . '</b></td>'
                . '<td>' . status_badge((string)$o['status']) . '</td>'
                . '<td>' . status_badge((string)($o['payment_status'] ?: 'unpaid')) . '</td>'
                . '<td class="actions-cell"><a class="abtn secondary xs" href="' . e(aurl('orders/view/' . (int)$o['id'])) . '">Open</a>'
                . '<a class="abtn ghost xs" href="' . e(aurl('orders/print/' . (int)$o['id'])) . '" target="_blank">Invoice</a></td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, array_filter(['q' => $q, 'status' => $status]));
    }
    $html .= '</div>';

    render_layout('Orders', 'orders', $html);
}

public static function orders_view(array $o): void
{
    $items = qall('SELECT * FROM order_items WHERE order_id = ?', [(int)$o['id']]);
    $couriers = qall("SELECT * FROM courier_methods ORDER BY sort_order, name");
    $referral = qrow('SELECT * FROM doctor_referrals WHERE order_id = ? LIMIT 1', [(int)$o['id']]);
    $doctor = $referral ? qrow('SELECT * FROM doctors WHERE id = ?', [(int)$referral['doctor_id']]) : null;
    $returns = qall('SELECT * FROM order_returns WHERE order_id = ? ORDER BY created_at DESC', [(int)$o['id']]);
    $rx = (int)$o['requires_prescription'] === 1
        ? qrow('SELECT * FROM prescriptions WHERE order_id = ? ORDER BY created_at DESC LIMIT 1', [(int)$o['id']])
        : null;
    $rxApproved = $rx && $rx['status'] === 'approved';

    $actions = '<a class="abtn ghost" href="' . e(aurl('orders')) . '">← All orders</a>'
        . '<a class="abtn secondary" href="' . e(aurl('orders/print/' . (int)$o['id'])) . '" target="_blank">' . icon('download') . ' Invoice</a>';

    $html = page_head('Order ' . ($o['order_number'] ?: '#' . $o['id']), 'Placed ' . dt($o['created_at']) . ' · ' . $o['status'], $actions);

    $html .= '<div class="adm-grid-2" style="grid-template-columns:1.6fr 1fr">';

    /* items + totals */
    $html .= '<div>';
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Items</h3></div><div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Product</th><th class="num">Price</th><th class="num">Qty</th><th class="num">Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $html .= '<tr><td>' . e($it['product_name']) . ($it['product_id'] ? '<div class="cell-sub">Product #' . (int)$it['product_id'] . '</div>' : '') . '</td>'
            . '<td class="num">' . amoney($it['price']) . '</td><td class="num">' . (int)$it['qty'] . '</td>'
            . '<td class="num"><b>' . amoney((float)$it['price'] * (int)$it['qty']) . '</b></td></tr>';
    }
    if (!$items) { $html .= '<tr><td colspan="4" class="cell-sub">No items recorded.</td></tr>'; }
    $html .= '</tbody></table></div>';
    $html .= '<div class="adm-card-pad" style="text-align:right;border-top:1px solid var(--a-border)">'
        . '<p style="margin:0;font-size:13px">Subtotal <b>' . amoney($o['subtotal']) . '</b></p>';
    if ((float)($o['discount_amount'] ?? 0) > 0) {
        $html .= '<p style="margin:2px 0 0;font-size:13px;color:#16813b">Discount (' . e($o['coupon_code'] ?: 'coupon') . ') <b>−' . amoney($o['discount_amount']) . '</b></p>';
    }
    $html .= '<p style="margin:2px 0 0;font-size:13px">Shipping <b>' . amoney($o['shipping']) . '</b></p>';
    if ((float)($o['cod_fee'] ?? 0) > 0) {
        $html .= '<p style="margin:2px 0 0;font-size:13px">COD fee <b>' . amoney($o['cod_fee']) . '</b></p>';
    }
    $html .= '<p style="margin:6px 0 0;font-size:16px;font-weight:800">Total ' . amoney($o['total']) . '</p></div>';
    $html .= '</div>';

    /* status update */
    $html .= '<div class="adm-card mt-3"><div class="adm-card-head"><h3>Shipping &amp; status</h3>' . status_badge((string)$o['status']) . '</div><div class="adm-card-pad">';
    $mainFlow = ['pending', 'awaiting_prescription', 'processing', 'shipped', 'delivered'];
    $flow = '<div class="adm-flow">';
    foreach ($mainFlow as $i => $s) {
        $flow .= ($i ? '<span class="f-arrow">→</span>' : '') . '<span class="f-pill' . ($o['status'] === $s ? ' on' : '') . '">' . e(ucfirst(str_replace('_', ' ', $s))) . '</span>';
    }
    $html .= $flow . '</div>';
    if (!in_array((string)$o['status'], $mainFlow, true)) {
        $html .= '<p class="cell-sub mt-2">Current state: ' . status_badge((string)$o['status']) . '</p>';
    }

    if ((int)$o['requires_prescription'] === 1) {
        $html .= '<div class="alert alert-warning mt-3" style="padding:10px 12px;border-radius:8px;font-size:13px">';
        $html .= '<b>Prescription-gated order.</b> ';
        if ($rx) {
            $html .= 'Linked prescription #' . (int)$rx['id'] . ' is <b>' . e($rx['status']) . '</b>. ';
            $html .= '<a href="' . e(aurl('prescriptions/view/' . (int)$rx['id'])) . '">Review</a>';
        } else {
            $html .= 'No prescription is linked yet — <a href="' . e(aurl('prescriptions')) . '">open prescriptions</a> to record or approve one.';
        }
        if (!$rxApproved && (string)$o['status'] !== 'cancelled') {
            $html .= ' Shipping is blocked until a prescription is approved.';
        }
        $html .= '</div>';
    }

    if (can('orders', 'edit')) {
        $html .= '<form method="post" action="' . e(aurl('orders/update/' . (int)$o['id'])) . '" class="mt-3">' . csrf_field();
        $html .= '<div class="afield"><label>Status</label><select class="aselect" name="status">';
        foreach (self::ORDER_STATUSES as $s) {
            $allowed = ($s === (string)$o['status']) || in_array($s, self::ORDER_TRANSITIONS[(string)$o['status']] ?? [], true);
            $html .= '<option value="' . $s . '"' . ($o['status'] === $s ? ' selected' : '') . ($allowed ? '' : ' disabled') . '>'
                . e(ucfirst(str_replace('_', ' ', $s))) . ($allowed ? '' : ' (not reachable)') . '</option>';
        }
        $html .= '</select></div>';
        $html .= '<div class="agrid-2"><div class="afield"><label>Courier</label><select class="aselect" name="courier_id"><option value="">— select courier —</option>';
        foreach ($couriers as $cm) {
            $html .= '<option value="' . (int)$cm['id'] . '"' . ((int)$o['courier_id'] === (int)$cm['id'] ? ' selected' : '') . '>' . e($cm['name']) . ' (' . amoney($cm['fee']) . ')</option>';
        }
        $html .= '</select></div>';
        $html .= '<div class="afield"><label>Tracking code</label><input class="ainput" name="tracking_code" value="' . e($o['tracking_code']) . '" placeholder="e.g. RX-TRK-123456"></div></div>';
        $html .= '<div class="afield"><label>Payment status</label><select class="aselect" name="payment_status">';
        foreach (self::PAYMENT_STATUSES as $ps) {
            $html .= '<option value="' . $ps . '"' . (($o['payment_status'] ?: 'unpaid') === $ps ? ' selected' : '') . '>' . e(ucfirst($ps)) . '</option>';
        }
        $html .= '</select></div>';
        $html .= '<div class="afield"><label>Admin notes</label><textarea class="atextarea" name="admin_notes" rows="2">' . e($o['admin_notes']) . '</textarea></div>';
        $html .= '<button class="abtn primary">Save shipping update</button></form>';
    }
    $html .= '</div></div>';

    /* returns */
    if ($returns) {
        $html .= '<div class="adm-card mt-3"><div class="adm-card-head"><h3>Return requests</h3></div><div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Reason</th><th>Status</th><th>Requested</th><th></th></tr></thead><tbody>';
        foreach ($returns as $r) {
            $html .= '<tr><td>' . e($r['reason'] ?: '—') . '</td><td>' . status_badge((string)$r['status']) . '</td><td class="cell-sub">' . e(dt($r['created_at'])) . '</td><td class="actions-cell">';
            if (can('orders', 'edit') && in_array($r['status'], ['requested'], true)) {
                $html .= '<form method="post" action="' . e(aurl('orders/return/' . (int)$o['id'])) . '" class="d-inline">' . csrf_field()
                    . '<input type="hidden" name="decision" value="approved"><button class="abtn secondary xs">Approve</button></form> '
                    . '<form method="post" action="' . e(aurl('orders/return/' . (int)$o['id'])) . '" class="d-inline">' . csrf_field()
                    . '<input type="hidden" name="decision" value="rejected"><button class="abtn ghost xs">Reject</button></form>';
            }
            if (can('orders', 'edit') && $r['status'] === 'approved') {
                $html .= '<form method="post" action="' . e(aurl('orders/return/' . (int)$o['id'])) . '" class="d-inline">' . csrf_field()
                    . '<input type="hidden" name="decision" value="received"><button class="abtn secondary xs">Mark received + restock</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div></div>';
    }
    $html .= '</div>'; // left col

    /* customer + delivery */
    $html .= '<div>';
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Customer &amp; delivery</h3></div><div class="adm-card-pad"><dl class="dl-grid">';
    $html .= '<div><dt>Name</dt><dd>' . e($o['customer'] ?: $o['shipping_name']) . '</dd></div>';
    $html .= '<div><dt>Account</dt><dd>' . ($o['user_id'] ? '<a href="' . e(aurl('customers/view/' . (int)$o['user_id'])) . '">' . e($o['customer'] ?: $o['shipping_name']) . '</a>' : '<span class="cell-sub">guest checkout</span>') . '</dd></div>';
    $html .= '<div><dt>Phone</dt><dd>' . e($o['shipping_phone']) . '</dd></div>';
    $html .= '<div><dt>Payment</dt><dd>' . e(str_replace('_', ' ', (string)$o['payment_method'])) . '</dd></div>';
    $html .= '<div><dt>Payment status</dt><dd>' . status_badge((string)($o['payment_status'] ?: 'unpaid')) . '</dd></div>';
    if ($o['coupon_code']) {
        $html .= '<div><dt>Coupon</dt><dd><span class="code-chip">' . e($o['coupon_code']) . '</span> −' . amoney($o['discount_amount']) . '</dd></div>';
    }
    $html .= '<div><dt>District</dt><dd>' . e($o['shipping_area'] ?: '—') . '</dd></div>';
    $html .= '<div style="grid-column:1/-1"><dt>Address</dt><dd>' . e($o['shipping_address']) . '</dd></div>';
    $html .= '<div><dt>Shipped at</dt><dd>' . e(dt($o['shipped_at'])) . '</dd></div>';
    $html .= '<div><dt>Delivered at</dt><dd>' . e(dt($o['delivered_at'])) . '</dd></div>';
    if ($o['failed_reason']) {
        $html .= '<div style="grid-column:1/-1"><dt>Failure / cancel note</dt><dd>' . e($o['failed_reason']) . '</dd></div>';
    }
    if ($o['tracking_code'] && $o['tracking_url']) {
        $url = str_replace('{code}', rawurlencode((string)$o['tracking_code']), (string)$o['tracking_url']);
        $html .= '<div style="grid-column:1/-1"><dt>Track</dt><dd><a href="' . e($url) . '" target="_blank" rel="noopener" class="code-chip">' . e($o['tracking_code']) . '</a></dd></div>';
    }
    $html .= '</dl></div></div>';

    if ($referral) {
        $html .= '<div class="adm-card mt-3"><div class="adm-card-head"><h3>Doctor referral</h3></div><div class="adm-card-pad">'
            . '<p style="font-size:13px;margin:0">Referred via <span class="code-chip">' . e($referral['coupon_code'] ?: '—') . '</span>'
            . ($doctor ? ' — <a href="' . e(aurl('doctors/view/' . (int)$doctor['id'])) . '">' . e($doctor['name']) . '</a>' : '') . '</p>'
            . '<p style="font-size:12.5px;color:var(--a-muted);margin-top:6px">Discount ' . amoney($referral['discount_amount']) . ' · status ' . e($referral['status']) . '</p></div></div>';
    }
    $html .= '</div>';
    $html .= '</div>';

    render_layout('Order ' . ($o['order_number'] ?: '#' . $o['id']), 'orders', $html, true);
}

public static function orders_print(array $o): void
{
    $items = qall('SELECT * FROM order_items WHERE order_id = ?', [(int)$o['id']]);
    $cfg = config('admin');
    ob_start();
    ?>
<!doctype html><html><head><meta charset="utf-8"><title>Invoice <?= e($o['order_number'] ?: '#' . $o['id']) ?></title>
<style>body{font-family:Inter,Arial,sans-serif;background:#f4f6f8;margin:0;padding:24px;color:#111}
.invoice-slip{background:#fff;max-width:800px;margin:0 auto;padding:32px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
h1{font-size:20px;margin:0 0 4px}table{width:100%;border-collapse:collapse;margin-top:18px}
th,td{border-bottom:1px solid #ddd;padding:9px 10px;font-size:13px;text-align:left}
th{background:#f8fafb;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
.num{text-align:right}.muted{color:#666;font-size:12px}.right{text-align:right}
a{color:#16813b}</style></head><body>
<div class="invoice-slip">
  <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div><h1><?= e($cfg['name']) ?></h1><div class="muted">Invoice for order <?= e($o['order_number'] ?: '#' . $o['id']) ?></div></div>
    <div class="right muted">Date: <?= e(d($o['created_at'])) ?><br>Status: <?= e(ucfirst(str_replace('_', ' ', (string)$o['status']))) ?><br>Payment: <?= e(str_replace('_', ' ', (string)$o['payment_method'])) ?> (<?= e((string)($o['payment_status'] ?: 'unpaid')) ?>)</div>
  </div>
  <div style="margin-top:18px;display:flex;gap:40px;flex-wrap:wrap;font-size:13px">
    <div><b>Bill to</b><br><?= e($o['shipping_name']) ?><br><?= e($o['shipping_phone']) ?><br><?= nl2br(e($o['shipping_address'])) ?><?= $o['shipping_area'] ? '<br>District: ' . e($o['shipping_area']) : '' ?></div>
    <div><b>Shipping</b><br><?= e($o['courier_name'] ?: 'To be assigned') ?><br><?= $o['tracking_code'] ? 'Tracking: ' . e($o['tracking_code']) : '' ?></div>
  </div>
  <table><thead><tr><th>Product</th><th class="num">Price</th><th class="num">Qty</th><th class="num">Subtotal</th></tr></thead><tbody>
  <?php foreach ($items as $it): ?>
    <tr><td><?= e($it['product_name']) ?></td><td class="num"><?= amoney($it['price']) ?></td><td class="num"><?= (int)$it['qty'] ?></td><td class="num"><?= amoney((float)$it['price'] * (int)$it['qty']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <div class="right" style="margin-top:16px;font-size:14px">
    <p>Subtotal: <b><?= amoney($o['subtotal']) ?></b></p>
    <?php if ((float)($o['discount_amount'] ?? 0) > 0): ?>
      <p>Discount (<?= e($o['coupon_code'] ?: 'coupon') ?>): <b>−<?= amoney($o['discount_amount']) ?></b></p>
    <?php endif; ?>
    <p>Shipping: <b><?= amoney($o['shipping']) ?></b></p>
    <?php if ((float)($o['cod_fee'] ?? 0) > 0): ?>
      <p>COD fee: <b><?= amoney($o['cod_fee']) ?></b></p>
    <?php endif; ?>
    <p style="font-size:17px">Total: <b><?= amoney($o['total']) ?></b></p>
  </div>
  <p class="muted" style="margin-top:26px">Thank you for your order. This invoice was generated by the <?= e($cfg['name']) ?> admin CMS.</p>
</div>
<script>window.print && setTimeout(function(){window.print()},400);</script>
</body></html>
    <?php
    throw new \Illuminate\Http\Exceptions\HttpResponseException(response(ob_get_clean()));
}

public static function orders_update(int $id): void
{
    verify_csrf_post();
    $o = self::orders_find($id);
    $status = post('status');
    if (!in_array($status, self::ORDER_STATUSES, true)) {
        flash('error', 'Invalid order status.');
        aredirect('orders/view/' . $id);
    }
    $current = (string)$o['status'];
    if ($status !== $current && !in_array($status, self::ORDER_TRANSITIONS[$current] ?? [], true)) {
        flash('error', 'Cannot move order from ' . ucfirst(str_replace('_', ' ', $current)) . ' to ' . ucfirst(str_replace('_', ' ', $status)) . '.');
        aredirect('orders/view/' . $id);
    }

    /* prescription gate — block shipping any Rx order without an approved prescription */
    if ($status === 'shipped' && (int)$o['requires_prescription'] === 1) {
        $rxOk = (int)qval("SELECT COUNT(*) FROM prescriptions WHERE order_id = ? AND status = 'approved'", [$id]);
        if (!$rxOk) {
            flash('error', 'Cannot ship: this order requires an approved prescription. Record or approve a prescription first.');
            aredirect('orders/view/' . $id);
        }
    }

    $courierId = (int)(request()->post('courier_id') ?? 0) ?: null;
    if ($courierId && !(int)qval('SELECT id FROM courier_methods WHERE id = ?', [$courierId])) {
        flash('error', 'Selected courier does not exist.');
        aredirect('orders/view/' . $id);
    }
    $tracking = post('tracking_code') ?: null;
    $notes = post('admin_notes') ?: null;
    $payStatus = post('payment_status');
    if (!in_array($payStatus, self::PAYMENT_STATUSES, true)) { $payStatus = (string)($o['payment_status'] ?: 'unpaid'); }
    $expectedPaymentStatus = (string)($o['payment_status'] ?: 'unpaid');
    /* COD orders are marked paid automatically once delivered */
    if ($status === 'delivered' && $payStatus === 'unpaid') {
        $payStatus = 'paid';
    }
    if ($payStatus === 'refunded' && ! in_array($status, ['cancelled', 'returned'], true)) {
        flash_redirect('error', 'A refund can only be recorded on a cancelled or returned order.', 'orders/view/' . $id);
    }
    if ($payStatus === 'refunded' && trim((string) $notes) === '') {
        flash_redirect('error', 'Enter the external refund confirmation reference in admin notes before recording the refund.', 'orders/view/' . $id);
    }
    if ((in_array($status, ['cancelled', 'returned'], true) || $payStatus === 'refunded')
        && (current_user()['role'] ?? '') !== 'admin') {
        flash_redirect('error', 'Only an administrator can cancel or refund an order.', 'orders/view/' . $id);
    }

    $shipped = $o['shipped_at'];
    $delivered = $o['delivered_at'];
    if ($status === 'shipped' && !$shipped) { $shipped = date('Y-m-d H:i:s'); }
    if ($status === 'delivered') {
        if (!$shipped) { $shipped = date('Y-m-d H:i:s'); }
        $delivered = $delivered ?: date('Y-m-d H:i:s');
    }

    $savedPaymentStatus = \Illuminate\Support\Facades\DB::transaction(function () use (
        $status, $courierId, $tracking, $notes, $shipped, $delivered, $payStatus, $id, $current, $expectedPaymentStatus
    ): ?string {
        $lockedOrder = \Illuminate\Support\Facades\DB::table('orders')->where('id', $id)
            ->lockForUpdate()->first(['status', 'payment_status', 'failed_reason']);
        if (! $lockedOrder
            || (string) $lockedOrder->status !== $current
            || (string) $lockedOrder->payment_status !== $expectedPaymentStatus) {
            return null;
        }

        $effectivePaymentStatus = $payStatus;
        if ($lockedOrder->payment_status === 'paid' && ! in_array($payStatus, ['paid', 'refunded'], true)) {
            $effectivePaymentStatus = 'paid';
        } elseif ($lockedOrder->payment_status === 'refunded') {
            $effectivePaymentStatus = 'refunded';
        } elseif ($lockedOrder->payment_status === 'pending' && $payStatus === 'unpaid') {
            $effectivePaymentStatus = 'pending';
        }

        q('UPDATE orders SET status=?, courier_id=?, tracking_code=?, admin_notes=?, shipped_at=?, delivered_at=?, payment_status=? WHERE id=?',
            [$status, $courierId, $tracking, $notes, $shipped, $delivered, $effectivePaymentStatus, $id]);

        if ($status === 'processing') {
            \App\Services\OrderService::confirmSellerOrderItems($id);
        }

        if ($status === 'cancelled' && $current !== 'cancelled') {
            if ($effectivePaymentStatus === 'paid') {
                $reason = trim((string) $lockedOrder->failed_reason);
                if (! str_contains(strtolower($reason), 'refund review is required')) {
                    $reason = trim($reason.' Order was cancelled while payment is recorded as paid; administrator refund review is required.');
                    q('UPDATE orders SET failed_reason=? WHERE id=?', [$reason, $id]);
                }
            }
            if (! \App\Services\OrderService::releaseStock($id, 'order_cancelled')) {
                throw new RuntimeException('Order stock could not be released.');
            }
        }
        if ($status === 'returned' && $current !== 'returned') {
            if (! \App\Services\OrderService::releaseStock($id, 'order_returned')) {
                throw new RuntimeException('Returned order stock could not be released.');
            }
        }

        return $effectivePaymentStatus;
    });
    if ($savedPaymentStatus === null) {
        flash_redirect('error', 'The order changed while this update was being processed. Refresh and retry.', 'orders/view/' . $id);
    }
    $payStatus = $savedPaymentStatus;

    // keep referral status in sync with delivery outcome
    if ($status === 'delivered') {
        q("UPDATE doctor_referrals SET status='credited' WHERE order_id = ? AND status='pending'", [$id]);
    } elseif ($status === 'cancelled') {
        q("UPDATE doctor_referrals SET status='cancelled' WHERE order_id = ? AND status='pending'", [$id]);
    }

    log_action('order.update', 'order', $id, ($o['order_number'] ?: '#' . $id) . ' → ' . $status . ' (payment: ' . $payStatus . ')');
    flash_redirect('success', 'Order ' . ($o['order_number'] ?: '#' . $id) . ' updated to ' . str_replace('_', ' ', $status) . '.', 'orders/view/' . $id);
}

/** Handle return request decisions: approve / reject / receive+restock. */
public static function orders_return(int $id): void
{
    verify_csrf_post();
    $o = self::orders_find($id);
    $decision = post('decision');
    $ret = qrow("SELECT * FROM order_returns WHERE order_id = ? AND status IN ('requested','approved') ORDER BY created_at DESC LIMIT 1", [$id]);
    if (!$ret) {
        flash('error', 'No open return request for this order.');
        aredirect('orders/view/' . $id);
    }
    $retId = (int)$ret['id'];

    if ($decision === 'approved') {
        q("UPDATE order_returns SET status='approved', admin_notes=CONCAT(COALESCE(admin_notes,''), '') WHERE id=?", [$retId]);
        /* keep order in return_requested until goods come back */
        if ((string)$o['status'] === 'delivered') {
            q("UPDATE orders SET status='return_requested' WHERE id=?", [$id]);
        }
        log_action('order.return.approve', 'order', $id, 'Return #' . $retId . ' approved');
        flash_redirect('success', 'Return approved. Mark as received to restock items.', 'orders/view/' . $id);
    } elseif ($decision === 'rejected') {
        q("UPDATE order_returns SET status='rejected' WHERE id=?", [$retId]);
        /* reject returns the order to delivered */
        if ((string)$o['status'] === 'return_requested') {
            q("UPDATE orders SET status='delivered' WHERE id=?", [$id]);
        }
        log_action('order.return.reject', 'order', $id, 'Return #' . $retId . ' rejected');
        flash_redirect('success', 'Return rejected — order stays delivered.', 'orders/view/' . $id);
    } elseif ($decision === 'received') {
        if ((current_user()['role'] ?? '') !== 'admin') {
            flash_redirect('error', 'Only an administrator can receive returned goods.', 'orders/view/' . $id);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($id, $retId): void {
            q("UPDATE order_returns SET status='received' WHERE id=?", [$retId]);
            if (! \App\Services\OrderService::releaseStock($id, 'order_return')) {
                throw new RuntimeException('Returned order stock could not be released.');
            }
            q("UPDATE orders SET status='returned' WHERE id=?", [$id]);
        });
        log_action('order.return.receive', 'order', $id, 'Return #' . $retId . ' received — stock restored; external refund confirmation required');
        flash_redirect('success', 'Return received and stock restored. Record the refund after external payment is confirmed.', 'orders/view/' . $id);
    } else {
        flash('error', 'Unknown return decision.');
        aredirect('orders/view/' . $id);
    }
}
}
