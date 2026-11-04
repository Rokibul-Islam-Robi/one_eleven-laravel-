# Conversion status

Implemented in this package:
- Core PHP entry point and Apache clean-route fallback
- Product catalog imported from the supplied source data
- Homepage, product detail, category, deals, bestsellers, new-arrivals and search pages
- Session-based cart and wishlist
- Quantity update/removal, cart totals and local demo checkout
- Responsive storefront styles, navigation, promo slider and footer
- XAMPP installation documentation and SQL starter schema

Not yet equivalent to the complete React app:
- Admin dashboard CRUD and permission/role flows
- Production customer registration/login and persistent accounts
- Supabase/API synchronization and live payment provider callbacks
- Full page-by-page pixel comparison against a running reference deployment

Image note: source product image definitions are mostly remote Pexels/Unsplash URLs, not local image files in the supplied ZIP. The PHP catalog preserves these URLs; internet access is needed to display them.

Homepage restructure (modular build):
- index.php is now a thin front controller; markup lives in includes/header.php, includes/footer.php, includes/sections/* and includes/pages/*.
- New chrome: language/currency selectors with client-side price conversion, autocomplete search (api/search.php), multi-level category navigation with sub-category panels, mobile accordion menu, and a slide-over cart drawer driven by api/cart.php.
- Hero slider now uses Swiper.js from CDN with a plain-CSS fallback implemented in assets/js/site.js.
- Styling for the new components is in assets/css/home.css; behaviour is in assets/js/home.js.
