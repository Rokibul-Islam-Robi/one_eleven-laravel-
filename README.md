# bHerb — Laravel 12 edition

The bHerb / RBS Pharmaceutical e-commerce storefront and 12-module admin CMS, converted from Core PHP to Laravel 12 with
**the same design, content, features and database data**. Storefront CSS/JS/images are the original files, byte for byte.

* Storefront: home, product, brand(s), category, deals / bestsellers / new, search, cart, wishlist, account, checkout, order success,
  payment callbacks (SSLCommerz, bKash, Nagad, COD + built-in demo gateway), prescription upload, return requests.
* Admin CMS (`/admin`): Dashboard, Orders & Shipping, Prescriptions, Products & Categories & Brands, Inventory & Expiry, Doctors,
  Doctor Reference Coupons, Offers & Discounts, Customers, Courier Settings, Website Content, Reports & Settings (system settings,
  admin accounts, audit log), global search. Roles: staff < manager < admin.

## Requirements
PHP 8.2+ (8.3 tested), Composer 2, MySQL 8 / MariaDB 10.4+ (XAMPP works), PHP extensions: pdo_mysql, mbstring, fileinfo, openssl, curl.

## Install
```bash
unzip bherb_laravel.zip && cd bherb_laravel
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# create an empty database, then put its name/user/password in .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
php artisan migrate --seed          # creates the 29 tables and loads the 553 original records
php artisan bherb:verify            # acceptance check (tables, records, catalogues, routes, storage)
php artisan serve                   # http://127.0.0.1:8000   (admin: /admin)
```

**Alternative — adopt the original database dump:** import `database/bherb_original.sql` into an empty database, point `.env`
at it and run `php artisan migrate` (the migrations skip tables that already exist, so nothing is changed or duplicated).
`php artisan migrate --seed` on a populated database also never duplicates data (tables that contain rows are skipped).

Apache/XAMPP: point the document root at `public/` (or browse `http://localhost/bherb_laravel/public/`). The app works from a
sub-folder (`<base href>` is derived from the request). Make `storage/` and `bootstrap/cache/` writable.
Prescription uploads are stored privately in `storage/app/private/uploads/prescriptions/` (no `storage:link` needed) and are
served only to signed-in staff through `/admin/prescriptions/file/{id}`.

## Admin sign-in
The seeded staff accounts are the ones in the original database (`admin@rbspharma.com`, `manager@rbspharma.com`,
`staff@rbspharma.com`; the original login page lists the demo passwords). **Change these passwords before going live**
(Admin → Reports & Settings → Admin accounts).

## Configuration (.env)
`APP_*`, `DB_*` as usual; `APP_TIMEZONE=Asia/Dhaka`. Payment gateways run in **demo mode** until their credentials are set
(see the commented `BHERB_*` variables in `.env.example`: SSLCommerz store id/password, bKash and Nagad credentials,
`BHERB_*_SANDBOX`). Set `APP_ENV=production`, `APP_DEBUG=false` in production.

## Product catalogue — important
* The **storefront** reads the **64 products** of `database/data/products.json` (exactly as the original PHP app did).
* The **`products` table holds 100 CMS products** managed in Admin → Products. They are preserved untouched and are **not merged**
  with the JSON catalogue. Stock for the storefront products lives in `catalog_stock`.
* See `docs/CATALOGUE_NOTES.md`.

## Project map
`routes/{storefront,checkout,admin}.php` · `app/Http/Controllers/{Storefront,Payment,Admin}` · `app/Services` (checkout, orders,
coupons, courier, prescriptions, payments) · `app/Support` (catalogue, cart, helpers, admin helper layer) ·
`app/Http/Middleware/{AdminAuthenticate,AdminModuleAccess}` · `resources/views` (Blade) · `database/migrations` (29 tables) ·
`database/seeders` (original records as JSON) · `database/bherb_original.sql` (original dump, unchanged).

More: `docs/TESTING_REPORT.md`, `docs/KNOWN_LIMITATIONS.md`, `docs/MODULES.md`.
