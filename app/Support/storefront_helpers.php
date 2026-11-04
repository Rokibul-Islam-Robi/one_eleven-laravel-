<?php
/* Global template helpers kept under their original names so the converted
   Blade views read like the original includes. Guarded against redeclaration. */

use App\Support\Catalog;
use App\Support\Icons;
use App\Support\Render;

if (! function_exists('ic')) {
    function ic($k, $cls = 'ic') { return Icons::ic($k, $cls); }
}
if (! function_exists('pcard')) {
    function pcard($p): string { return Render::pcard($p); }
}
if (! function_exists('secHead')) {
    function secHead(string $title, string $tag, string $href): string { return Render::secHead($title, $tag, $href); }
}
if (! function_exists('price_html')) {
    function price_html(string $tag, string $value, string $cls = ''): string { return Render::priceHtml($tag, $value, $cls); }
}
if (! function_exists('brand_slug')) {
    function brand_slug(string $name): string { return Catalog::brandSlug($name); }
}
if (! function_exists('discPct')) {
    function discPct($p): int { return Catalog::discPct($p); }
}
if (! function_exists('dealText')) {
    function dealText($p): string { return Catalog::dealText($p); }
}
if (! function_exists('productById')) {
    function productById($id) { return Catalog::byId($id); }
}
if (! function_exists('product_potencies')) {
    function product_potencies($p): array { return Catalog::potencies($p); }
}
if (! function_exists('product_packs')) {
    function product_packs($p): array { return Catalog::packs($p); }
}
if (! function_exists('product_gallery')) {
    function product_gallery($p): array { return Catalog::gallery($p); }
}
if (! function_exists('variant_unit_price')) {
    function variant_unit_price($p, string $potency, int $pack = 1): float { return Catalog::variantUnitPrice($p, $potency, $pack); }
}
if (! function_exists('storefront_content')) {
    function storefront_content(string $key, array $defaults): array { return \App\Support\Content::get($key, $defaults); }
}

/* ---- thin wrappers so converted templates keep the original function names ---- */
if (! function_exists('brand_index')) {
    function brand_index(): array { return Catalog::brandIndex(); }
}
if (! function_exists('productsByBrand')) {
    function productsByBrand(string $slug): array { return Catalog::byBrand($slug); }
}
if (! function_exists('productsByCategory')) {
    function productsByCategory(string $cat): array { return Catalog::byCategory($cat); }
}
if (! function_exists('cart_snapshot')) {
    function cart_snapshot(): array { return \App\Support\Cart::snapshot(); }
}
if (! function_exists('auth_current_user')) {
    function auth_current_user(): ?array { return \App\Support\CustomerAuth::current(); }
}
if (! function_exists('auth_display_name')) {
    function auth_display_name(?array $user = null): string { return \App\Support\CustomerAuth::displayName($user); }
}
if (! function_exists('render_cart_body')) {
    function render_cart_body(): string { return Render::cartBody(); }
}
if (! function_exists('render_cart_foot')) {
    function render_cart_foot(): string { return Render::cartFoot(); }
}

/* Constants kept under their original names for the converted templates. */
if (! defined('BHERB_PROMO_CODE')) {
    define('BHERB_PROMO_CODE', 'BHERB20');
}
if (! defined('BHERB_FREE_SHIPPING_FROM')) {
    define('BHERB_FREE_SHIPPING_FROM', 5000);
}

/* ---- checkout helpers kept under their original names ---- */
if (! function_exists('bd_division_of')) {
    function bd_division_of(string $district): string { return \App\Support\Districts::divisionOf($district); }
}
