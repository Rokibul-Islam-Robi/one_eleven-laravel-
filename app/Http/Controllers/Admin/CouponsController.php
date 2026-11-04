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
 * Offers & Discounts + Doctor Reference Coupons — shared CRUD on the coupons table.
 * mode 'offers'  → store-wide coupons (doctor_id IS NULL)
 * mode 'dcoupons' → doctor reference coupons (doctor_id IS NOT NULL)
 */
final class CouponsController extends Controller
{
    public const COUPON_TYPES = ['percent', 'fixed'];

public static function mod_offers(string $action, int $id): void
{
    self::coupons_controller('offers', $action, $id);
}

public static function mod_dcoupons(string $action, int $id): void
{
    self::coupons_controller('doctor_coupons', $action, $id);
}

public static function coupons_mode_url(string $mode, string $path = '', array $query = []): string
{
    return aurl($mode === 'offers' ? 'offers' . ($path !== '' ? '/' . $path : '') : 'doctor-coupons' . ($path !== '' ? '/' . $path : ''), $query);
}

public static function coupons_active_mode(): string
{
    return ($GLOBALS['ADMIN_MODULE'] ?? '') === 'doctor-coupons' ? 'doctor_coupons' : 'offers';
}

public static function coupons_controller(string $mode, string $action, int $id): void
{
    $GLOBALS['ADMIN_MODULE'] = $mode === 'offers' ? 'offers' : 'doctor-coupons'; // URL form (used by Reset/pager links)
    switch ($action) {
        case 'new':
            require_module($mode, 'edit');
            self::coupons_form($mode, null);
            break;
        case 'create':
            require_module($mode, 'edit');
            self::coupons_save($mode, null);
            break;
        case 'edit':
            require_module($mode, 'edit');
            self::coupons_form($mode, self::coupons_find($id, $mode));
            break;
        case 'update':
            require_module($mode, 'edit');
            self::coupons_save($mode, self::coupons_find($id, $mode));
            break;
        case 'delete':
            require_module($mode, 'delete');
            self::coupons_delete($id, $mode);
            break;
        case 'toggle':
            require_module($mode, 'edit');
            self::coupons_toggle($id, $mode);
            break;
        default:
            self::coupons_list($mode);
    }
}

public static function coupons_find(int $id, string $mode): array
{
    $row = qrow('SELECT * FROM coupons WHERE id = ?', [$id]);
    if (!$row || ($mode === 'offers' ? $row['doctor_id'] !== null : $row['doctor_id'] === null)) {
        adm_status(404);
        render_layout('Coupon not found', $mode, '<div class="adm-card adm-card-pad adm-empty"><h3>Coupon not found</h3><a class="abtn primary mt-3" href="' . e(self::coupons_mode_url($mode)) . '">Back</a></div>');
    }
    return $row;
}

public static function coupons_list(string $mode): void
{
    $q = getq('q');
    $active = getq('active');
    $page = pint(getq('page', '1'));
    $isDoctor = $mode === 'doctor_coupons';

    $where = $isDoctor ? 'c.doctor_id IS NOT NULL' : 'c.doctor_id IS NULL';
    $params = [];
    if ($q !== '') {
        $where .= ' AND (c.code LIKE ? OR c.description LIKE ? OR d.name LIKE ?)';
        $like = like($q);
        array_push($params, $like, $like, $like);
    }
    if ($active !== '' && in_array($active, ['1', '0'], true)) {
        $where .= ' AND c.active = ?';
        $params[] = (int)$active;
    }

    $total = (int)qval("SELECT COUNT(*) FROM coupons c LEFT JOIN doctors d ON d.id = c.doctor_id WHERE $where", $params);
    [$limitSql, , $pager] = paginate($total, $page, (int)config('admin.per_page'));
    $rows = qall(
        "SELECT c.*, d.name AS doctor_name, d.reference_code AS doctor_code
         FROM coupons c LEFT JOIN doctors d ON d.id = c.doctor_id
         WHERE $where ORDER BY c.id DESC $limitSql",
        $params
    );

    $title = $isDoctor ? 'Doctor Reference Coupons' : 'Offers & Discounts';
    $sub = $isDoctor
        ? 'Discount coupons tied to referring doctors. Patients use the code at checkout; each use is recorded as a referral.'
        : 'Store-wide promo codes shown on the storefront (hero banners, deals rows).';

    $actions = '';
    if (can($mode, 'edit')) {
        $actions .= '<a class="abtn primary" href="' . e(self::coupons_mode_url($mode, 'new')) . '">' . icon('plus') . ($isDoctor ? ' New reference coupon' : ' New offer') . '</a>';
    }

    $html = page_head($title, $sub, $actions);
    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([
        filter_select('active', 'Status', ['' => 'All', '1' => 'Active', '0' => 'Inactive'], $active),
    ], $isDoctor ? 'Search code, description or doctor…' : 'Search code or description…', $q);

    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon($isDoctor ? 'coupon' : 'offer') . '</div><h3>No coupons found</h3><p>'
            . ($q || $active !== '' ? 'No records match the current filters.' : 'Create your first ' . ($isDoctor ? 'reference coupon' : 'offer') . '.')
            . '</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Code</th>'
            . ($isDoctor ? '<th>Doctor</th>' : '')
            . '<th>Discount</th><th>Min. order</th><th class="num">Usage</th><th>Validity</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($rows as $c) {
            $validity = [];
            if ($c['starts_at']) { $validity[] = 'from ' . d($c['starts_at']); }
            if ($c['ends_at']) { $validity[] = 'until ' . d($c['ends_at']); }
            $discount = $c['discount_type'] === 'fixed'
                ? amoney($c['discount_percent'])
                : rtrim(rtrim(number_format((float)$c['discount_percent'], 2), '0'), '.') . '%';
            $usage = (int)$c['used_count'] . '/' . ((int)$c['max_uses'] > 0 ? (int)$c['max_uses'] : '∞');
            $html .= '<tr>'
                . '<td><span class="code-chip">' . e($c['code']) . '<button type="button" data-copy="' . e($c['code']) . '" title="Copy">' . icon('copy') . '</button></span>'
                . ($c['description'] ? '<div class="cell-sub">' . e($c['description']) . '</div>' : '') . '</td>';
            if ($isDoctor) {
                $html .= '<td>' . ($c['doctor_id'] ? '<a href="' . e(aurl('doctors/view/' . (int)$c['doctor_id'])) . '">' . e($c['doctor_name']) . '</a><div class="cell-sub">' . e($c['doctor_code']) . '</div>' : '<span class="cell-sub">—</span>') . '</td>';
            }
            $html .= '<td><b>' . e($discount) . '</b> <span class="cell-sub">' . ($c['discount_type'] === 'fixed' ? 'flat' : 'percent') . '</span></td>'
                . '<td>' . ((float)$c['min_order'] > 0 ? amoney($c['min_order']) : '<span class="cell-sub">none</span>') . '</td>'
                . '<td class="num">' . e($usage) . '</td>'
                . '<td>' . e($validity ? implode(', ', $validity) : 'Always') . '</td>'
                . '<td>' . status_badge($c['active'] ? 'active' : 'inactive') . '</td>'
                . '<td class="actions-cell">';
            if (can($mode, 'edit')) {
                $html .= '<a class="abtn secondary xs" href="' . e(self::coupons_mode_url($mode, 'edit/' . (int)$c['id'])) . '">Edit</a>'
                    . '<form method="post" action="' . e(self::coupons_mode_url($mode, 'toggle/' . (int)$c['id'])) . '" class="d-inline">' . csrf_field()
                    . '<button class="abtn secondary xs" type="submit">' . ($c['active'] ? 'Disable' : 'Enable') . '</button></form>';
            }
            if (can($mode, 'delete')) {
                $html .= '<form method="post" action="' . e(self::coupons_mode_url($mode, 'delete/' . (int)$c['id'])) . '" class="d-inline"' . confirm_delete_js() . '>'
                    . csrf_field() . '<button class="abtn danger-soft xs" type="submit">Delete</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, array_filter(['q' => $q, 'active' => $active]));
    }
    $html .= '</div>';

    render_layout($title, $GLOBALS['ADMIN_MODULE'], $html);
}

public static function coupons_form(string $mode, ?array $c, array $errors = [], array $old = []): void
{
    $isEdit = $c !== null;
    $isDoctor = $mode === 'doctor_coupons';
    $v = static function (string $key, $default = '') use ($c, $old) {
        if ($old) { return (string)($old[$key] ?? $default); }
        return (string)($c[$key] ?? $default);
    };
    $err = static fn(string $k): string => isset($errors[$k]) ? ' err' : '';
    $errmsg = static fn(string $k): string => isset($errors[$k]) ? '<div class="aerr">' . e($errors[$k]) . '</div>' : '';

    $doctors = qall("SELECT id, name, reference_code FROM doctors ORDER BY name");
    $presetDoctor = (int)getq('doctor_id', (string)($c['doctor_id'] ?? '0'));

    $html = page_head(
        $isEdit ? 'Edit Coupon' : ($isDoctor ? 'New Reference Coupon' : 'New Offer'),
        $isDoctor ? 'Linked to a doctor; every checkout use records a referral.' : 'Store-wide discount code for the storefront.',
        '<a class="abtn ghost" href="' . e(self::coupons_mode_url($mode)) . '">← Back</a>'
    );

    $html .= '<div class="adm-card" style="max-width:760px"><div class="adm-card-pad"><form method="post" action="' . e(self::coupons_mode_url($mode, $isEdit ? 'update' : 'create')) . '" novalidate>' . csrf_field();
    if ($isEdit) { $html .= '<input type="hidden" name="id" value="' . (int)$c['id'] . '">'; }

    $html .= '<div class="agrid-2">';
    $html .= '<div class="afield"><label>Coupon code * <span class="hint">(A–Z, 0–9)</span></label><input class="ainput' . $err('code') . '" name="code" value="' . e($v('code')) . '" placeholder="e.g. ' . ($isDoctor ? 'DOCRAHIM10' : 'FESTIVE25') . '" style="text-transform:uppercase" required>' . $errmsg('code') . '</div>';
    $html .= '<div class="afield"><label>Discount type *</label><select class="aselect" name="discount_type">'
        . '<option value="percent"' . ($v('discount_type', 'percent') === 'percent' ? ' selected' : '') . '>Percentage (%)</option>'
        . '<option value="fixed"' . ($v('discount_type') === 'fixed' ? ' selected' : '') . '>Fixed amount (' . e(config('admin.currency')) . ')</option>'
        . '</select></div>';
    $html .= '</div><div class="agrid-2">';
    $html .= '<div class="afield"><label>Discount value * </label><input class="ainput' . $err('discount_percent') . '" type="number" step="0.01" min="0" name="discount_percent" value="' . e($v('discount_percent', '0')) . '" required>' . $errmsg('discount_percent') . '</div>';
    $html .= '<div class="afield"><label>Minimum order amount</label><input class="ainput' . $err('min_order') . '" type="number" step="0.01" min="0" name="min_order" value="' . e($v('min_order', '0')) . '">' . $errmsg('min_order') . '</div>';
    $html .= '</div>';

    if ($isDoctor) {
        $html .= '<div class="afield"><label>Linked doctor *</label><select class="aselect' . $err('doctor_id') . '" name="doctor_id" required>'
            . '<option value="">— select doctor —</option>';
        foreach ($doctors as $d) {
            $sel = ($presetDoctor ? $presetDoctor : (int)$v('doctor_id')) === (int)$d['id'];
            $html .= '<option value="' . (int)$d['id'] . '"' . ($sel ? ' selected' : '') . '>' . e($d['name']) . ' (' . e($d['reference_code']) . ')</option>';
        }
        $html .= '</select>' . $errmsg('doctor_id') . '</div>';
    } else {
        $html .= '<input type="hidden" name="doctor_id" value="0">';
    }

    $html .= '<div class="agrid-3">';
    $html .= '<div class="afield"><label>Max total uses <span class="hint">(0 = unlimited)</span></label><input class="ainput' . $err('max_uses') . '" type="number" min="0" name="max_uses" value="' . e($v('max_uses', '0')) . '">' . $errmsg('max_uses') . '</div>';
    $html .= '<div class="afield"><label>Starts at</label><input class="ainput' . $err('starts_at') . '" type="datetime-local" name="starts_at" value="' . e(str_replace(' ', 'T', substr((string)$v('starts_at'), 0, 16))) . '">' . $errmsg('starts_at') . '</div>';
    $html .= '<div class="afield"><label>Ends at</label><input class="ainput' . $err('ends_at') . '" type="datetime-local" name="ends_at" value="' . e(str_replace(' ', 'T', substr((string)$v('ends_at'), 0, 16))) . '">' . $errmsg('ends_at') . '</div>';
    $html .= '</div><div class="afield"><label>Description</label><input class="ainput' . $err('description') . '" name="description" value="' . e($v('description')) . '" placeholder="Shown in admin; patients see the code."></div>';
    $html .= '<div class="afield"><label class="d-flex align-items-center gap-2"><input type="checkbox" class="adm-check" name="active" value="1"' . ($v('active', '1') !== '0' ? ' checked' : '') . '> Active (accept at checkout)</label></div>';

    $html .= '<div class="form-actions"><button class="abtn primary" type="submit">' . ($isEdit ? 'Save changes' : 'Create coupon') . '</button><a class="abtn ghost" href="' . e(self::coupons_mode_url($mode)) . '">Cancel</a></div>';
    $html .= '</form></div></div>';

    render_layout($isEdit ? 'Edit Coupon' : 'New Coupon', $GLOBALS['ADMIN_MODULE'], $html);
}

public static function coupons_save(string $mode, ?array $existing): void
{
    verify_csrf_post();
    $isDoctor = $mode === 'doctor_coupons';
    $v = new Validator(request()->post());

    $v->required('code', 'Coupon code')
      ->max('code', 50, 'Coupon code')
      ->required('discount_percent', 'Discount value')
      ->numeric('discount_percent', 'Discount value', false, 0)
      ->numeric('min_order', 'Minimum order', true, 0)
      ->numeric('max_uses', 'Max uses', true, 0)
      ->in('discount_type', self::COUPON_TYPES, 'Discount type')
      ->max('description', 255, 'Description');

    $code = strtoupper(preg_replace('/\s+/', '', $v->str('code')));
    if ($code !== '' && !preg_match('/^[A-Z0-9_\-]{3,50}$/', $code)) {
        $v->errors['code'] = 'Code may only contain letters, numbers, dashes and underscores.';
    }
    $discount = $v->float('discount_percent', 0);
    if ($v->str('discount_type') === 'percent' && $discount > 100 && !isset($v->errors['discount_percent'])) {
        $v->errors['discount_percent'] = 'Percentage discount cannot exceed 100.';
    }
    foreach (['starts_at', 'ends_at'] as $f) {
        $raw = str_replace('T', ' ', $v->str($f));
        if ($raw !== '' && strtotime($raw) === false) {
            $v->errors[$f] = 'Invalid date/time.';
        }
    }
    if ($v->str('starts_at') !== '' && $v->str('ends_at') !== ''
        && strtotime(str_replace('T', ' ', $v->str('starts_at'))) >= strtotime(str_replace('T', ' ', $v->str('ends_at')))) {
        $v->errors['ends_at'] = 'End date must be after the start date.';
    }

    $doctorId = 0;
    if ($isDoctor) {
        $doctorId = $v->int('doctor_id');
        if (!$doctorId) { $v->errors['doctor_id'] = 'Please select a doctor.'; }
    } else {
        $doctorId = $v->int('doctor_id') ?: 0;
        if ($doctorId) { $doctorId = 0; } // offers are always store-wide
    }

    $dup = qval('SELECT id FROM coupons WHERE code = ? AND id <> ?', [$code, (int)($existing['id'] ?? 0)]);
    if ((int)$dup) { $v->errors['code'] = 'This coupon code already exists.'; }

    if ($v->fails()) {
        flash('error', 'Please correct the highlighted fields.');
        self::coupons_form($mode, $existing, $v->errors, request()->post());
        return;
    }

    $starts = $v->str('starts_at') !== '' ? str_replace('T', ' ', $v->str('starts_at')) . ':00' : null;
    $ends = $v->str('ends_at') !== '' ? str_replace('T', ' ', $v->str('ends_at')) . ':00' : null;

    $data = [
        'code' => $code,
        'discount_percent' => number_format($discount, 2, '.', ''),
        'discount_type' => $v->str('discount_type', 'percent'),
        'active' => $v->bool('active') ? 1 : 0,
        'doctor_id' => $doctorId ?: null,
        'description' => $v->str('description') ?: null,
        'min_order' => number_format($v->float('min_order', 0), 2, '.', ''),
        'max_uses' => $v->int('max_uses', 0),
        'starts_at' => $starts,
        'ends_at' => $ends,
    ];

    if ($existing) {
        update_row('coupons', $data, (int)$existing['id']);
        log_action('coupon.update', 'coupon', (int)$existing['id'], $code);
        flash_redirect('success', 'Coupon ' . $code . ' updated.', $mode === 'offers' ? 'offers' : 'doctor-coupons');
    } else {
        $id = insert_row('coupons', $data);
        log_action('coupon.create', 'coupon', $id, $code);
        flash_redirect('success', 'Coupon ' . $code . ' created.', $mode === 'offers' ? 'offers' : 'doctor-coupons');
    }
}

public static function coupons_delete(int $id, string $mode): void
{
    verify_csrf_post();
    $c = self::coupons_find($id, $mode);
    $used = (int)qval('SELECT COUNT(*) FROM doctor_referrals WHERE coupon_code = ?', [$c['code']]);
    if ($used > 0) {
        flash('warning', 'Coupon ' . $c['code'] . ' is referenced by ' . $used . ' referral record(s) — it has been deactivated instead of deleted to preserve history.');
        q('UPDATE coupons SET active = 0 WHERE id = ?', [$id]);
        aredirect($mode === 'offers' ? 'offers' : 'doctor-coupons');
    }
    q('DELETE FROM coupons WHERE id = ?', [$id]);
    log_action('coupon.delete', 'coupon', $id, $c['code']);
    flash_redirect('success', 'Coupon ' . $c['code'] . ' deleted.', $mode === 'offers' ? 'offers' : 'doctor-coupons');
}

public static function coupons_toggle(int $id, string $mode): void
{
    verify_csrf_post();
    $c = self::coupons_find($id, $mode);
    q('UPDATE coupons SET active = 1 - active WHERE id = ?', [$id]);
    log_action('coupon.toggle', 'coupon', $id, $c['code']);
    flash_redirect('success', 'Coupon ' . $c['code'] . ' ' . ($c['active'] ? 'disabled' : 'enabled') . '.', $mode === 'offers' ? 'offers' : 'doctor-coupons');
}
}
