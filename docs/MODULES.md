# Converted modules

Storefront: / · /product/{id} · /brand/{slug} · /brands · /category/{slug} · /deals · /bestsellers · /new · /search · /cart ·
/wishlist · /account · /checkout · /order-success · api/cart.php · api/search.php · api/auth.php · api/checkout.php · api/quote.php ·
payment/{success,fail,cancel,ipn,demo}(.php).

Admin (`/admin/{module}/{action}/{id}`, controller per module in `app/Http/Controllers/Admin`):
dashboard · orders (list, view, print invoice, status flow, courier, Rx gate, returns) · prescriptions (record, upload, private file,
approve/reject, delete) · products (+ categories, brands, quick stock) · inventory (batches, expiry, thresholds) · doctors ·
doctor-coupons · offers · customers (view, enable/disable, password reset) · courier · content · reports (reports, settings, admin accounts,
audit log) · search.

Roles (minimum role for view / edit / delete): dashboard staff/staff/admin; products, doctors, coupons, offers, courier, customers,
inventory staff/manager/admin; orders staff/staff/manager; prescriptions staff/staff/manager; content and reports manager/manager/admin;
system settings and admin accounts admin only. Enforced server-side in `AdminModuleAccess` and again inside the controllers.

Seller fulfillment uses seller-owned order-item rows; sellers can pack, ship and mark only their own items delivered. The parent order
remains admin-managed. Seller inventory shows movement history scoped to the seller's products. Admin cancellation and return receipt
release reserved inventory once; recording a payment as refunded is a separate admin action after the external refund is confirmed.
Eligible pending online payments expire after 30 minutes and release their stock through `orders:expire-pending-payments`, scheduled
every minute. Orders awaiting prescription are excluded and require a separately defined prescription workflow. Run Laravel's scheduler
(`php artisan schedule:run` from the host scheduler) for automatic expiry; the command supports `--dry-run`.

Intentional differences from the original admin: forms use Laravel `@csrf`; every state-changing admin action is POST-only
(the original ran delete/toggle on a plain GET link because its CSRF check only applied to POSTs); the System-settings tab reads/writes the
real `settings.skey/svalue` columns of the supplied database (the original code queried `k/v`, which do not exist in that dump).
