<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Variables every storefront template expects (the original bootstrap.php globals). */
class StorefrontData
{
    public static function globals(): array
    {
        $req = request();
        $path = '/' . ltrim($req->path(), '/');
        $path = rtrim($path, '/') === '' ? '/' : $path;

        $snap = Cart::snapshot();
        $wishProducts = [];
        foreach (Cart::wish() as $wid) {
            if ($p = Catalog::byId($wid)) {
                $wishProducts[] = $p;
            }
        }
        $customerAuth = CustomerAuth::current();
        $customer = $customerAuth ? \App\Models\Customer::find((int) $customerAuth['id']) : null;
        $defaultAddress = $customer && Schema::hasTable('customer_addresses')
            ? DB::table('customer_addresses')->where('customer_id', $customer->id)->orderByDesc('is_default')->orderBy('id')->first()
            : null;
        $checkoutDefaults = [
            'name' => $defaultAddress->full_name ?? $customer?->full_name ?? '',
            'phone' => $defaultAddress->phone ?? $customer?->phone ?? '',
            'email' => $customer?->email ?? '',
            'area' => $defaultAddress->district ?? '',
            'address' => $defaultAddress
                ? implode(', ', array_filter([$defaultAddress->street_address, $defaultAddress->area]))
                : '',
            'note' => $defaultAddress->instructions ?? '',
        ];

        return [
            'products'         => Catalog::all(),
            'path'             => $path,
            'basePath'         => site_base_path(),
            'search'           => trim((string) $req->query('q', '')),
            'slug'             => basename(rtrim($path, '/')),
            'isAdmin'          => false,
            'cssV'             => '1.5.1',
            'jsV'              => '1.4.5',
            'siteTitle'        => 'bHerb — Your Everyday Wellness Destination',
            'siteDesc'         => 'Shop vitamins, supplements, beauty, sports nutrition and wellness products at bHerb.',
            'snap'             => $snap,
            'cartCount'        => $snap['count'],
            'cartTotal'        => $snap['total'],
            'cartProducts'     => $snap['items'],
            'wishProducts'     => $wishProducts,
            'wishCount'        => count($wishProducts),
            'authUser'         => $customerAuth,
            'checkoutDefaults' => $checkoutDefaults,
            'NAV_CATEGORIES'   => Navigation::categories(),
            'SUB_LABELS'       => Navigation::subLabels(),
            'SUB_PARENT'       => Navigation::subParent(),
            'CAT_LABELS'       => Navigation::catLabels(),
            'NAV_PRIMARY'      => Navigation::primary(),
            'NAV_GROUPS'       => Navigation::groups(),
            'TRENDING_BRANDS'  => Navigation::trendingBrands(),
            'REGIONS'          => Navigation::regions(),
            'NAV_MENUS'        => Navigation::menus(),
            'NAV_BRANDS'       => Navigation::brands(),
            'CURRENCIES'       => Navigation::currencies(),
            'LANGUAGES'        => Navigation::languages(),
        ];
    }
}
