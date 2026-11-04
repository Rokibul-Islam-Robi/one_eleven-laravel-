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
 * Doctors — complete CRUD with search, filters, validation, pagination,
 * reference codes and referral history.
 */
final class DoctorsController extends Controller
{
    public const DOCTOR_STATUSES = ['active', 'inactive'];
    public const DOCTOR_VERIFY = ['unverified', 'pending', 'verified', 'rejected'];

public static function mod_doctors(string $action, int $id): void
{
    switch ($action) {
        case 'new':
            require_module('doctors', 'edit');
            self::doctors_form(null);
            break;
        case 'create':
            require_module('doctors', 'edit');
            self::doctors_save(null);
            break;
        case 'edit':
            require_module('doctors', 'edit');
            self::doctors_form(self::doctors_find($id));
            break;
        case 'update':
            require_module('doctors', 'edit');
            self::doctors_save(self::doctors_find($id));
            break;
        case 'delete':
            require_module('doctors', 'delete');
            self::doctors_delete($id);
            break;
        case 'toggle':
            require_module('doctors', 'edit');
            self::doctors_toggle($id);
            break;
        case 'view':
            self::doctors_view(self::doctors_find($id));
            break;
        default:
            self::doctors_list();
    }
}

public static function doctors_find(int $id): array
{
    $row = qrow('SELECT * FROM doctors WHERE id = ?', [$id]);
    if (!$row) {
        adm_status(404);
        render_layout('Doctor not found', 'doctors', '<div class="adm-card adm-card-pad adm-empty"><h3>Doctor not found</h3><p>The requested doctor record does not exist.</p><a class="abtn primary mt-3" href="' . e(aurl('doctors')) . '">Back to doctors</a></div>');
    }
    return $row;
}

/* ------------------------------------------------------------------ */
/* List with search, filters, pagination                               */
/* ------------------------------------------------------------------ */
public static function doctors_list(): void
{
    $q = getq('q');
    $status = getq('status');
    $verify = getq('verify');
    $spec = getq('specialty');
    $sort = getq('sort', 'name');
    $page = pint(getq('page', '1'));

    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(d.name LIKE ? OR d.email LIKE ? OR d.phone LIKE ? OR d.registration_number LIKE ? OR d.reference_code LIKE ? OR d.specialty LIKE ?)';
        $like = like($q);
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    if ($status !== '' && in_array($status, self::DOCTOR_STATUSES, true)) {
        $where[] = 'd.status = ?';
        $params[] = $status;
    }
    if ($verify !== '' && in_array($verify, self::DOCTOR_VERIFY, true)) {
        $where[] = 'd.verification_status = ?';
        $params[] = $verify;
    }
    if ($spec !== '') {
        $where[] = 'd.specialty = ?';
        $params[] = $spec;
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $sortMap = [
        'name' => 'd.name ASC',
        'name_desc' => 'd.name DESC',
        'recent' => 'd.created_at DESC',
        'discount' => 'd.reference_discount DESC',
        'refs' => 'ref_count DESC',
    ];
    $orderBy = $sortMap[$sort] ?? $sortMap['name'];

    $total = (int)qval('SELECT COUNT(*) FROM doctors d' . $whereSql, $params);
    [$limitSql, , $pager] = paginate($total, $page, (int)config('admin.per_page'));

    $rows = qall(
        "SELECT d.*,
                (SELECT COUNT(*) FROM doctor_referrals r WHERE r.doctor_id = d.id AND r.status <> 'cancelled') AS ref_count,
                (SELECT COALESCE(SUM(r.discount_amount),0) FROM doctor_referrals r WHERE r.doctor_id = d.id AND r.status <> 'cancelled') AS ref_value,
                (SELECT COUNT(*) FROM coupons c WHERE c.doctor_id = d.id) AS coupon_count
         FROM doctors d$whereSql
         ORDER BY $orderBy $limitSql",
        $params
    );

    $specialties = qall('SELECT specialty, COUNT(*) c FROM doctors WHERE specialty IS NOT NULL AND specialty <> "" GROUP BY specialty ORDER BY specialty');
    $specOptions = ['' => 'All specialties'];
    foreach ($specialties as $s) { $specOptions[$s['specialty']] = $s['specialty'] . ' (' . (int)$s['c'] . ')'; }

    $actions = '';
    if (can('doctors', 'edit')) {
        $actions .= '<a class="abtn primary" href="' . e(aurl('doctors/new')) . '">' . icon('plus') . ' Add Doctor</a>';
    }

    $html = page_head('Doctors', 'Manage the referring doctor network, verification and reference codes.', $actions);

    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([
        filter_select('status', 'Status', ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'], $status),
        filter_select('verify', 'Verification', ['' => 'All'] + array_combine(self::DOCTOR_VERIFY, array_map('ucfirst', self::DOCTOR_VERIFY)), $verify),
        filter_select('specialty', 'Specialty', $specOptions, $spec),
        filter_select('sort', 'Sort', ['name' => 'Name A–Z', 'name_desc' => 'Name Z–A', 'recent' => 'Newest first', 'discount' => 'Highest discount', 'refs' => 'Most referrals'], $sort),
    ], 'Search name, email, phone, BMDC or reference code…', $q);

    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('doctor') . '</div><h3>No doctors found</h3><p>'
            . ($q || $status || $verify || $spec ? 'No records match the current filters. Try clearing them.' : 'Add your first referring doctor to start tracking referrals.')
            . '</p>' . (can('doctors', 'edit') ? '<a class="abtn primary mt-3" href="' . e(aurl('doctors/new')) . '">Add Doctor</a>' : '') . '</div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr>'
            . '<th>Doctor</th><th>Contact</th><th>Registration</th><th>Reference code</th>'
            . '<th class="num">Discount</th><th class="num">Referrals</th><th>Verification</th><th>Status</th><th></th>'
            . '</tr></thead><tbody>';
        foreach ($rows as $d) {
            $html .= '<tr>'
                . '<td><a href="' . e(aurl('doctors/view/' . (int)$d['id'])) . '"><span class="cell-main">' . e($d['name']) . '</span></a>'
                . '<div class="cell-sub">' . e($d['specialty'] ?: 'General practice') . ' · ' . e($d['city'] ?: '—') . '</div></td>'
                . '<td>' . e($d['phone'] ?: '—') . '<div class="cell-sub">' . e($d['email'] ?: 'No email') . '</div></td>'
                . '<td>' . e($d['registration_number'] ?: '—')
                . '<div class="cell-sub">' . e($d['registration_council'] ?: '') . '</div></td>'
                . '<td><span class="code-chip">' . e($d['reference_code']) . '<button type="button" data-copy="' . e($d['reference_code']) . '" title="Copy code">' . icon('copy') . '</button></span>'
                . '<div class="cell-sub">' . (int)$d['coupon_count'] . ' coupon(s)</div></td>'
                . '<td class="num"><b>' . rtrim(rtrim(number_format((float)$d['reference_discount'], 2), '0'), '.') . '%</b></td>'
                . '<td class="num"><b>' . (int)$d['ref_count'] . '</b><div class="cell-sub">' . amoney($d['ref_value']) . '</div></td>'
                . '<td>' . status_badge((string)$d['verification_status']) . '</td>'
                . '<td>' . status_badge((string)$d['status']) . '</td>'
                . '<td class="actions-cell">'
                . '<a class="abtn secondary xs" href="' . e(aurl('doctors/view/' . (int)$d['id'])) . '">View</a>';
            if (can('doctors', 'edit')) {
                $html .= '<a class="abtn secondary xs" href="' . e(aurl('doctors/edit/' . (int)$d['id'])) . '">Edit</a>';
            }
            if (can('doctors', 'delete')) {
                $html .= '<form method="post" action="' . e(aurl('doctors/delete/' . (int)$d['id'])) . '" class="d-inline"' . confirm_delete_js() . '>'
                    . csrf_field() . '<button class="abtn danger-soft xs" type="submit">Delete</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, array_filter(['q' => $q, 'status' => $status, 'verify' => $verify, 'specialty' => $spec, 'sort' => $sort !== 'name' ? $sort : '']));
    }
    $html .= '</div>';

    render_layout('Doctors', 'doctors', $html);
}

/* ------------------------------------------------------------------ */
/* Create / edit form                                                  */
/* ------------------------------------------------------------------ */
public static function doctors_form(?array $d, array $errors = [], array $old = []): void
{
    $isEdit = $d !== null;
    $v = static function (string $key, $default = '') use ($d, $old, $isEdit) {
        if ($old) { return (string)($old[$key] ?? $default); }
        return (string)($d[$key] ?? $default);
    };
    $err = static fn(string $key): string => isset($errors[$key]) ? ' err' : '';
    $errmsg = static fn(string $key): string => isset($errors[$key]) ? '<div class="aerr">' . e($errors[$key]) . '</div>' : '';

    $verifyOptions = array_combine(self::DOCTOR_VERIFY, array_map('ucfirst', self::DOCTOR_VERIFY));

    $html = page_head(
        $isEdit ? 'Edit Doctor' : 'Add Doctor',
        $isEdit ? 'Update profile, verification and referral settings for ' . $d['name'] . '.' : 'Register a new referring doctor. A unique reference code is generated automatically.',
        '<a class="abtn ghost" href="' . e(aurl('doctors')) . '">← Back to list</a>'
    );

    $html .= '<div class="adm-card"><div class="adm-card-pad">';
    $html .= '<form method="post" action="' . e(aurl($isEdit ? 'doctors/update' : 'doctors/create')) . '" novalidate>';
    $html .= csrf_field();
    if ($isEdit) { $html .= '<input type="hidden" name="id" value="' . (int)$d['id'] . '">'; }

    $html .= '<h3 style="font-size:14px;margin-bottom:12px">Personal details</h3><div class="agrid-2">';
    $html .= '<div class="afield"><label>Full name *</label><input class="ainput' . $err('name') . '" name="name" value="' . e($v('name')) . '" placeholder="e.g. Dr. Rahim Uddin" required>' . $errmsg('name') . '</div>';
    $html .= '<div class="afield"><label>Specialty</label><input class="ainput' . $err('specialty') . '" name="specialty" value="' . e($v('specialty')) . '" placeholder="e.g. Cardiology" list="specialty-list">'
        . '<datalist id="specialty-list">'
        . '<option value="Cardiology"><option value="Gynecology"><option value="General Medicine"><option value="Pediatrics"><option value="Orthopedics"><option value="Dermatology"><option value="ENT"><option value="Ophthalmology">'
        . '</datalist>' . $errmsg('specialty') . '</div>';
    $html .= '</div><div class="agrid-2">';
    $html .= '<div class="afield"><label>Email</label><input class="ainput' . $err('email') . '" type="email" name="email" value="' . e($v('email')) . '" placeholder="doctor@clinic.com">' . $errmsg('email') . '</div>';
    $html .= '<div class="afield"><label>Mobile number *</label><input class="ainput' . $err('phone') . '" name="phone" value="' . e($v('phone')) . '" placeholder="+8801XXXXXXXXX" required>' . $errmsg('phone') . '</div>';
    $html .= '</div><div class="agrid-2">';
    $html .= '<div class="afield"><label>Alternate phone</label><input class="ainput' . $err('alternate_phone') . '" name="alternate_phone" value="' . e($v('alternate_phone')) . '">' . $errmsg('alternate_phone') . '</div>';
    $html .= '<div class="afield"><label>City</label><input class="ainput' . $err('city') . '" name="city" value="' . e($v('city')) . '" placeholder="e.g. Dhaka">' . $errmsg('city') . '</div>';
    $html .= '</div><div class="afield"><label>Clinic / chamber address</label><input class="ainput' . $err('address') . '" name="address" value="' . e($v('address')) . '" placeholder="House, road, area">' . $errmsg('address') . '</div>';

    $html .= '<h3 style="font-size:14px;margin:18px 0 12px">Registration &amp; verification</h3><div class="agrid-3">';
    $html .= '<div class="afield"><label>Registration number</label><input class="ainput' . $err('registration_number') . '" name="registration_number" value="' . e($v('registration_number')) . '" placeholder="e.g. BMDC-A-45211">' . $errmsg('registration_number')
        . '<div class="hint">Must be unique (BMDC / license number).</div></div>';
    $html .= '<div class="afield"><label>Registering council</label><input class="ainput' . $err('registration_council') . '" name="registration_council" value="' . e($v('registration_council')) . '" placeholder="e.g. Bangladesh Medical & Dental Council">' . $errmsg('registration_council') . '</div>';
    $html .= '<div class="afield"><label>Verified on</label><input class="ainput' . $err('registration_verified_at') . '" type="date" name="registration_verified_at" value="' . e($v('registration_verified_at')) . '">' . $errmsg('registration_verified_at') . '</div>';
    $html .= '</div><div class="agrid-2">';
    $html .= '<div class="afield"><label>Verification status *</label><select class="aselect' . $err('verification_status') . '" name="verification_status">';
    foreach ($verifyOptions as $val => $lbl) {
        $html .= '<option value="' . e($val) . '"' . ($v('verification_status', 'unverified') === $val ? ' selected' : '') . '>' . e($lbl) . '</option>';
    }
    $html .= '</select>' . $errmsg('verification_status') . '</div>';
    $html .= '<div class="afield"><label>Status *</label><select class="aselect" name="status">'
        . '<option value="active"' . ($v('status', 'active') === 'active' ? ' selected' : '') . '>Active</option>'
        . '<option value="inactive"' . ($v('status') === 'inactive' ? ' selected' : '') . '>Inactive</option>'
        . '</select></div>';
    $html .= '</div>';

    $html .= '<h3 style="font-size:14px;margin:18px 0 12px">Reference &amp; referral settings</h3><div class="agrid-2">';
    $html .= '<div class="afield"><label>Reference code</label><input class="ainput' . $err('reference_code') . '" name="reference_code" value="' . e($v('reference_code')) . '" placeholder="Leave blank to auto-generate">'
        . '<div class="hint">Shown to patients; unique across all doctors.</div>' . $errmsg('reference_code') . '</div>';
    $html .= '<div class="afield"><label>Reference discount (%)</label><input class="ainput' . $err('reference_discount') . '" type="number" step="0.01" min="0" max="100" name="reference_discount" value="' . e($v('reference_discount', '0')) . '">'
        . '<div class="hint">0–100. Used for coupons created for this doctor.</div>' . $errmsg('reference_discount') . '</div>';
    $html .= '</div><div class="afield"><label>Internal notes</label><textarea class="atextarea" name="notes" rows="3" placeholder="Agreement details, campaign notes…">' . e($v('notes')) . '</textarea></div>';

    $html .= '<div class="form-actions"><button class="abtn primary" type="submit">' . ($isEdit ? 'Save changes' : 'Create doctor') . '</button>'
        . '<a class="abtn ghost" href="' . e(aurl('doctors')) . '">Cancel</a></div>';
    $html .= '</form></div></div>';

    render_layout($isEdit ? 'Edit Doctor' : 'Add Doctor', 'doctors', $html);
}

/* ------------------------------------------------------------------ */
/* Save (create + update) with validation                              */
/* ------------------------------------------------------------------ */
public static function doctors_save(?array $existing): void
{
    verify_csrf_post();
    $v = new Validator(request()->post());

    $v->required('name', 'Full name')
      ->max('name', 150, 'Full name')
      ->max('specialty', 120, 'Specialty')
      ->email('email', 'Email')
      ->max('email', 190, 'Email')
      ->required('phone', 'Mobile number')
      ->phone('phone', 'Mobile number')
      ->phone('alternate_phone', 'Alternate phone')
      ->max('address', 255, 'Address')
      ->max('city', 80, 'City')
      ->max('registration_number', 80, 'Registration number')
      ->max('registration_council', 120, 'Council')
      ->date('registration_verified_at', 'Verified date')
      ->in('verification_status', self::DOCTOR_VERIFY, 'Verification status')
      ->in('status', self::DOCTOR_STATUSES, 'Status')
      ->max('reference_code', 20, 'Reference code')
      ->numeric('reference_discount', 'Reference discount', true, 0);

    $discount = $v->float('reference_discount', 0);
    if ($discount > 100 && !isset($v->errors['reference_discount'])) {
        $v->errors['reference_discount'] = 'Reference discount cannot exceed 100%.';
    }

    $code = strtoupper($v->str('reference_code'));
    if ($code !== '' && !preg_match('/^[A-Z0-9\-]{3,20}$/', $code)) {
        $v->errors['reference_code'] = 'Reference code may only contain letters, numbers and dashes (3–20 characters).';
    }
    if ($code === '' && $v->passes()) {
        $code = generate_ref_code();
    }

    // uniqueness: email, registration, reference code
    $email = $v->str('email');
    if ($email !== '') {
        $dup = qval('SELECT id FROM doctors WHERE email = ? AND id <> ?', [$email, (int)($existing['id'] ?? 0)]);
        if ((int)$dup) { $v->errors['email'] = 'A doctor with this email already exists.'; }
    }
    $reg = $v->str('registration_number');
    if ($reg !== '') {
        $dup = qval('SELECT id FROM doctors WHERE registration_number = ? AND id <> ?', [$reg, (int)($existing['id'] ?? 0)]);
        if ((int)$dup) { $v->errors['registration_number'] = 'This registration number is already registered.'; }
    }
    if ($code !== '') {
        $dup = qval('SELECT id FROM doctors WHERE reference_code = ? AND id <> ?', [$code, (int)($existing['id'] ?? 0)]);
        if ((int)$dup) { $v->errors['reference_code'] = 'This reference code is already in use.'; }
    }

    if ($v->fails()) {
        flash('error', 'Please correct the highlighted fields.');
        self::doctors_form($existing, $v->errors, request()->post());
        return;
    }

    $data = [
        'name' => $v->str('name'),
        'specialty' => $v->str('specialty'),
        'email' => $email ?: null,
        'phone' => $v->str('phone'),
        'alternate_phone' => $v->str('alternate_phone') ?: null,
        'address' => $v->str('address') ?: null,
        'city' => $v->str('city') ?: null,
        'registration_number' => $reg ?: null,
        'registration_council' => $v->str('registration_council') ?: null,
        'registration_verified_at' => $v->str('registration_verified_at') ?: null,
        'verification_status' => $v->str('verification_status', 'unverified'),
        'reference_code' => $code,
        'reference_discount' => number_format($discount, 2, '.', ''),
        'status' => $v->str('status', 'active'),
        'notes' => $v->str('notes') ?: null,
    ];

    try {
        if ($existing) {
            update_row('doctors', $data, (int)$existing['id']);
            $docId = (int)$existing['id'];
            log_action('doctor.update', 'doctor', $docId, $data['name'] . ' · ' . $code);
            flash_redirect('success', 'Doctor “' . $data['name'] . '” updated.', 'doctors/view/' . $docId);
        } else {
            $data['created_by'] = (int)session('admin_user.id');
            $docId = insert_row('doctors', $data);
            log_action('doctor.create', 'doctor', $docId, $data['name'] . ' · ' . $code);
            flash_redirect('success', 'Doctor “' . $data['name'] . '” created with reference code ' . $code . '.', 'doctors/view/' . $docId);
        }
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            $v->errors['reference_code'] = 'Duplicate value — the reference code, email or registration number is already used.';
            flash('error', 'A unique field conflicts with an existing record.');
            self::doctors_form($existing, $v->errors, request()->post());
            return;
        }
        throw $e;
    }
}

/* ------------------------------------------------------------------ */
/* View + referral history                                             */
/* ------------------------------------------------------------------ */
public static function doctors_view(array $d): void
{
    $docId = (int)$d['id'];

    $stats = [
        'referrals' => (int)qval("SELECT COUNT(*) FROM doctor_referrals WHERE doctor_id=? AND status<>'cancelled'", [$docId]),
        'pending'   => (int)qval("SELECT COUNT(*) FROM doctor_referrals WHERE doctor_id=? AND status='pending'", [$docId]),
        'value'     => (float)qval("SELECT COALESCE(SUM(discount_amount),0) FROM doctor_referrals WHERE doctor_id=? AND status<>'cancelled'", [$docId]),
        'orders'    => (int)qval('SELECT COUNT(*) FROM doctor_referrals WHERE doctor_id=? AND order_id IS NOT NULL AND status<>"cancelled"', [$docId]),
        'coupons'   => (int)qval('SELECT COUNT(*) FROM coupons WHERE doctor_id=?', [$docId]),
    ];

    $refWhere = 'WHERE r.doctor_id = ?';
    $refParams = [$docId];
    $rf = getq('rstatus');
    if ($rf !== '' && in_array($rf, ['pending', 'credited', 'cancelled'], true)) {
        $refWhere .= ' AND r.status = ?';
        $refParams[] = $rf;
    }
    $referrals = qall(
        "SELECT r.*, o.order_number, u.name AS customer
         FROM doctor_referrals r
         LEFT JOIN orders o ON o.id = r.order_id
         LEFT JOIN users u ON u.id = r.customer_id
         $refWhere
         ORDER BY r.referred_at DESC, r.id DESC LIMIT 100",
        $refParams
    );

    $coupons = qall('SELECT * FROM coupons WHERE doctor_id = ? ORDER BY id DESC', [$docId]);
    $recentRx = qall('SELECT * FROM prescriptions WHERE doctor_id = ? ORDER BY created_at DESC LIMIT 5', [$docId]);

    $actions = '';
    if (can('doctors', 'edit')) {
        $actions .= '<a class="abtn secondary" href="' . e(aurl('doctors/edit/' . $docId)) . '">' . icon('edit') . ' Edit</a>';
        $actions .= '<form method="post" action="' . e(aurl('doctors/toggle/' . $docId)) . '" class="d-inline">' . csrf_field()
            . '<button class="abtn ' . ($d['status'] === 'active' ? 'danger-soft' : 'primary') . '" type="submit">'
            . ($d['status'] === 'active' ? 'Deactivate' : 'Activate') . '</button></form>';
    }
    if (can('doctors', 'delete')) {
        $actions .= '<form method="post" action="' . e(aurl('doctors/delete/' . $docId)) . '" class="d-inline"' . confirm_delete_js() . '>'
            . csrf_field() . '<button class="abtn danger" type="submit">Delete</button></form>';
    }

    $html = page_head('Doctor profile', 'Complete record, verification, coupons and referral history.', $actions);
    $html .= '<a class="abtn ghost sm mb-3" href="' . e(aurl('doctors')) . '">← All doctors</a>';

    $html .= '<div class="adm-grid-2" style="grid-template-columns:2fr 1fr">';

    /* profile card */
    $html .= '<div class="adm-card"><div class="adm-card-pad"><div class="prof-head">';
    $html .= '<span class="prof-avatar">' . e(strtoupper(mb_substr((string)preg_replace('/^Dr\.?\s*/i', '', $d['name']), 0, 1))) . '</span>';
    $html .= '<div class="grow" style="flex:1"><h2>' . e($d['name']) . '</h2>'
        . '<div class="meta">' . e($d['specialty'] ?: 'General practice') . ' · ' . e($d['city'] ?: 'Location not set') . '</div>'
        . '<div style="margin-top:8px">' . status_badge((string)$d['status']) . ' ' . status_badge((string)$d['verification_status']) . '</div>';
    $html .= '<div class="prof-stats">'
        . '<div class="ps"><b>' . (int)$stats['referrals'] . '</b><small>Referrals</small></div>'
        . '<div class="ps"><b>' . amoney($stats['value']) . '</b><small>Discount given</small></div>'
        . '<div class="ps"><b>' . (int)$stats['orders'] . '</b><small>Linked orders</small></div>'
        . '<div class="ps"><b>' . (int)$stats['coupons'] . '</b><small>Coupons</small></div>'
        . '</div></div></div>';

    $html .= '<dl class="dl-grid mt-4">';
    $html .= '<div><dt>Email</dt><dd>' . e($d['email'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Mobile</dt><dd>' . e($d['phone'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Alternate phone</dt><dd>' . e($d['alternate_phone'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Address</dt><dd>' . e($d['address'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Registration no.</dt><dd>' . e($d['registration_number'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Council</dt><dd>' . e($d['registration_council'] ?: '—') . '</dd></div>';
    $html .= '<div><dt>Verified on</dt><dd>' . e(d($d['registration_verified_at'])) . '</dd></div>';
    $html .= '<div><dt>Registered</dt><dd>' . e(dt($d['created_at'])) . '</dd></div>';
    $html .= '</dl>';
    if ($d['notes']) {
        $html .= '<div class="mt-3" style="font-size:13px;color:var(--a-muted)"><b style="color:var(--a-ink)">Notes:</b> ' . e($d['notes']) . '</div>';
    }
    $html .= '</div></div>';

    /* reference code + coupons card */
    $html .= '<div class="adm-card"><div class="adm-card-head"><h3>Reference code</h3></div><div class="adm-card-pad">';
    $html .= '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap"><span class="code-chip" style="font-size:16px;padding:8px 14px">' . e($d['reference_code'])
        . '<button type="button" data-copy="' . e($d['reference_code']) . '" title="Copy">' . icon('copy') . '</button></span>'
        . '<b>' . rtrim(rtrim(number_format((float)$d['reference_discount'], 2), '0'), '.') . '%</b> patient discount</div>';
    $html .= '<div style="margin-top:14px">';
    if (!$coupons) {
        $html .= '<p style="font-size:12.5px;color:var(--a-muted)">No coupons issued yet.'
            . (can('doctor_coupons', 'edit') ? ' <a style="color:var(--a-brand)" href="' . e(aurl('doctor-coupons/new', ['doctor_id' => $docId])) . '">Create a reference coupon</a>.' : '') . '</p>';
    } else {
        $html .= '<ul class="mini-list">';
        foreach ($coupons as $c) {
            $html .= '<li><span class="grow"><span class="t code-chip">' . e($c['code']) . '</span>'
                . '<span class="s">' . rtrim(rtrim(number_format((float)$c['discount_percent'], 2), '0'), '.') . '% off · used ' . (int)$c['used_count'] . '×</span></span>'
                . ($c['active'] ? status_badge('active') : status_badge('inactive')) . '</li>';
        }
        $html .= '</ul>';
    }
    $html .= '</div></div></div>';
    $html .= '</div>'; // grid

    /* referral history */
    $html .= '<div class="adm-card mt-3"><div class="adm-card-head"><h3>Referral history</h3>'
        . '<div class="d-flex gap-2"><form method="get" class="d-flex gap-2">'
        . '<select class="aselect adm-filter-select" name="rstatus" data-autosubmit>'
        . '<option value="">All statuses</option>'
        . '<option value="pending"' . ($rf === 'pending' ? ' selected' : '') . '>Pending</option>'
        . '<option value="credited"' . ($rf === 'credited' ? ' selected' : '') . '>Credited</option>'
        . '<option value="cancelled"' . ($rf === 'cancelled' ? ' selected' : '') . '>Cancelled</option>'
        . '</select></form></div></div>';
    if (!$referrals) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('coupon') . '</div><h3>No referrals yet</h3><p>When patients use this doctor’s reference code or coupon, referrals appear here.</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Date</th><th>Customer</th><th>Coupon</th><th>Order</th><th class="num">Order value</th><th class="num">Discount</th><th>Status</th></tr></thead><tbody>';
        foreach ($referrals as $r) {
            $html .= '<tr><td>' . e(d($r['referred_at'])) . '</td>'
                . '<td><span class="cell-main">' . e($r['customer'] ?: $r['customer_name']) . '</span></td>'
                . '<td><span class="code-chip">' . e($r['coupon_code'] ?: '—') . '</span></td>'
                . '<td>' . ($r['order_id'] ? '<a href="' . e(aurl('orders/view/' . (int)$r['order_id'])) . '">' . e($r['order_number'] ?: '#' . $r['order_id']) . '</a>' : '<span class="cell-sub">offline</span>') . '</td>'
                . '<td class="num">' . amoney($r['order_amount']) . '</td>'
                . '<td class="num">' . amoney($r['discount_amount']) . '</td>'
                . '<td>' . status_badge((string)$r['status']) . ($r['notes'] ? '<div class="cell-sub">' . e($r['notes']) . '</div>' : '') . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div>';

    /* prescriptions by this doctor */
    if ($recentRx) {
        $html .= '<div class="adm-card mt-3"><div class="adm-card-head"><h3>Prescriptions linked to this doctor</h3><a class="abtn ghost sm" href="' . e(aurl('prescriptions')) . '">All prescriptions</a></div>';
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Patient</th><th>Notes</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        foreach ($recentRx as $rx) {
            $html .= '<tr><td>' . e($rx['customer_name']) . '</td><td>' . e(mb_substr((string)$rx['notes'], 0, 80)) . '</td><td>' . status_badge((string)$rx['status']) . '</td><td>' . e(dt($rx['created_at'])) . '</td></tr>';
        }
        $html .= '</tbody></table></div></div>';
    }

    render_layout($d['name'], 'doctors', $html);
}

/* ------------------------------------------------------------------ */
/* Delete / toggle                                                     */
/* ------------------------------------------------------------------ */
public static function doctors_delete(int $id): void
{
    verify_csrf_post();
    $d = self::doctors_find($id);
    $refCount = (int)qval('SELECT COUNT(*) FROM doctor_referrals WHERE doctor_id = ?', [$id]);

    if ($refCount > 0) {
        flash('error', '“' . $d['name'] . '” cannot be deleted because ' . $refCount . ' referral record(s) exist. Set the doctor to Inactive instead to preserve referral history.');
        aredirect('doctors');
    }
    q('DELETE FROM coupons WHERE doctor_id = ?', [$id]);
    q('DELETE FROM doctors WHERE id = ?', [$id]);
    log_action('doctor.delete', 'doctor', $id, $d['name']);
    flash_redirect('success', 'Doctor “' . $d['name'] . '” deleted.', 'doctors');
}

public static function doctors_toggle(int $id): void
{
    verify_csrf_post();
    $d = self::doctors_find($id);
    $new = $d['status'] === 'active' ? 'inactive' : 'active';
    q('UPDATE doctors SET status = ? WHERE id = ?', [$new, $id]);
    // keep the doctor's coupons in sync with the account status
    q('UPDATE coupons SET active = ? WHERE doctor_id = ?', [$new === 'active' ? 1 : 0, $id]);
    log_action('doctor.toggle', 'doctor', $id, $d['name'] . ' → ' . $new);
    flash_redirect('success', $d['name'] . ' is now ' . $new . '.', 'doctors');
}
}
