# Conversion and verification notes

- Source analyzed: supplied `bherb_react_clone.zip`, including `src/App.jsx`, `src/data.js`, the original global styles, storefront component/page inventory, and the built CSS bundle.
- Catalog: 64 product records extracted from `src/data.js` into `database/products.json`.
- Styling: original `src/styles.css`, `src/tw.css`, and admin CSS retained in the package; additional PHP storefront styles are in `assets/css/site-php.css`. The storefront loads the original global stylesheet and its PHP-specific additions; Tailwind source imports are not loaded because they require a build step.
- Validation performed: PHP syntax check (`php -l index.php`) and local PHP server homepage HTTP smoke test (200 response).
- Not validated: full browser visual comparison at multiple viewport sizes, every route under Apache rewrite, payment callbacks, production auth, Supabase-backed data, and the original admin CRUD workflows. Therefore this package should be considered a functional storefront conversion starter, not a verified pixel-perfect or full-feature-parity conversion.

- Modular homepage build: `index.php` routes into `includes/pages/*` (buffered so each view can set its title), with shared chrome in `includes/header.php` and `includes/footer.php`; homepage blocks are `includes/sections/*.php` and cart/free-shipping markup is rendered from `includes/partials/*.php` both server-side and from `api/cart.php` for AJAX repaints.
- Added `includes/.htaccess` (deny direct web access), `api/search.php` (autocomplete JSON), `api/cart.php` (cart/wishlist JSON), `assets/css/home.css` and `assets/js/home.js`. Swiper.js 11 is loaded from jsDelivr for the hero slider; `assets/js/site.js` keeps a CSS-only fallback for offline use.
- Verified with `php -l` on every PHP file plus a `php -S` smoke test covering the homepage, product, sub-category, deals, bestsellers, search, cart, checkout, order-success, wishlist, account, 404 routes and both JSON endpoints (no PHP warnings/notices in the responses).
