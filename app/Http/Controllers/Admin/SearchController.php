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
 * Global search — grouped results across products, orders, customers, doctors.
 */
final class SearchController extends Controller
{


public static function mod_search(string $action, int $id): void
{
    $q = getq('q');
    $html = page_head('Search', $q !== '' ? 'Results for “' . $q . '”' : 'Type a keyword in the top search box.');
    $html .= '<div class="adm-card adm-card-pad mb-3"><form method="get" action="' . e(aurl('search')) . '" class="d-flex gap-2">'
        . '<input class="ainput" name="q" value="' . e($q) . '" placeholder="Search products, orders, customers, doctors…" autofocus>'
        . '<button class="abtn primary">Search</button></form></div>';

    if ($q === '') {
        render_layout('Search', 'search', $html);
        return;
    }
    $like = like($q);

    $groups = [];

    if (can('doctors')) {
        $groups['Doctors'] = [
            'url' => 'doctors/view/%d',
            'rows' => qall("SELECT id, name AS t, CONCAT_WS(' — ', specialty, phone, reference_code) AS s FROM doctors WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? OR reference_code LIKE ? OR registration_number LIKE ? LIMIT 8", [$like, $like, $like, $like, $like]),
        ];
    }
    if (can('orders')) {
        $groups['Orders'] = [
            'url' => 'orders/view/%d',
            'rows' => qall("SELECT o.id, CONCAT_WS(' — ', o.order_number, o.shipping_name, o.shipping_phone) AS t, CONCAT(o.status, ' · ', o.total) AS s FROM orders o WHERE o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ? OR o.tracking_code LIKE ? LIMIT 8", [$like, $like, $like, $like]),
        ];
    }
    if (can('products')) {
        $groups['Products'] = [
            'url' => 'products/edit/%d',
            'rows' => qall("SELECT id, name AS t, CONCAT_WS(' — ', sku, CAST(price AS CHAR), CONCAT(stock, ' in stock')) AS s FROM products WHERE name LIKE ? OR sku LIKE ? LIMIT 8", [$like, $like]),
        ];
    }
    if (can('customers')) {
        $groups['Customers'] = [
            'url' => 'customers/view/%d',
            'rows' => qall("SELECT id, name AS t, CONCAT_WS(' — ', email, phone) AS s FROM users WHERE role='customer' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?) LIMIT 8", [$like, $like, $like]),
        ];
    }
    if (can('doctor_coupons')) {
        $groups['Coupons'] = [
            'url' => 'doctor-coupons/edit/%d',
            'rows' => qall("SELECT id, code AS t, CONCAT(discount_percent, '% off') AS s FROM coupons WHERE code LIKE ? OR description LIKE ? LIMIT 8", [$like, $like]),
        ];
    }

    $found = 0;
    foreach ($groups as $label => $g) {
        $html .= '<div class="adm-card mb-3"><div class="adm-card-head"><h3>' . e($label) . ' <span class="cnt">' . count($g['rows']) . '</span></h3></div>';
        if (!$g['rows']) {
            $html .= '<div class="adm-card-pad" style="color:var(--a-muted);font-size:13px">No matches.</div>';
        } else {
            $found += count($g['rows']);
            $html .= '<ul class="mini-list adm-card-pad">';
            foreach ($g['rows'] as $r) {
                $html .= '<li><a class="grow" href="' . e(aurl(sprintf($g['url'], (int)$r['id']))) . '"><span class="t">' . e($r['t']) . '</span><span class="s">' . e($r['s']) . '</span></a></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div>';
    }

    if ($found === 0) {
        $html .= '<div class="adm-card adm-card-pad adm-empty"><div class="e-ic">' . icon('search') . '</div><h3>No results</h3><p>Nothing matched “' . e($q) . '”. Try a different keyword.</p></div>';
    }

    render_layout('Search', 'search', $html);
}
}
