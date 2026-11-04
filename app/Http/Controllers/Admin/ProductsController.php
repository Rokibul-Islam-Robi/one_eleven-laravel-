<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminValidator as Validator;
use App\Support\AdminAuth;
use App\Services\OrderService;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerLog;
use Illuminate\Support\Facades\DB;
use DateTime;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Products & Categories — tabbed module: products, categories, brands.
 */
final class ProductsController extends Controller
{


public static function mod_products(string $action, int $id): void
{
    $tab = getq('tab', 'products');

    /* category sub-actions */
    if (in_array($action, ['cat_new', 'cat_create', 'cat_edit', 'cat_update', 'cat_delete'], true)) {
        require_module('categories', $action === 'cat_delete' ? 'delete' : 'edit');
        self::products_categories($action, $id);
        return;
    }
    /* brand sub-actions */
    if (in_array($action, ['brand_new', 'brand_create', 'brand_edit', 'brand_update', 'brand_delete'], true)) {
        require_module('brands', $action === 'brand_delete' ? 'delete' : 'edit');
        self::products_brands($action, $id);
        return;
    }

    switch ($action) {
        case 'new':
            require_module('products', 'edit');
            self::products_form(null);
            break;
        case 'create':
            require_module('products', 'edit');
            self::products_save(null);
            break;
        case 'edit':
            require_module('products', 'edit');
            $product = self::products_find($id);
            if ($product['seller_id']) {
                flash_redirect('error', 'Seller products must use the moderation workflow.', 'products/review/'.$id);
            }
            self::products_form($product);
            break;
        case 'update':
            require_module('products', 'edit');
            $product = self::products_find($id);
            if ($product['seller_id']) {
                flash_redirect('error', 'Seller products must use the moderation workflow.', 'products/review/'.$id);
            }
            self::products_save($product);
            break;
        case 'delete':
            require_module('products', 'delete');
            self::products_delete($id);
            break;
        case 'stock':
            require_module('products', 'edit');
            self::products_stock($id);
            break;
        case 'review-queue':
            require_module('products', 'view');
            self::products_review_queue();
            break;
        case 'review':
            require_module('products', 'edit');
            self::products_review($id);
            break;
        case 'approve':
            require_module('products', 'edit');
            self::products_review_decision($id, 'approved');
            break;
        case 'reject':
            require_module('products', 'edit');
            self::products_review_decision($id, 'rejected');
            break;
        default:
            if ($tab === 'categories') { require_module('categories'); self::products_tab_categories(); }
            elseif ($tab === 'brands') { require_module('brands'); self::products_tab_brands(); }
            else { self::products_tab_list(); }
    }
}

public static function products_tab_nav(string $current, int $pCats = 0, int $pBrands = 0, int $pSellerReviews = 0): string
{
    $pSellerReviews = $pSellerReviews ?: (int)qval("SELECT COUNT(*) FROM products WHERE seller_id IS NOT NULL AND approval_status = 'pending'");
    $tabs = [
        'products' => ['Products', aurl('products'), 0],
        'seller-submissions' => ['Seller submissions', aurl('products/review-queue'), $pSellerReviews],
        'categories' => ['Categories', aurl('products', ['tab' => 'categories']), $pCats],
        'brands' => ['Brands', aurl('products', ['tab' => 'brands']), $pBrands],
    ];
    $html = '<div class="adm-tabs">';
    foreach ($tabs as $key => [$label, $url, $cnt]) {
        $html .= '<a href="' . e($url) . '" class="' . ($key === $current ? 'on' : '') . '">' . e($label)
            . ($cnt ? ' <span class="cnt">' . (int)$cnt . '</span>' : '') . '</a>';
    }
    return $html . '</div>';
}

public static function products_find(int $id): array
{
    $row = qrow('SELECT * FROM products WHERE id = ?', [$id]);
    if (!$row) {
        adm_status(404);
        render_layout('Product not found', 'products', '<div class="adm-card adm-card-pad adm-empty"><h3>Product not found</h3><a class="abtn primary mt-3" href="' . e(aurl('products')) . '">Back</a></div>');
    }
    return $row;
}

/* ------------------------------------------------------------------ */
/* Products tab                                                        */
/* ------------------------------------------------------------------ */
public static function products_tab_list(): void
{
    $q = getq('q');
    $cat = getq('category');
    $stock = getq('stock');
    $approval = getq('approval');
    $page = pint(getq('page', '1'));

    $where = '1=1';
    $params = [];
    if ($q !== '') {
        $where .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR b.name LIKE ?)';
        $like = like($q);
        array_push($params, $like, $like, $like);
    }
    if ($cat !== '' && ctype_digit($cat)) {
        $where .= ' AND p.category_id = ?';
        $params[] = (int)$cat;
    }
    if ($stock === 'low') { $where .= ' AND p.stock > 0 AND p.stock <= p.low_stock_threshold'; }
    elseif ($stock === 'out') { $where .= ' AND p.stock = 0'; }
    if (in_array($approval, ['draft', 'pending', 'approved', 'rejected'], true)) {
        $where .= ' AND p.approval_status = ?';
        $params[] = $approval;
    }

    $total = (int)qval("SELECT COUNT(*) FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN sellers s ON s.id = p.seller_id WHERE $where", $params);
    [$limitSql, , $pager] = paginate($total, $page, (int)config('admin.per_page'));
    $rows = qall(
        "SELECT p.*, c.name AS category_name, b.name AS brand_name, s.shop_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN brands b ON b.id = p.brand_id
         LEFT JOIN sellers s ON s.id = p.seller_id
         WHERE $where ORDER BY p.id DESC $limitSql",
        $params
    );
    $catCount = (int)qval('SELECT COUNT(*) FROM categories');
    $brandCount = (int)qval('SELECT COUNT(*) FROM brands');
    $pendingSellerCount = (int)qval("SELECT COUNT(*) FROM products WHERE seller_id IS NOT NULL AND approval_status = 'pending'");
    $catOptions = ['' => 'All categories'];
    foreach (qall('SELECT id, name FROM categories ORDER BY name') as $c) { $catOptions[$c['id']] = $c['name']; }

    $actions = can('products', 'edit')
        ? '<a class="abtn primary" href="' . e(aurl('products/new')) . '">' . icon('plus') . ' Add Product</a>' : '';

    $html = page_head('Products & Categories', 'Manage the catalogue sold on the storefront.', $actions);
    $html .= self::products_tab_nav('products', $catCount, $brandCount, $pendingSellerCount);
    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([
        filter_select('category', 'Category', $catOptions, $cat),
        filter_select('stock', 'Stock', ['' => 'All', 'low' => 'Low stock', 'out' => 'Out of stock'], $stock),
        filter_select('approval', 'Review status', ['' => 'All', 'draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'], $approval),
    ], 'Search product name, SKU or brand…', $q);

    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('products') . '</div><h3>No products found</h3><p>' . ($q || $cat || $stock ? 'Nothing matches the current filters.' : 'Add your first product.') . '</p></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Product</th><th>Category / Brand</th><th>Seller / Review</th><th>SKU</th><th class="num">Price</th><th class="num">Stock</th><th>Flags</th><th></th></tr></thead><tbody>';
        foreach ($rows as $p) {
            $flags = [];
            if ($p['featured']) { $flags[] = '<span class="abadge blue">Featured</span>'; }
            if ($p['bestseller']) { $flags[] = '<span class="abadge green">Bestseller</span>'; }
            if ($p['trending']) { $flags[] = '<span class="abadge purple">Trending</span>'; }
            if ($p['new_arrival']) { $flags[] = '<span class="abadge amber">New</span>'; }
            $stockCls = $p['stock'] === 0 ? 'low' : ($p['stock'] <= $p['low_stock_threshold'] ? 'mid' : '');
            $html .= '<tr>'
                . '<td><div style="display:flex;gap:10px;align-items:center">'
                . ($p['image'] ? '<img class="adm-td-thumb" src="' . e($p['image']) . '" alt="" loading="lazy">' : '')
                . '<div><span class="cell-main">' . e($p['name']) . '</span><div class="cell-sub">#' . (int)$p['id'] . '</div></div></div></td>'
                . '<td>' . e($p['category_name'] ?: '—') . '<div class="cell-sub">' . e($p['brand_name'] ?: '—') . '</div></td>'
                . '<td>' . e($p['shop_name'] ?: 'BHerb') . '<div class="cell-sub">' . e(ucfirst($p['approval_status'])) . '</div></td>'
                . '<td><span class="code-chip">' . e($p['sku'] ?: '—') . '</span></td>'
                . '<td class="num"><b>' . amoney($p['price']) . '</b>'
                . ((float)$p['old_price'] > 0 ? '<div class="cell-sub"><del>' . amoney($p['old_price']) . '</del></div>' : '') . '</td>'
                . '<td class="num"><b>' . (int)$p['stock'] . '</b><span class="stock-bar ' . $stockCls . '"><i style="width:' . min(100, (int)$p['stock']) . '%"></i></span></td>'
                . '<td>' . ($flags ? implode(' ', $flags) : '<span class="cell-sub">—</span>') . '</td>'
                . '<td class="actions-cell">';
            if ($p['seller_id'] && $p['approval_status'] === 'pending') {
                $html .= '<a class="abtn secondary xs" href="' . e(aurl('products/review/' . (int)$p['id'])) . '">Review</a>';
            } elseif (can('products', 'edit') && ! $p['seller_id']) {
                $html .= '<a class="abtn secondary xs" href="' . e(aurl('products/edit/' . (int)$p['id'])) . '">Edit</a>';
            }
            if (can('products', 'delete') && ! $p['seller_id']) {
                $html .= '<form method="post" action="' . e(aurl('products/delete/' . (int)$p['id'])) . '" class="d-inline"' . confirm_delete_js() . '>' . csrf_field()
                    . '<button class="abtn danger-soft xs" type="submit">Delete</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= render_pager($pager, array_filter(['q' => $q, 'category' => $cat, 'stock' => $stock, 'approval' => $approval]));
    }
    $html .= '</div>';

    render_layout('Products', 'products', $html);
}

public static function products_review_queue(): void
{
    $page = pint(getq('page', '1'));
    $total = (int)qval("SELECT COUNT(*) FROM products WHERE seller_id IS NOT NULL AND approval_status = 'pending'");
    [$limitSql, , $pager] = paginate($total, $page, (int)config('admin.per_page'));
    $rows = qall(
        "SELECT p.id, p.name, p.sku, p.price, p.stock, p.submitted_at, s.shop_name, s.owner_name
         FROM products p JOIN sellers s ON s.id = p.seller_id
         WHERE p.approval_status = 'pending'
         ORDER BY p.submitted_at ASC, p.id ASC $limitSql"
    );

    $html = page_head('Seller product submissions', 'Review submitted seller products before they appear in the storefront.');
    $html .= self::products_tab_nav('seller-submissions');
    $html .= '<div class="adm-card"><div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Product</th><th>Seller</th><th>SKU</th><th class="num">Price</th><th class="num">Stock</th><th>Submitted</th><th></th></tr></thead><tbody>';
    foreach ($rows as $product) {
        $html .= '<tr><td><span class="cell-main">'.e($product['name']).'</span><div class="cell-sub">#'.(int)$product['id'].'</div></td>'
            .'<td>'.e($product['shop_name']).'<div class="cell-sub">'.e($product['owner_name']).'</div></td>'
            .'<td>'.e($product['sku'] ?: '—').'</td>'
            .'<td class="num">'.amoney($product['price']).'</td>'
            .'<td class="num">'.(int)$product['stock'].'</td>'
            .'<td>'.e($product['submitted_at'] ?: '—').'</td>'
            .'<td><a class="abtn secondary xs" href="'.e(aurl('products/review/'.(int)$product['id'])).'">Review</a></td></tr>';
    }
    if (! $rows) {
        $html .= '<tr><td colspan="7" class="text-center text-muted py-4">No seller products are waiting for review.</td></tr>';
    }
    $html .= '</tbody></table></div>'.render_pager($pager, []).'</div>';
    render_layout('Seller product submissions', 'products', $html, true);
}

public static function products_review(int $id): void
{
    $product = self::products_find($id);
    if (! $product['seller_id']) {
        flash_redirect('error', 'Only seller-submitted products use this review page.', 'products');
    }
    $seller = qrow(
        'SELECT id, shop_name, owner_name, email, status, application_status, is_active, email_verified_at FROM sellers WHERE id = ?',
        [(int)$product['seller_id']]
    );
    if (! $seller) {
        adm_status(404);
        render_layout('Seller not found', 'products', '<div class="adm-card adm-card-pad"><h3>Seller account not found.</h3></div>');
    }
    $category = $product['category_id'] ? qrow('SELECT name FROM categories WHERE id = ?', [(int)$product['category_id']]) : null;
    $brand = $product['brand_id'] ? qrow('SELECT name FROM brands WHERE id = ?', [(int)$product['brand_id']]) : null;
    $status = (string)$product['approval_status'];
    $html = '<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Review seller product</h1><p class="text-muted mb-0">Product #'.(int)$product['id'].'</p></div><a class="abtn" href="'.e(aurl('products/review-queue')).'">Back to submissions</a></div>';
    $html .= '<div class="adm-card adm-card-pad"><div class="row g-3">';
    foreach ([
        'Product name' => $product['name'],
        'Seller shop' => $seller['shop_name'],
        'Seller owner' => $seller['owner_name'],
        'Seller email' => $seller['email'],
        'Seller status' => $seller['status'].' / '.$seller['application_status'].($seller['is_active'] ? '' : ' / inactive'),
        'Email verification' => $seller['email_verified_at'] ? 'Verified' : 'Unverified',
        'Category' => $category['name'] ?? '—',
        'Brand' => $brand['name'] ?? '—',
        'SKU' => $product['sku'] ?: '—',
        'Price' => amoney($product['price']),
        'Previous price' => (float)$product['old_price'] > 0 ? amoney($product['old_price']) : '—',
        'Stock' => (string)(int)$product['stock'],
        'Low-stock threshold' => (string)(int)$product['low_stock_threshold'],
        'Prescription required' => $product['requires_prescription'] ? 'Yes' : 'No',
        'Moderation status' => ucfirst($status),
        'Submitted' => $product['submitted_at'] ?: '—',
    ] as $label => $value) {
        $html .= '<div class="col-md-4"><small class="text-muted d-block">'.e($label).'</small><strong>'.e((string)$value).'</strong></div>';
    }
    if ($product['image']) {
        $html .= '<div class="col-12"><img src="'.e($product['image']).'" alt="" style="max-width:220px;max-height:220px;object-fit:contain"></div>';
    }
    $html .= '<div class="col-12"><small class="text-muted d-block">Short description</small>'.nl2br(e((string)$product['short_description'])).'</div>'
        .'<div class="col-12"><small class="text-muted d-block">Product details</small>'.nl2br(e((string)$product['description'])).'</div></div></div>';

    if ($status === 'pending') {
        $sellerEligible = $seller['status'] === 'active' && $seller['application_status'] === 'approved'
            && (bool)$seller['is_active'] && ! empty($seller['email_verified_at']);
        $html .= '<div class="adm-card adm-card-pad mt-3"><h2 class="h5">Moderation decision</h2>';
        if (! $sellerEligible) {
            $html .= '<p class="text-warning">This seller is not currently active and verified. Do not approve products until the seller account is eligible.</p>';
        } else {
            $html .= '<form method="post" action="'.e(aurl('products/approve/'.$id)).'" class="d-inline">'.csrf_field().'<button class="btn btn-success" type="submit">Approve and publish</button></form>';
        }
        $html .= '<form method="post" action="'.e(aurl('products/reject/'.$id)).'" class="mt-3">'.csrf_field()
            .'<label class="form-label" for="rejection-reason">Rejection reason</label>'
            .'<textarea class="form-control" id="rejection-reason" name="reason" maxlength="255" required></textarea>'
            .'<button class="btn btn-outline-danger mt-2" type="submit">Reject product</button></form></div>';
    } elseif ($status === 'rejected' && $product['rejection_reason']) {
        $html .= '<div class="adm-card adm-card-pad mt-3"><strong>Rejection reason:</strong> '.e($product['rejection_reason']).'</div>';
    }

    render_layout('Review seller product', 'products', $html, true);
}

public static function products_review_decision(int $id, string $decision): void
{
    verify_csrf_post();
    $reason = $decision === 'rejected'
        ? trim((string)request()->validate(['reason' => ['required', 'string', 'max:255']])['reason'])
        : null;
    if ($decision === 'rejected' && $reason === '') {
        flash_redirect('error', 'Provide a reason for rejecting this product.', 'products/review/'.$id);
    }

    $adminId = (int)(session('admin_user.id') ?? 0);
    $updated = DB::transaction(function () use ($id, $decision, $reason, $adminId) {
        $product = Product::query()->lockForUpdate()->find($id);
        if (! $product || ! $product->seller_id || $product->approval_status !== 'pending') {
            return null;
        }
        $seller = Seller::query()->find($product->seller_id);
        if (! $seller) {
            return null;
        }
        if ($decision === 'approved'
            && ($seller->status !== 'active' || $seller->application_status !== 'approved'
                || ! $seller->is_active || ! $seller->email_verified_at)) {
            return false;
        }

        $approved = $decision === 'approved';
        $product->forceFill([
            'approval_status' => $decision,
            'rejection_reason' => $reason,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'is_active' => $approved,
            'updated_at' => now(),
        ])->save();
        SellerLog::query()->create([
            'seller_id' => $seller->id,
            'actor_type' => 'admin',
            'actor_id' => $adminId,
            'action' => 'admin.product.'.$decision,
            'detail' => $reason ? 'Product #'.$product->id.' rejected; reason recorded.' : 'Product #'.$product->id.' approved.',
            'ip' => substr((string)request()->ip(), 0, 45),
            'created_at' => now(),
        ]);

        return $seller->id;
    });

    if ($updated === false) {
        flash_redirect('error', 'This seller is no longer active and verified; the product was not approved.', 'products/review/'.$id);
    }
    if ($updated === null) {
        flash_redirect('error', 'This product is no longer awaiting review.', 'products/review-queue');
    }
    log_action('seller_product.'.$decision, 'product', $id, $reason ? 'Rejection reason recorded.' : null);
    flash_redirect('success', $decision === 'approved' ? 'Seller product approved and published.' : 'Seller product rejected.', 'products/review/'.$id);
}

public static function products_form(?array $p, array $errors = [], array $old = []): void
{
    $isEdit = $p !== null;
    $v = static function (string $key, $default = '') use ($p, $old) {
        if ($old) { return (string)($old[$key] ?? $default); }
        return (string)($p[$key] ?? $default);
    };
    $flag = static function (string $key) use ($p, $old) {
        if ($old) { return !empty($old[$key]); }
        return !empty($p[$key]);
    };
    $err = static fn(string $k): string => isset($errors[$k]) ? ' err' : '';
    $errmsg = static fn(string $k): string => isset($errors[$k]) ? '<div class="aerr">' . e($errors[$k]) . '</div>' : '';

    $cats = qall('SELECT id, name FROM categories ORDER BY name');
    $brands = qall('SELECT id, name FROM brands ORDER BY name');

    $html = page_head($isEdit ? 'Edit Product' : 'Add Product', $isEdit ? 'Update product #' . (int)$p['id'] : 'Create a new catalogue product.',
        '<a class="abtn ghost" href="' . e(aurl('products')) . '">← Back</a>');
    $html .= '<div class="adm-card" style="max-width:900px"><div class="adm-card-pad"><form method="post" action="' . e(aurl($isEdit ? 'products/update' : 'products/create')) . '" novalidate>' . csrf_field();
    if ($isEdit) { $html .= '<input type="hidden" name="id" value="' . (int)$p['id'] . '">'; }

    $html .= '<div class="afield"><label>Product name *</label><input class="ainput' . $err('name') . '" name="name" value="' . e($v('name')) . '" required>' . $errmsg('name') . '</div>';
    $html .= '<div class="agrid-2">';
    $html .= '<div class="afield"><label>Category</label><select class="aselect" name="category_id"><option value="">— none —</option>';
    foreach ($cats as $c) { $html .= '<option value="' . (int)$c['id'] . '"' . ($v('category_id') === (string)$c['id'] ? ' selected' : '') . '>' . e($c['name']) . '</option>'; }
    $html .= '</select></div>';
    $html .= '<div class="afield"><label>Brand</label><select class="aselect" name="brand_id"><option value="">— none —</option>';
    foreach ($brands as $b) { $html .= '<option value="' . (int)$b['id'] . '"' . ($v('brand_id') === (string)$b['id'] ? ' selected' : '') . '>' . e($b['name']) . '</option>'; }
    $html .= '</select></div></div>';

    $html .= '<div class="agrid-3">';
    $html .= '<div class="afield"><label>SKU</label><input class="ainput' . $err('sku') . '" name="sku" value="' . e($v('sku')) . '">' . $errmsg('sku') . '<div class="hint">Must be unique.</div></div>';
    $html .= '<div class="afield"><label>Price * </label><input class="ainput' . $err('price') . '" type="number" step="0.01" min="0" name="price" value="' . e($v('price', '0')) . '" required>' . $errmsg('price') . '</div>';
    $html .= '<div class="afield"><label>Old price <span class="hint">(for strikethrough)</span></label><input class="ainput' . $err('old_price') . '" type="number" step="0.01" min="0" name="old_price" value="' . e($v('old_price')) . '">' . $errmsg('old_price') . '</div>';
    $html .= '</div><div class="agrid-3">';
    $html .= '<div class="afield"><label>Stock</label><input class="ainput' . $err('stock') . '" type="number" min="0" name="stock" value="' . e($v('stock', '0')) . '">' . $errmsg('stock') . '</div>';
    $html .= '<div class="afield"><label>Low-stock threshold</label><input class="ainput' . $err('low_stock_threshold') . '" type="number" min="0" name="low_stock_threshold" value="' . e($v('low_stock_threshold', '10')) . '"></div>';
    $html .= '<div class="afield"><label>Badge</label><input class="ainput" name="badge" value="' . e($v('badge')) . '" placeholder="e.g. 20% OFF"></div>';
    $html .= '</div>';
    $html .= '<div class="afield"><label>Image URL</label><input class="ainput' . $err('image') . '" name="image" value="' . e($v('image')) . '" placeholder="https://…">' . $errmsg('image') . '</div>';
    $html .= '<div class="afield"><label>Short description</label><input class="ainput" name="short_description" value="' . e($v('short_description')) . '"></div>';
    $html .= '<div class="afield"><label>Description</label><textarea class="atextarea" name="description" rows="4">' . e($v('description')) . '</textarea></div>';

    $html .= '<div class="afield"><label>Storefront flags</label><div class="d-flex flex-wrap gap-3" style="font-size:13px;font-weight:600">';
    foreach (['featured' => 'Featured', 'bestseller' => 'Bestseller', 'trending' => 'Trending', 'new_arrival' => 'New arrival'] as $k => $lbl) {
        $html .= '<label class="d-flex align-items-center gap-2"><input type="checkbox" class="adm-check" name="' . $k . '" value="1"' . ($flag($k) ? ' checked' : '') . '> ' . e($lbl) . '</label>';
    }
    $html .= '</div></div>';

    $html .= '<div class="form-actions"><button class="abtn primary" type="submit">' . ($isEdit ? 'Save changes' : 'Create product') . '</button><a class="abtn ghost" href="' . e(aurl('products')) . '">Cancel</a></div>';
    $html .= '</form></div></div>';

    render_layout($isEdit ? 'Edit Product' : 'Add Product', 'products', $html);
}

public static function products_save(?array $existing): void
{
    verify_csrf_post();
    $v = new Validator(request()->post());
    $v->required('name', 'Product name')->max('name', 255, 'Product name')
      ->numeric('price', 'Price', false, 0)->numeric('old_price', 'Old price', true, 0)
      ->numeric('stock', 'Stock', true, 0)->numeric('low_stock_threshold', 'Threshold', true, 0)
      ->max('sku', 80, 'SKU')->max('image', 255, 'Image URL')->max('badge', 50, 'Badge');

    $sku = $v->str('sku');
    if ($sku !== '') {
        $dup = qval('SELECT id FROM products WHERE sku = ? AND id <> ?', [$sku, (int)($existing['id'] ?? 0)]);
        if ((int)$dup) { $v->errors['sku'] = 'This SKU is already used by another product.'; }
    }
    $img = $v->str('image');
    if ($img !== '' && !preg_match('#^https?://#i', $img) && !str_starts_with($img, 'assets/')) {
        $v->errors['image'] = 'Image must be a full http(s) URL or an assets/ path.';
    }

    if ($v->fails()) {
        flash('error', 'Please correct the highlighted fields.');
        self::products_form($existing, $v->errors, request()->post());
        return;
    }

    $name = $v->str('name');
    $slug = slugify($name);
    $slugBase = $slug;
    $n = 1;
    while ((int)qval('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, (int)($existing['id'] ?? 0)])) {
        $slug = $slugBase . '-' . (++$n);
    }

    $data = [
        'name' => $name,
        'slug' => $slug,
        'category_id' => $v->int('category_id') ?: null,
        'brand_id' => $v->int('brand_id') ?: null,
        'sku' => $sku ?: null,
        'price' => number_format($v->float('price', 0), 2, '.', ''),
        'old_price' => $v->str('old_price') !== '' ? number_format($v->float('old_price', 0), 2, '.', '') : null,
        'stock' => $v->int('stock', 0),
        'low_stock_threshold' => $v->int('low_stock_threshold', 10),
        'badge' => $v->str('badge'),
        'image' => $img ?: null,
        'short_description' => $v->str('short_description') ?: '',
        'description' => $v->str('description') ?: null,
        'featured' => $v->bool('featured') ? 1 : 0,
        'bestseller' => $v->bool('bestseller') ? 1 : 0,
        'trending' => $v->bool('trending') ? 1 : 0,
        'new_arrival' => $v->bool('new_arrival') ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($existing) {
        update_row('products', $data, (int)$existing['id']);
        log_action('product.update', 'product', (int)$existing['id'], $name);
        flash_redirect('success', 'Product “' . $name . '” updated.', 'products');
    } else {
        $id = insert_row('products', $data);
        log_action('product.create', 'product', $id, $name);
        flash_redirect('success', 'Product “' . $name . '” created.', 'products');
    }
}

public static function products_delete(int $id): void
{
    verify_csrf_post();
    $p = self::products_find($id);
    if ($p['seller_id']) {
        flash_redirect('error', 'Seller-owned products cannot be deleted from the catalogue manager.', 'products/review/'.$id);
    }
    $used = (int)qval('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$id]);
    if ($used > 0) {
        flash('error', '“' . $p['name'] . '” is referenced by ' . $used . ' order item(s) and cannot be deleted. Set stock to 0 instead.');
        aredirect('products');
    }
    q('DELETE FROM products WHERE id = ?', [$id]);
    log_action('product.delete', 'product', $id, $p['name']);
    flash_redirect('success', 'Product deleted.', 'products');
}

public static function products_stock(int $id): void
{
    verify_csrf_post();
    $p = self::products_find($id);
    $stock = max(0, (int)(request()->post('stock') ?? 0));
    if ($p['seller_id']) {
        DB::transaction(function () use ($p, $stock, $id) {
            $delta = $stock - (int)$p['stock'];
            q('UPDATE products SET stock = ?, updated_at = ? WHERE id = ?', [$stock, date('Y-m-d H:i:s'), $id]);
            $updated = q('UPDATE catalog_stock SET stock = ? WHERE json_id = ?', [$stock, (int)$p['json_id']])->rowCount();
            if ($updated === 0 && ! qval('SELECT COUNT(*) FROM catalog_stock WHERE json_id = ?', [(int)$p['json_id']])) {
                throw new RuntimeException('The seller product stock mapping is missing.');
            }
            if ($delta !== 0) {
                q('INSERT INTO stock_movements (product_id, order_id, delta, type, note, created_at) VALUES (?,NULL,?,?,?,?)', [
                    (int)$p['json_id'],
                    $delta,
                    'manual_adjust',
                    'Admin stock adjustment',
                    date('Y-m-d H:i:s'),
                ]);
            }
        });
    } else {
        q('UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?', [$stock, $id]);
    }
    log_action('product.stock', 'product', $id, $p['name'] . ' → ' . $stock);
    flash_redirect('success', 'Stock for “' . $p['name'] . '” set to ' . $stock . '.', 'products');
}

/* ------------------------------------------------------------------ */
/* Categories tab                                                      */
/* ------------------------------------------------------------------ */
public static function products_tab_categories(): void
{
    $q = getq('q');
    $where = '1=1';
    $params = [];
    if ($q !== '') { $where = 'c.name LIKE ? OR c.slug LIKE ?'; $params = [like($q), like($q)]; }
    $rows = qall(
        "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
         FROM categories c WHERE $where ORDER BY c.name",
        $params
    );

    $actions = can('categories', 'edit')
        ? '<a class="abtn primary" href="' . e(aurl('products/cat_new')) . '">' . icon('plus') . ' Add Category</a>' : '';

    $html = page_head('Products & Categories', 'Categories shown in storefront navigation and filters.', $actions);
    $html .= self::products_tab_nav('categories', count($rows), (int)qval('SELECT COUNT(*) FROM brands'));
    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([], 'Search category name or slug…', $q);
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('content') . '</div><h3>No categories</h3></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Category</th><th>Slug</th><th class="num">Products</th><th></th></tr></thead><tbody>';
        foreach ($rows as $c) {
            $html .= '<tr><td><span class="cell-main">' . e($c['name']) . '</span></td>'
                . '<td><span class="code-chip">' . e($c['slug']) . '</span></td>'
                . '<td class="num">' . (int)$c['product_count'] . '</td><td class="actions-cell">';
            if (can('categories', 'edit')) {
                $html .= '<a class="abtn secondary xs" href="' . e(aurl('products/cat_edit/' . (int)$c['id'])) . '">Edit</a>';
            }
            if (can('categories', 'delete')) {
                $html .= '<form method="post" action="' . e(aurl('products/cat_delete/' . (int)$c['id'])) . '" class="d-inline"' . confirm_delete_js() . '>' . csrf_field()
                    . '<button class="abtn danger-soft xs" type="submit">Delete</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div>';
    render_layout('Categories', 'products', $html);
}

public static function products_categories(string $action, int $id): void
{
    if (!in_array($action, ['cat_new', 'cat_edit'], true)) { verify_csrf_post(); }
    if ($action === 'cat_delete') {
        $c = qrow('SELECT * FROM categories WHERE id = ?', [$id]);
        if (!$c) { adm_die(404, 'Category not found'); }
        $used = (int)qval('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]);
        if ($used > 0) {
            flash('error', 'Category “' . $c['name'] . '” is used by ' . $used . ' product(s) and cannot be deleted.');
            aredirect('products', ['tab' => 'categories']);
        }
        q('DELETE FROM categories WHERE id = ?', [$id]);
        log_action('category.delete', 'category', $id, $c['name']);
        flash_redirect('success', 'Category deleted.', 'products', ['tab' => 'categories']);
    }

    $isEdit = $action === 'cat_edit' || $action === 'cat_update';
    $c = $isEdit ? qrow('SELECT * FROM categories WHERE id = ?', [$id]) : null;
    if ($isEdit && !$c) { adm_die(404, 'Category not found'); }

    if (in_array($action, ['cat_new', 'cat_edit'], true)) {
        $html = page_head($isEdit ? 'Edit Category' : 'Add Category', '', '<a class="abtn ghost" href="' . e(aurl('products', ['tab' => 'categories'])) . '">← Back</a>');
        $html .= '<div class="adm-card" style="max-width:560px"><div class="adm-card-pad"><form method="post" action="' . e(aurl('products/' . ($isEdit ? 'cat_update' : 'cat_create'))) . '">' . csrf_field();
        if ($isEdit) { $html .= '<input type="hidden" name="id" value="' . (int)$c['id'] . '">'; }
        $html .= '<div class="afield"><label>Name *</label><input class="ainput" name="name" value="' . e($c['name'] ?? '') . '" required></div>';
        $html .= '<div class="afield"><label>Slug <span class="hint">(leave blank to auto-generate)</span></label><input class="ainput" name="slug" value="' . e($c['slug'] ?? '') . '"></div>';
        $html .= '<div class="afield"><label>Icon / CSS class</label><input class="ainput" name="icon" value="' . e($c['icon'] ?? '') . '" placeholder="i-cat-supplements"></div>';
        $html .= '<div class="form-actions"><button class="abtn primary">' . ($isEdit ? 'Save' : 'Create') . '</button><a class="abtn ghost" href="' . e(aurl('products', ['tab' => 'categories'])) . '">Cancel</a></div>';
        $html .= '</form></div></div>';
        render_layout($isEdit ? 'Edit Category' : 'Add Category', 'products', $html);
        return;
    }

    // create / update
    $name = post('name');
    if ($name === '') {
        flash('error', 'Category name is required.');
        aredirect('products', ['tab' => 'categories']);
    }
    $slug = post('slug') !== '' ? slugify(post('slug')) : slugify($name);
    $dup = (int)qval('SELECT id FROM categories WHERE (slug = ? OR name = ?) AND id <> ?', [$slug, $name, (int)(request()->post('id') ?? 0)]);
    if ($dup) {
        flash('error', 'A category with this name or slug already exists.');
        aredirect('products', ['tab' => 'categories']);
    }
    $data = ['name' => $name, 'slug' => $slug, 'icon' => post('icon') ?: '✣'];
    if ($action === 'cat_create') {
        $newId = insert_row('categories', $data);
        log_action('category.create', 'category', $newId, $name);
        flash_redirect('success', 'Category “' . $name . '” created.', 'products', ['tab' => 'categories']);
    }
    update_row('categories', $data, (int)request()->post('id'));
    log_action('category.update', 'category', (int)request()->post('id'), $name);
    flash_redirect('success', 'Category updated.', 'products', ['tab' => 'categories']);
}

/* ------------------------------------------------------------------ */
/* Brands tab                                                          */
/* ------------------------------------------------------------------ */
public static function products_tab_brands(): void
{
    $q = getq('q');
    $where = '1=1';
    $params = [];
    if ($q !== '') { $where = 'b.name LIKE ? OR b.slug LIKE ?'; $params = [like($q), like($q)]; }
    $rows = qall(
        "SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS product_count
         FROM brands b WHERE $where ORDER BY b.name",
        $params
    );

    $actions = can('brands', 'edit')
        ? '<a class="abtn primary" href="' . e(aurl('products/brand_new')) . '">' . icon('plus') . ' Add Brand</a>' : '';

    $html = page_head('Products & Categories', 'Brands used across the catalogue.', $actions);
    $html .= self::products_tab_nav('brands', (int)qval('SELECT COUNT(*) FROM categories'), count($rows));
    $html .= '<div class="adm-card">';
    $html .= filter_toolbar([], 'Search brand name or slug…', $q);
    if (!$rows) {
        $html .= '<div class="adm-empty"><div class="e-ic">' . icon('products') . '</div><h3>No brands</h3></div>';
    } else {
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th>Brand</th><th>Slug</th><th class="num">Products</th><th></th></tr></thead><tbody>';
        foreach ($rows as $b) {
            $html .= '<tr><td><span class="cell-main">' . e($b['name']) . '</span></td>'
                . '<td><span class="code-chip">' . e($b['slug']) . '</span></td>'
                . '<td class="num">' . (int)$b['product_count'] . '</td><td class="actions-cell">';
            if (can('brands', 'edit')) {
                $html .= '<a class="abtn secondary xs" href="' . e(aurl('products/brand_edit/' . (int)$b['id'])) . '">Edit</a>';
            }
            if (can('brands', 'delete')) {
                $html .= '<form method="post" action="' . e(aurl('products/brand_delete/' . (int)$b['id'])) . '" class="d-inline"' . confirm_delete_js() . '>' . csrf_field()
                    . '<button class="abtn danger-soft xs" type="submit">Delete</button></form>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    }
    $html .= '</div>';
    render_layout('Brands', 'products', $html);
}

public static function products_brands(string $action, int $id): void
{
    if (!in_array($action, ['brand_new', 'brand_edit'], true)) { verify_csrf_post(); }
    if ($action === 'brand_delete') {
        $b = qrow('SELECT * FROM brands WHERE id = ?', [$id]);
        if (!$b) { adm_die(404, 'Brand not found'); }
        $used = (int)qval('SELECT COUNT(*) FROM products WHERE brand_id = ?', [$id]);
        if ($used > 0) {
            flash('error', 'Brand “' . $b['name'] . '” is used by ' . $used . ' product(s) and cannot be deleted.');
            aredirect('products', ['tab' => 'brands']);
        }
        q('DELETE FROM brands WHERE id = ?', [$id]);
        log_action('brand.delete', 'brand', $id, $b['name']);
        flash_redirect('success', 'Brand deleted.', 'products', ['tab' => 'brands']);
    }

    $isEdit = $action === 'brand_edit' || $action === 'brand_update';
    $b = $isEdit ? qrow('SELECT * FROM brands WHERE id = ?', [$id]) : null;
    if ($isEdit && !$b) { adm_die(404, 'Brand not found'); }

    if (in_array($action, ['brand_new', 'brand_edit'], true)) {
        $html = page_head($isEdit ? 'Edit Brand' : 'Add Brand', '', '<a class="abtn ghost" href="' . e(aurl('products', ['tab' => 'brands'])) . '">← Back</a>');
        $html .= '<div class="adm-card" style="max-width:560px"><div class="adm-card-pad"><form method="post" action="' . e(aurl('products/' . ($isEdit ? 'brand_update' : 'brand_create'))) . '">' . csrf_field();
        if ($isEdit) { $html .= '<input type="hidden" name="id" value="' . (int)$b['id'] . '">'; }
        $html .= '<div class="afield"><label>Name *</label><input class="ainput" name="name" value="' . e($b['name'] ?? '') . '" required></div>';
        $html .= '<div class="afield"><label>Slug <span class="hint">(leave blank to auto-generate)</span></label><input class="ainput" name="slug" value="' . e($b['slug'] ?? '') . '"></div>';
        $html .= '<div class="form-actions"><button class="abtn primary">' . ($isEdit ? 'Save' : 'Create') . '</button><a class="abtn ghost" href="' . e(aurl('products', ['tab' => 'brands'])) . '">Cancel</a></div>';
        $html .= '</form></div></div>';
        render_layout($isEdit ? 'Edit Brand' : 'Add Brand', 'products', $html);
        return;
    }

    $name = post('name');
    if ($name === '') {
        flash('error', 'Brand name is required.');
        aredirect('products', ['tab' => 'brands']);
    }
    $slug = post('slug') !== '' ? slugify(post('slug')) : slugify($name);
    $dup = (int)qval('SELECT id FROM brands WHERE (slug = ? OR name = ?) AND id <> ?', [$slug, $name, (int)(request()->post('id') ?? 0)]);
    if ($dup) {
        flash('error', 'A brand with this name or slug already exists.');
        aredirect('products', ['tab' => 'brands']);
    }
    $data = ['name' => $name, 'slug' => $slug];
    if ($action === 'brand_create') {
        $newId = insert_row('brands', $data);
        log_action('brand.create', 'brand', $newId, $name);
        flash_redirect('success', 'Brand “' . $name . '” created.', 'products', ['tab' => 'brands']);
    }
    update_row('brands', $data, (int)request()->post('id'));
    log_action('brand.update', 'brand', (int)request()->post('id'), $name);
    flash_redirect('success', 'Brand updated.', 'products', ['tab' => 'brands']);
}
}
