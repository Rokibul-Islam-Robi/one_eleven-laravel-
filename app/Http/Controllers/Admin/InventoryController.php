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
 * Inventory & Expiry — stock overview, low-stock alerts, batch tracking with expiry dates.
 */
final class InventoryController extends Controller
{


public static function mod_inventory(string $action, int $id): void
{
    $tab = getq('tab', 'stock');
    switch ($action) {
        case 'batch_new':
            require_module('inventory', 'edit');
            self::inventory_batch_form();
            break;
        case 'batch_create':
            require_module('inventory', 'edit');
            self::inventory_batch_save();
            break;
        case 'batch_edit':
            require_module('inventory', 'edit');
            self::inventory_batch_form(self::inventory_batch_find($id));
            break;
        case 'batch_update':
            require_module('inventory', 'edit');
            self::inventory_batch_save(self::inventory_batch_find($id));
            break;
        case 'batch_delete':
            require_module('inventory', 'delete');
            self::inventory_batch_delete($id);
            break;
        case 'threshold':
            require_module('inventory', 'edit');
            self::inventory_threshold($id);
            break;
        default:
            if ($tab === 'expiry') { self::inventory_expiry_tab(); }
            elseif ($tab === 'batches') { self::inventory_batches_tab(); }
            else { self::inventory_stock_tab(); }
    }
}

public static function inventory_batch_find(int $id): array
{
    $row = qrow('SELECT b.*, p.name AS product_name FROM inventory_batches b JOIN products p ON p.id = b.product_id WHERE b.id = ?', [$id]);
    if (!$row) {
        adm_status(404);
        render_layout('Batch not found', 'inventory', '<div class="adm-card adm-card-pad adm-empty"><h3>Batch not found</h3><a class="abtn primary mt-3" href="' . e(aurl('inventory', ['tab' => 'batches'])) . '">Back</a></div>');
    }
    return $row;
}

public static function inventory_tabs(string $current): string
{
    $counts = [
        'stock' => (int)qval('SELECT COUNT(*) FROM products'),
        'batches' => (int)qval('SELECT COUNT(*) FROM inventory_batches'),
        'expiry' => (int)qval("SELECT COUNT(*) FROM inventory_batches WHERE qty_remaining > 0 AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"),
    ];
    $tabs = [
        'stock' => ['Stock overview', aurl('inventory'), $counts['stock']],
        'batches' => ['Batches', aurl('inventory', ['tab' => 'batches']), $counts['batches']],
        'expiry' => ['Expiry alerts', aurl('inventory', ['tab' => 'expiry']), $counts['expiry']],
    ];
    $html = '<div class="adm-tabs">';
    foreach ($tabs as $key => [$label, $url, $cnt]) {
        $html .= '<a href="' . e($url) . '" class="' . ($key === $current ? 'on' : '') . '">' . e($label) . ' <span class="cnt">' . (int)$cnt . '</span></a>';
    }
    return $html . '</div>';
}

public static function inventory_stock_tab(): void
{
    $q = getq('q');
    $filter = getq('stock');
    $page = pint(getq('page', '1'));

    $where = '1=1';
    $params = [];
    if ($q !== '') { $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)'; $params[] = like($q); $params[] = like($q); }
    if ($filter === 'low') { $where .= ' AND p.stock > 0 AND p.stock <= p.low_stock_threshold'; }
    elseif ($filter === 'out') { $where .= ' AND p.stock = 0'; }

    $total = (int)qval("SELECT COUNT(*) FROM products p WHERE $where", $params);
    [$limitSql, , $pager] = paginate($total, $page, 40);
    $rows = qall(
        "SELECT p.*, c.name AS category_name,
                (SELECT COALESCE(SUM(b.qty_remaining),0) FROM inventory_batches b WHERE b.product_id = p.id AND (b.expiry_date IS NULL OR b.expiry_date >= CURDATE())) AS batch_stock,
                (SELECT MIN(b.expiry_date) FROM inventory_batches b WHERE b.product_id = p.id AND b.qty_remaining > 0 AND b.expiry_date IS NOT NULL) AS next_expiry
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE $where ORDER BY p.stock ASC, p.name $limitSql",
        $params
    );

    $html = page_head('Inventory & Expiry', 'Stock levels, reorder thresholds and batch expiry tracking.');
    $html .= self::inventory_tabs('stock');
    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([
        filter_select('stock', 'Stock', ['' => 'All', 'low' => 'Low stock', 'out' => 'Out of stock'], $filter),
    ], 'Search product name or SKU…', $q);
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('inventory') . '</div><h3>No products found</h3></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Product</th><th>Category</th><th class="num">Stock</th><th class="num">Batch stock</th><th>Next expiry</th><th>Threshold</th></tr></thead><tbody>';
        foreach ($rows as $p) {
            $cls = $p['stock'] === 0 ? 'low' : ($p['stock'] <= $p['low_stock_threshold'] ? 'mid' : '');
            $exp = (string)($p['next_expiry'] ?? '');
            $expHtml = '—';
            if ($exp !== '') {
                $isExpired = $exp < date('Y-m-d');
                $soon = !$isExpired && strtotime($exp) <= strtotime('+30 days');
                $expHtml = '<span class="' . ($isExpired ? 'exp-expired' : ($soon ? 'exp-soon' : 'exp-ok')) . '">' . e(d($exp)) . '</span>';
            }
            $html .= '<tr><td><span class="cell-main">' . e($p['name']) . '</span><div class="cell-sub">' . e($p['sku'] ?: '—') . '</div></td>'
                . '<td>' . e($p['category_name'] ?: '—') . '</td>'
                . '<td class="num"><b>' . (int)$p['stock'] . '</b><span class="stock-bar ' . $cls . '"><i style="width:' . min(100, (int)$p['stock']) . '%"></i></span></td>'
                . '<td class="num">' . (int)$p['batch_stock'] . '</td>'
                . '<td>' . $expHtml . '</td>'
                . '<td>' . (can('inventory', 'edit')
                    ? '<form method="post" action="' . e(aurl('inventory/threshold/' . (int)$p['id'])) . '" class="d-flex gap-1 align-items-center">' . csrf_field()
                    . '<input class="ainput" style="width:74px;height:30px" type="number" min="0" name="low_stock_threshold" value="' . (int)$p['low_stock_threshold'] . '">'
                    . '<button class="abtn secondary xs">Set</button></form>'
                    : (int)$p['low_stock_threshold']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, array_filter(['q' => $q, 'stock' => $filter]));
    }
    $html .= '</div>';
    render_layout('Inventory', 'inventory', $html);
}

public static function inventory_batches_tab(): void
{
    $q = getq('q');
    $page = pint(getq('page', '1'));
    $where = '1=1';
    $params = [];
    if ($q !== '') { $where = '(b.batch_number LIKE ? OR p.name LIKE ? OR b.supplier LIKE ?)'; $params = [like($q), like($q), like($q)]; }

    $total = (int)qval("SELECT COUNT(*) FROM inventory_batches b JOIN products p ON p.id = b.product_id WHERE $where", $params);
    [$limitSql, , $pager] = paginate($total, $page, 30);
    $rows = qall(
        "SELECT b.*, p.name AS product_name FROM inventory_batches b JOIN products p ON p.id = b.product_id
         WHERE $where ORDER BY b.expiry_date IS NULL, b.expiry_date ASC $limitSql",
        $params
    );

    $actions = can('inventory', 'edit')
        ? '<a class="abtn primary" href="' . e(aurl('inventory/batch_new')) . '">' . icon('plus') . ' Add batch</a>' : '';

    $html = page_head('Inventory & Expiry', 'Procurement batches with cost, remaining quantity and expiry dates.', $actions);
    $html .= self::inventory_tabs('batches');
    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([], 'Search batch no, product or supplier…', $q);
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('inventory') . '</div><h3>No batches yet</h3><p>Record incoming stock batches to track expiry per lot.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Batch</th><th>Product</th><th class="num">Received</th><th class="num">Remaining</th><th class="num">Cost</th><th>Expiry</th><th>Supplier</th><th></th></tr></thead><tbody>';
        foreach ($rows as $b) {
            $exp = (string)($b['expiry_date'] ?? '');
            $expHtml = '<span class="cell-sub">—</span>';
            if ($exp !== '') {
                $isExpired = $exp < date('Y-m-d');
                $soon = !$isExpired && strtotime($exp) <= strtotime('+30 days');
                $expHtml = '<span class="' . ($isExpired ? 'exp-expired' : ($soon ? 'exp-soon' : 'exp-ok')) . '">' . e(d($exp)) . ($isExpired ? ' · expired' : ($soon ? ' · soon' : '')) . '</span>';
            }
            $html .= '<tr><td><span class="code-chip">' . e($b['batch_number']) . '</span></td>'
                . '<td>' . e($b['product_name']) . '</td>'
                . '<td class="num">' . (int)$b['qty_received'] . '</td>'
                . '<td class="num"><b>' . (int)$b['qty_remaining'] . '</b></td>'
                . '<td class="num">' . amoney($b['cost_price']) . '</td>'
                . '<td>' . $expHtml . '</td>'
                . '<td>' . e($b['supplier'] ?: '—') . '</td>'
                . '<td class="actions-cell">';
            if (can('inventory', 'edit')) {
                $html .= '<a class="abtn secondary xs" href="' . e(aurl('inventory/batch_edit/' . (int)$b['id'])) . '">Edit</a>';
            }
            if (can('inventory', 'delete')) {
                $html .= '<form method="post" action="' . e(aurl('inventory/batch_delete/' . (int)$b['id'])) . '" class="d-inline"' . confirm_delete_js() . '>' . csrf_field()
                    . '<button class="abtn danger-soft xs">Delete</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, ['q' => $q]);
    }
    $html .= '</div>';
    render_layout('Inventory batches', 'inventory', $html);
}

public static function inventory_expiry_tab(): void
{
    $filter = getq('exp', 'alerts');
    if ($filter === 'all') {
        $rows = qall("SELECT b.*, p.name AS product_name FROM inventory_batches b JOIN products p ON p.id = b.product_id WHERE b.qty_remaining > 0 AND b.expiry_date IS NOT NULL ORDER BY b.expiry_date ASC LIMIT 200");
    } else {
        $rows = qall("SELECT b.*, p.name AS product_name FROM inventory_batches b JOIN products p ON p.id = b.product_id WHERE b.qty_remaining > 0 AND b.expiry_date IS NOT NULL AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY b.expiry_date ASC LIMIT 200");
    }
    $expiredCount = (int)qval("SELECT COUNT(*) FROM inventory_batches WHERE qty_remaining > 0 AND expiry_date < CURDATE()");
    $soonCount = (int)qval("SELECT COUNT(*) FROM inventory_batches WHERE qty_remaining > 0 AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");

    $html = page_head('Inventory & Expiry', 'Batches that are expired or expiring within 30 days.');
    $html .= self::inventory_tabs('expiry');
    $html .= '<div class="adm-metrics">'
        . metric_card('Already expired', (string)$expiredCount, 'alert', 'red')
        . metric_card('Expiring ≤ 30 days', (string)$soonCount, 'calendar', 'amber')
        . metric_card('Active batches', (string)qval("SELECT COUNT(*) FROM inventory_batches WHERE qty_remaining > 0"), 'inventory', 'green')
        . '</div>';
    $html .= '<div class="adm-tabs" style="margin-top:4px">'
        . '<a href="' . e(aurl('inventory', ['tab' => 'expiry', 'exp' => 'alerts'])) . '" class="' . ($filter !== 'all' ? 'on' : '') . '">Alerts only</a>'
        . '<a href="' . e(aurl('inventory', ['tab' => 'expiry', 'exp' => 'all'])) . '" class="' . ($filter === 'all' ? 'on' : '') . '">All dated batches</a></div>';

    $html .= '<div class="adm-card">';
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('check') . '</div><h3>No expiry alerts</h3><p>Nothing is expired or expiring in the next 30 days.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Product</th><th>Batch</th><th class="num">Remaining</th><th>Expiry</th><th>Status</th><th>Supplier</th></tr></thead><tbody>';
        foreach ($rows as $b) {
            $exp = (string)$b['expiry_date'];
            $isExpired = $exp < date('Y-m-d');
            $soon = !$isExpired && strtotime($exp) <= strtotime('+30 days');
            $html .= '<tr><td><span class="cell-main">' . e($b['product_name']) . '</span></td>'
                . '<td><span class="code-chip">' . e($b['batch_number']) . '</span></td>'
                . '<td class="num">' . (int)$b['qty_remaining'] . '</td>'
                . '<td>' . e(d($exp)) . '</td>'
                . '<td>' . ($isExpired ? status_badge('expired') : ($soon ? status_badge('expiring') : status_badge('ok'))) . '</td>'
                . '<td>' . e($b['supplier'] ?: '—') . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div>';
    render_layout('Expiry alerts', 'inventory', $html);
}

public static function inventory_batch_form(?array $b = null, array $errors = [], array $old = []): void
{
    $isEdit = $b !== null;
    $v = static function (string $k, string $d = '') use ($b, $old) {
        if ($old) { return (string)($old[$k] ?? $d); }
        return (string)($b[$k] ?? $d);
    };
    $products = qall('SELECT id, name, sku FROM products ORDER BY name');
    $err = static fn(string $k): string => isset($errors[$k]) ? ' err' : '';
    $errmsg = static fn(string $k): string => isset($errors[$k]) ? '<div class="aerr">' . e($errors[$k]) . '</div>' : '';

    $html = page_head($isEdit ? 'Edit batch' : 'Add stock batch', $isEdit ? $b['product_name'] . ' · ' . $b['batch_number'] : 'Record incoming inventory with expiry tracking.',
        '<a class="abtn ghost" href="' . e(aurl('inventory', ['tab' => 'batches'])) . '">← Back</a>');
    $html .= '<div class="adm-card" style="max-width:680px"><div class="adm-card-pad"><form method="post" action="' . e(aurl('inventory/' . ($isEdit ? 'batch_update' : 'batch_create'))) . '" novalidate>' . csrf_field();
    if ($isEdit) { $html .= '<input type="hidden" name="id" value="' . (int)$b['id'] . '">'; }
    $html .= '<div class="afield"><label>Product *</label><select class="aselect' . $err('product_id') . '" name="product_id" required><option value="">— select —</option>';
    foreach ($products as $p) {
        $html .= '<option value="' . (int)$p['id'] . '"' . ($v('product_id') === (string)$p['id'] ? ' selected' : '') . '>' . e($p['name']) . ($p['sku'] ? ' (' . e($p['sku']) . ')' : '') . '</option>';
    }
    $html .= '</select>' . $errmsg('product_id') . '</div>';
    $html .= '<div class="agrid-2"><div class="afield"><label>Batch number *</label><input class="ainput' . $err('batch_number') . '" name="batch_number" value="' . e($v('batch_number')) . '" required>' . $errmsg('batch_number') . '</div>';
    $html .= '<div class="afield"><label>Supplier</label><input class="ainput" name="supplier" value="' . e($v('supplier')) . '"></div></div>';
    $html .= '<div class="agrid-3"><div class="afield"><label>Qty received *</label><input class="ainput' . $err('qty_received') . '" type="number" min="0" name="qty_received" value="' . e($v('qty_received', '0')) . '" required>' . $errmsg('qty_received') . '</div>';
    $html .= '<div class="afield"><label>Qty remaining *</label><input class="ainput' . $err('qty_remaining') . '" type="number" min="0" name="qty_remaining" value="' . e($v('qty_remaining', '0')) . '" required>' . $errmsg('qty_remaining') . '</div>';
    $html .= '<div class="afield"><label>Unit cost</label><input class="ainput' . $err('cost_price') . '" type="number" step="0.01" min="0" name="cost_price" value="' . e($v('cost_price', '0')) . '"></div></div>';
    $html .= '<div class="agrid-2"><div class="afield"><label>Expiry date</label><input class="ainput' . $err('expiry_date') . '" type="date" name="expiry_date" value="' . e($v('expiry_date')) . '">' . $errmsg('expiry_date') . '</div>';
    $html .= '<div class="afield"><label>Received date</label><input class="ainput' . $err('received_at') . '" type="date" name="received_at" value="' . e($v('received_at', date('Y-m-d'))) . '"></div></div>';
    $html .= '<div class="afield"><label>Notes</label><input class="ainput" name="notes" value="' . e($v('notes')) . '"></div>';
    $html .= '<div class="form-actions"><button class="abtn primary">' . ($isEdit ? 'Save batch' : 'Add batch') . '</button></div></form></div></div>';
    render_layout($isEdit ? 'Edit batch' : 'Add batch', 'inventory', $html);
}

public static function inventory_batch_save(?array $existing = null): void
{
    verify_csrf_post();
    $v = new Validator(request()->post());
    $v->required('product_id', 'Product')
      ->required('batch_number', 'Batch number')
      ->max('batch_number', 80, 'Batch number')
      ->required('qty_received', 'Qty received')
      ->numeric('qty_received', 'Qty received', false, 0)
      ->numeric('qty_remaining', 'Qty remaining', true, 0)
      ->numeric('cost_price', 'Cost price', true, 0)
      ->date('expiry_date', 'Expiry date')
      ->date('received_at', 'Received date')
      ->max('supplier', 120, 'Supplier');

    if (!(int)qval('SELECT id FROM products WHERE id = ?', [$v->int('product_id')])) {
        $v->errors['product_id'] = 'Selected product does not exist.';
    }
    $rem = $v->int('qty_remaining', 0);
    $recv = $v->int('qty_received', 0);
    if ($rem > $recv && !isset($v->errors['qty_remaining'])) {
        $v->errors['qty_remaining'] = 'Remaining quantity cannot exceed quantity received.';
    }

    if ($v->fails()) {
        flash('error', 'Please correct the highlighted fields.');
        self::inventory_batch_form($existing, $v->errors, request()->post());
        return;
    }

    $data = [
        'product_id' => $v->int('product_id'),
        'batch_number' => $v->str('batch_number'),
        'qty_received' => $recv,
        'qty_remaining' => $rem,
        'cost_price' => number_format($v->float('cost_price', 0), 2, '.', ''),
        'expiry_date' => $v->str('expiry_date') ?: null,
        'supplier' => $v->str('supplier') ?: null,
        'received_at' => $v->str('received_at') ?: null,
        'notes' => $v->str('notes') ?: null,
    ];

    if ($existing) {
        update_row('inventory_batches', $data, (int)$existing['id']);
        log_action('batch.update', 'batch', (int)$existing['id'], $data['batch_number']);
        flash_redirect('success', 'Batch ' . $data['batch_number'] . ' updated.', 'inventory', ['tab' => 'batches']);
    }
    $id = insert_row('inventory_batches', $data);
    log_action('batch.create', 'batch', $id, $data['batch_number']);
    flash_redirect('success', 'Batch ' . $data['batch_number'] . ' added.', 'inventory', ['tab' => 'batches']);
}

public static function inventory_batch_delete(int $id): void
{
    verify_csrf_post();
    $b = self::inventory_batch_find($id);
    q('DELETE FROM inventory_batches WHERE id = ?', [$id]);
    log_action('batch.delete', 'batch', $id, $b['batch_number']);
    flash_redirect('success', 'Batch ' . $b['batch_number'] . ' deleted.', 'inventory', ['tab' => 'batches']);
}

public static function inventory_threshold(int $id): void
{
    verify_csrf_post();
    $p = qrow('SELECT * FROM products WHERE id = ?', [$id]);
    if (!$p) { adm_die(404, 'Product not found'); }
    $t = max(0, (int)(request()->post('low_stock_threshold') ?? 0));
    q('UPDATE products SET low_stock_threshold = ?, updated_at = NOW() WHERE id = ?', [$t, $id]);
    log_action('inventory.threshold', 'product', $id, $p['name'] . ' → ' . $t);
    flash_redirect('success', 'Low-stock threshold for “' . $p['name'] . '” set to ' . $t . '.', 'inventory');
}
}
