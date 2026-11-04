# Front-end plan implementation — change log

Hero slider: single Swiper instance (650ms, autoplay 5.5s, pause on hover/focus/hidden tab, reduced-motion respected, keyboard + a11y labels), CSS fallback in site.js only when the CDN fails (now with touch swipe), reserved image sizes.
Wishlist: heart icon states (outline/filled/busy), aria-pressed + changing label, server session is the source of truth (api/cart.php returns `wished`), all hearts of a product stay in sync, failure restores prior state, wishlist page removes cards in place.
Product page: 1650px centred wrapper; media | info | sticky purchase panel (desktop), 2-col tablet, 1-col mobile; horizontal thumbnail strip directly under the main image with prev/next arrows when it overflows.
Checkout: postal code removed everywhere (form, JS, API, order insert; SSLCommerz gets a neutral server-side default postcode); searchable 64-district list (includes/districts.php, validated server-side); district stored in orders.shipping_area and shown on order-success and admin order view/invoice; street address stays separate.
Payment: SSLCommerz + COD only (bKash/Nagad shown as channels inside SSLCommerz); demo mode clearly labelled everywhere; logo slots in assets/images/payment/.
Safety fixes: a forged "DEMO…" val_id is no longer accepted when sandbox credentials are configured; replayed fail/cancel/IPN can no longer flip a PAID order to failed; success.php checks tran_id + amount; duplicate success callbacks are no-ops.
No database migration needed (shipping_postal column stays, just unused).
