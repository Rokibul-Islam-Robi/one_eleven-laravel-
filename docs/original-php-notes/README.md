# bHerb PHP storefront

A Core PHP conversion of the supplied bHerb React project. The storefront uses PHP sessions for the cart and wishlist, includes a product catalog extracted from the supplied source data, and retains the original project's main CSS and original public image assets where present. Product image URLs in the source catalog are preserved; some images are served by Pexels/Unsplash and therefore require an internet connection.

## Requirements
- PHP 8.0+ (8.1+ recommended)
- Apache with `mod_rewrite` enabled (XAMPP works)
- No Node.js, npm, React, or build server is needed to run the PHP storefront.

## XAMPP installation
1. Extract the `bherb_php` folder into `C:\xampp\htdocs\bherb_php`.
2. Start Apache from the XAMPP Control Panel.
3. Open `http://localhost/bherb_php/`.
4. Make sure Apache allows `.htaccess` overrides (`AllowOverride All`) so clean routes such as `/product/1` work. If clean routes are unavailable, use `index.php` and configure Apache rewrite support.

## Included features
- Modular PHP templates: shared `header.php` / `footer.php`, homepage sections and page templates
- Top bar with rotating promotional banners plus language and currency selectors (client-side price conversion)
- Header with logo, autocomplete search (`api/search.php`), wishlist, account, and cart icon with a dynamic counter badge
- Multi-level navigation: category menus with sub-category mega panels, Deals / Health Topics / Brands dropdowns, and an accordion mobile menu
- Hero banner slider powered by Swiper.js (CDN) with arrows, pagination dots and a plain-CSS fallback
- "Shop by Category" circular icons, Deals / Trending / Best Sellers / New Arrivals product grids with discount tags, star ratings, prices and Add to Cart
- Slide-over cart drawer on the right: quantity steppers, remove, totals and checkout button, updated over `api/cart.php` (no-JS form posts still work)
- Footer with company links, customer service links, newsletter form and social icons
- Session-based wishlist, cart, local demo checkout, product detail, category/sub-category, deals, bestsellers and search pages
- Product catalog stored in `database/products.json`

## Project structure
```
index.php                  front controller (route -> view, header/footer chrome)
includes/
  bootstrap.php            session, helpers, catalog, cart, navigation tree, routes
  functions.php            product cards, section heads, cart drawer renderers, search
  actions.php              classic (no-JS) POST handlers
  header.php  footer.php   shared document head, navigation, cart drawer, footer, scripts
  sections/                homepage blocks (hero, deals, categories, trending, best sellers …)
  pages/                   product, listing, search, cart, checkout, wishlist, account, 404
  partials/                cart drawer body/foot + free-shipping strip (server + AJAX)
api/
  search.php               JSON search autocomplete
  cart.php                 JSON cart/wishlist mutations + chrome fragments
assets/css/home.css        nav dropdowns, cart drawer, mobile menu, toasts, responsive rules
assets/js/home.js          drawer, AJAX cart, autocomplete, dropdown nav, currency, Swiper
```

## Database
The current conversion reads the product catalog from JSON and uses PHP sessions for cart/wishlist state. Orders, order items, stock movements, couriers and coupons are persisted to MySQL (`includes/db.php`, schema/migrations in `database/`), so no Node stack is needed to run the demo. Checkout supports Cash on Delivery plus the online gateways described below; without gateway credentials it falls back to a local offline simulator, so the whole purchase flow works out of the box.

## Payment gateways
Checkout routes four methods — Cash on Delivery, SSLCommerz, bKash (Tokenized Checkout) and Nagad (`check-out/initialize` + `check-out/complete`). Routing lives in `api/checkout.php` / `includes/actions.php` through `includes/payment.php`; every gateway returns to `payment/success.php` (universal return URL), with `payment/fail.php`, `payment/cancel.php` and `payment/ipn.php` alongside. Those handlers verify the callback, settle the order as **Paid**, keep it **Pending** while confirmation is outstanding, or mark it **Failed**, and render the transaction summary (gateway, transaction ID, processor reference, instrument, amount) on the order success page.

**Modes** — `BHERB_PAYMENT_MODE` (`demo` | `sandbox` | `live`), auto-detected when unset:
- `demo`: no credentials configured → `payment/demo.php` simulates the chosen gateway locally, reproducing its exact callback shape (success, failure and cancellation), so checkout works offline.
- `sandbox`: selected automatically as soon as any gateway credential exists.
- `live`: forced with the env var; each gateway then reads its `..._LIVE_*` credential block.

**Credentials** are environment variables read in `includes/config.php`:

| Gateway | Sandbox variables | Live variables |
| --- | --- | --- |
| SSLCommerz | `BHERB_SSLCZ_STORE_ID`, `BHERB_SSLCZ_STORE_PASS` | `BHERB_SSLCZ_LIVE_STORE_ID`, `BHERB_SSLCZ_LIVE_STORE_PASS` |
| bKash | `BHERB_BKASH_USERNAME`, `BHERB_BKASH_PASSWORD`, `BHERB_BKASH_APP_KEY`, `BHERB_BKASH_APP_SECRET` | `BHERB_BKASH_LIVE_*` (same suffixes) |
| Nagad | `BHERB_NAGAD_MERCHANT_ID`, `BHERB_NAGAD_ACCOUNT_NUMBER`, `BHERB_NAGAD_PG_PUBLIC_KEY`, `BHERB_NAGAD_MERCHANT_PRIVATE_KEY` | `BHERB_NAGAD_LIVE_*` (same suffixes) |

Nagad key values may be PEM text, a PEM file path, or the bare base64 body pasted from the Nagad panel. All keys are listed in the header comment of `includes/config.php`, including the optional `BHERB_NAGAD_CURRENCY_CODE` (default `050`, the ISO-4217 numeric code for BDT).

**Order states**: an order starts `unpaid`, becomes `pending` the moment a gateway accepts the session (stock is held and a gateway reference is stored), then `paid` after a verified success callback or `failed` from the fail/cancel handlers (stock is released, and the same order can be retried). State writes live in `includes/checkout.php` (`create_order`, `order_mark_pending`, `order_mark_paid`, `order_mark_failed`).

Run `php database/migrate_payments.php` once on an existing database: it adds the gateway columns (`val_id`, `bank_tran_id`, `card_type`, `demo_payment`, `failed_reason`), the `pending` payment state and the reference lookup index. The migration is idempotent.

## Important scope note
The supplied React project contains a larger admin dashboard, authentication, order management, and third-party payment/API integrations. This PHP conversion recreates the main storefront and local shopping flows, but does **not** claim parity for protected admin CRUD, production login/registration, Supabase integrations, or all original external services. Gateway routing, callback verification and order settlement are implemented and tested against the local demo stack; connecting a real SSLCommerz / bKash / Nagad sandbox still requires that gateway's credentials and a round trip against its servers. Compare the original React site and this PHP version page-by-page before production use.
