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
 * Courier Settings — delivery methods, fees, COD charges, free-delivery thresholds.
 */
final class CourierController extends Controller
{


public static function mod_courier(string $action, int $id): void
{
    switch ($action) {
        case 'new':
            self::courier_form(null);
            break;
        case 'create':
            self::courier_save(null);
            break;
        case 'edit':
            self::courier_form(self::courier_find($id));
            break;
        case 'update':
            self::courier_save(self::courier_find($id));
            break;
        case 'delete':
            self::courier_delete($id);
            break;
        case 'toggle':
            self::courier_toggle($id);
            break;
        case 'default':
            self::courier_default($id);
            break;
        default:
            self::courier_list();
    }
}

public static function courier_find(int $id): array
{
    $row = qrow('SELECT * FROM courier_methods WHERE id = ?', [$id]);
    if (!$row) {
        adm_status(404);
        render_layout('Courier not found', 'courier', '<div class="adm-card adm-card-pad adm-empty"><h3>Courier not found</h3><a class="abtn primary mt-3" href="' . e(aurl('courier')) . '">Back</a></div>');
    }
    return $row;
}

public static function courier_list(): void
{
    $rows = qall(
        "SELECT cm.*, (SELECT COUNT(*) FROM orders o WHERE o.courier_id = cm.id) AS order_count
         FROM courier_methods cm ORDER BY cm.sort_order, cm.name"
    );

    $actions = can('courier', 'edit')
        ? '<a class="abtn primary" href="' . e(aurl('courier/new')) . '">' . icon('plus') . ' Add courier</a>' : '';

    $html = page_head('Courier Settings', 'Delivery partners, fees, COD charges and free-delivery thresholds used at checkout.');
    $html .= '<div class="adm-card">';
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('courier') . '</div><h3>No couriers configured</h3><p>Add a delivery partner to start assigning shipments.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Courier</th><th class="num">Fee</th><th class="num">COD fee</th><th class="num">Free above</th><th>Est. delivery</th><th class="num">Orders</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $c) {
            $html .= '<tr><td><span class="cell-main">' . e($c['name']) . '</span> <span class="code-chip">' . e($c['code']) . '</span>'
                . ($c['is_default'] ? ' <span class="abadge green">Default</span>' : '')
                . ($c['contact_phone'] ? '<div class="cell-sub">' . e($c['contact_phone']) . '</div>' : '') . '</td>'
                . '<td class="num">' . amoney($c['fee']) . '</td>'
                . '<td class="num">' . amoney($c['cod_fee']) . '</td>'
                . '<td class="num">' . ((float)$c['free_delivery_min'] > 0 ? amoney($c['free_delivery_min']) : '—') . '</td>'
                . '<td>' . e($c['estimated_days'] ?: '—') . '</td>'
                . '<td class="num">' . (int)$c['order_count'] . '</td>'
                . '<td>' . status_badge((string)$c['status']) . '</td>'
                . '<td class="actions-cell">';
            if (can('courier', 'edit')) {
                $html .= '<a class="abtn secondary xs" href="' . e(aurl('courier/edit/' . (int)$c['id'])) . '">Edit</a>';
                if (!$c['is_default']) {
                    $html .= '<form method="post" action="' . e(aurl('courier/default/' . (int)$c['id'])) . '" class="d-inline">' . csrf_field()
                        . '<button class="abtn secondary xs">Make default</button></form>';
                }
            }
            if (can('courier', 'delete')) {
                $html .= '<form method="post" action="' . e(aurl('courier/delete/' . (int)$c['id'])) . '" class="d-inline"' . confirm_delete_js() . '>' . csrf_field()
                    . '<button class="abtn danger-soft xs">Delete</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div>';
    render_layout('Courier Settings', 'courier', $html);
}

public static function courier_form(?array $c, array $errors = [], array $old = []): void
{
    $isEdit = $c !== null;
    $v = static function (string $k, string $d = '') use ($c, $old) {
        if ($old) { return (string)($old[$k] ?? $d); }
        return (string)($c[$k] ?? $d);
    };
    $err = static fn(string $k): string => isset($errors[$k]) ? ' err' : '';
    $errmsg = static fn(string $k): string => isset($errors[$k]) ? '<div class="aerr">' . e($errors[$k]) . '</div>' : '';

    $html = page_head($isEdit ? 'Edit courier' : 'Add courier', 'These values feed checkout delivery options.',
        '<a class="abtn ghost" href="' . e(aurl('courier')) . '">← Back</a>');
    $html .= '<div class="adm-card" style="max-width:680px"><div class="adm-card-pad"><form method="post" action="' . e(aurl('courier/' . ($isEdit ? 'update' : 'create'))) . '" novalidate>' . csrf_field();
    if ($isEdit) { $html .= '<input type="hidden" name="id" value="' . (int)$c['id'] . '">'; }
    $html .= '<div class="agrid-2"><div class="afield"><label>Name *</label><input class="ainput' . $err('name') . '" name="name" value="' . e($v('name')) . '" required>' . $errmsg('name') . '</div>';
    $html .= '<div class="afield"><label>Code * <span class="hint">(unique, lowercase)</span></label><input class="ainput' . $err('code') . '" name="code" value="' . e($v('code')) . '" placeholder="redx">' . $errmsg('code') . '</div></div>';
    $html .= '<div class="agrid-3"><div class="afield"><label>Delivery fee *</label><input class="ainput' . $err('fee') . '" type="number" step="0.01" min="0" name="fee" value="' . e($v('fee', '0')) . '">' . $errmsg('fee') . '</div>';
    $html .= '<div class="afield"><label>COD fee</label><input class="ainput' . $err('cod_fee') . '" type="number" step="0.01" min="0" name="cod_fee" value="' . e($v('cod_fee', '0')) . '"></div>';
    $html .= '<div class="afield"><label>Free delivery above</label><input class="ainput' . $err('free_delivery_min') . '" type="number" step="0.01" min="0" name="free_delivery_min" value="' . e($v('free_delivery_min', '0')) . '"></div></div>';
    $html .= '<div class="agrid-3"><div class="afield"><label>Estimated delivery</label><input class="ainput" name="estimated_days" value="' . e($v('estimated_days')) . '" placeholder="2–3 days"></div>';
    $html .= '<div class="afield"><label>Contact phone</label><input class="ainput' . $err('contact_phone') . '" name="contact_phone" value="' . e($v('contact_phone')) . '">' . $errmsg('contact_phone') . '</div>';
    $html .= '<div class="afield"><label>Sort order</label><input class="ainput" type="number" name="sort_order" value="' . e($v('sort_order', '0')) . '"></div></div>';
    $html .= '<div class="afield"><label>Tracking URL <span class="hint">({code} is replaced by the tracking number)</span></label><input class="ainput' . $err('tracking_url') . '" name="tracking_url" value="' . e($v('tracking_url')) . '" placeholder="https://example.com/track?track={code}">' . $errmsg('tracking_url') . '</div>';
    $activeChecked = $old ? !empty($old['status']) : ($isEdit ? $c['status'] === 'active' : true);
    $html .= '<div class="afield"><label class="d-flex align-items-center gap-2"><input type="checkbox" class="adm-check" name="status" value="active"' . ($activeChecked ? ' checked' : '') . '> Active</label></div>';
    $defaultChecked = $old ? !empty($old['is_default']) : !empty($c['is_default']);
    $html .= '<div class="afield"><label class="d-flex align-items-center gap-2"><input type="checkbox" class="adm-check" name="is_default" value="1"' . ($defaultChecked ? ' checked' : '') . '> Default courier</label></div>';
    $html .= '<div class="form-actions"><button class="abtn primary">' . ($isEdit ? 'Save courier' : 'Add courier') . '</button></div></form></div></div>';
    render_layout($isEdit ? 'Edit courier' : 'Add courier', 'courier', $html);
}

public static function checked_attr(array $c): string
{
    return !empty($c['is_default']) ? ' checked' : '';
}

public static function courier_save(?array $existing): void
{
    verify_csrf_post();
    $v = new Validator(request()->post());
    $v->required('name', 'Name')->max('name', 100, 'Name')
      ->required('code', 'Code')->max('code', 50, 'Code')
      ->numeric('fee', 'Delivery fee', false, 0)
      ->numeric('cod_fee', 'COD fee', true, 0)
      ->numeric('free_delivery_min', 'Free delivery threshold', true, 0)
      ->max('estimated_days', 40, 'Estimated delivery')
      ->max('contact_phone', 40, 'Contact phone')
      ->max('tracking_url', 255, 'Tracking URL');

    $code = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $v->str('code')));
    $dup = (int)qval('SELECT id FROM courier_methods WHERE code = ? AND id <> ?', [$code, (int)($existing['id'] ?? 0)]);
    if ($dup) { $v->errors['code'] = 'This courier code already exists.'; }

    if ($v->fails()) {
        flash('error', 'Please correct the highlighted fields.');
        self::courier_form($existing, $v->errors, request()->post());
        return;
    }

    $isDefault = !empty(request()->post('is_default')) ? 1 : 0;
    $data = [
        'name' => $v->str('name'),
        'code' => $code,
        'fee' => number_format($v->float('fee', 0), 2, '.', ''),
        'cod_fee' => number_format($v->float('cod_fee', 0), 2, '.', ''),
        'free_delivery_min' => number_format($v->float('free_delivery_min', 0), 2, '.', ''),
        'estimated_days' => $v->str('estimated_days') ?: null,
        'contact_phone' => $v->str('contact_phone') ?: null,
        'tracking_url' => $v->str('tracking_url') ?: null,
        'is_default' => $isDefault,
        'status' => empty(request()->post('status')) ? 'inactive' : 'active',
        'sort_order' => $v->int('sort_order', 0),
    ];

    if ($isDefault) { q('UPDATE courier_methods SET is_default = 0'); }

    if ($existing) {
        update_row('courier_methods', $data, (int)$existing['id']);
        log_action('courier.update', 'courier', (int)$existing['id'], $data['name']);
        flash_redirect('success', 'Courier “' . $data['name'] . '” updated.', 'courier');
    }
    $id = insert_row('courier_methods', $data);
    log_action('courier.create', 'courier', $id, $data['name']);
    flash_redirect('success', 'Courier “' . $data['name'] . '” added.', 'courier');
}

public static function courier_delete(int $id): void
{
    verify_csrf_post();
    $c = self::courier_find($id);
    $used = (int)qval('SELECT COUNT(*) FROM orders WHERE courier_id = ?', [$id]);
    if ($used > 0) {
        flash('error', 'Courier “' . $c['name'] . '” is assigned to ' . $used . ' order(s). Set it to inactive instead.');
        aredirect('courier');
    }
    q('DELETE FROM courier_methods WHERE id = ?', [$id]);
    log_action('courier.delete', 'courier', $id, $c['name']);
    flash_redirect('success', 'Courier “' . $c['name'] . '” deleted.', 'courier');
}

public static function courier_toggle(int $id): void
{
    verify_csrf_post();
    $c = self::courier_find($id);
    $new = $c['status'] === 'active' ? 'inactive' : 'active';
    q('UPDATE courier_methods SET status = ? WHERE id = ?', [$new, $id]);
    log_action('courier.toggle', 'courier', $id, $c['name'] . ' → ' . $new);
    flash_redirect('success', $c['name'] . ' is now ' . $new . '.', 'courier');
}

public static function courier_default(int $id): void
{
    verify_csrf_post();
    $c = self::courier_find($id);
    q('UPDATE courier_methods SET is_default = 0');
    q("UPDATE courier_methods SET is_default = 1, status = 'active' WHERE id = ?", [$id]);
    log_action('courier.default', 'courier', $id, $c['name']);
    flash_redirect('success', $c['name'] . ' is now the default courier.', 'courier');
}
}
