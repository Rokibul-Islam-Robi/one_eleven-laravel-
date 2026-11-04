# Testing report

Environment: PHP 8.3.6, Laravel 12.70.2, **SQLite scratch databases** (copies of a freshly seeded database; the original SQL dump
and the original PHP project were never written to). MySQL/MariaDB was not available in the build environment.
The tests were scripted HTTP tests (cookie-jar sessions against `php artisan serve`) plus direct DB assertions; they are not shipped.
What ships and can be re-run anywhere is `php artisan bherb:verify`.

## Data preservation — PASS
29/29 tables, 553/553 original records (0 field differences vs the dump), 106 indexes, 100 CMS products untouched, 64 storefront
products from the unchanged `products.json`, seeding and migrating are idempotent (second run: "Nothing to migrate", tables with data skipped).
`database/bherb_original.sql`, `database/backup/bherb_original.sql`, `database/data/products.json` and all storefront CSS/JS files are
byte-identical to the originals (cmp).

## Storefront vs original — PASS
* 14 pages (home, product ×2, category, brands, brand, deals, bestsellers, new, search, cart, wishlist, account, checkout, 404)
  compared token-for-token with the original PHP output: identical except the injected CSRF script and one CMS-driven link
  (`category/supplements`, read from the `page_content` table that the original could not read without MySQL).
* 12 Playwright screenshots (6 pages × desktop 1366px / mobile 390px): 0.00–0.05 % differing pixels.
* 197 local links/assets crawled on the clean install: 0 broken. 22 route/asset probes: all 200.
* Cart add/qty/remove/wishlist, search API, customer sign-up/login/logout, CSRF refusal on API POST: PASS.

## Checkout, payments, prescriptions — PASS (demo gateway)
COD order (customer, items, stock deduction, stock movement); duplicate submission creates one order; forced DB failure inside the
transaction → no order row, no orphan items, stock unchanged; SSLCommerz / bKash / Nagad demo cycles: initiate → success (paid,
processing) → replay harmless; fail and cancel mark the order failed and release stock; IPN without data rejected; prescription-required
product without file blocked, with valid PNG creates order + prescription row, `.txt` rejected; return request creates `order_returns`.

## Admin — module by module (116 CRUD/behaviour checks + 141 permission checks + security probes)
| Module | Tested | Result |
|---|---|---|
| Login/session | success, bad password, 5-attempt lockout, inactive account, idle 30-min expiry, logout (POST), return-URL | PASS |
| Dashboard | renders real counts, role-aware links | PASS |
| Orders | list/filter/search, view, print invoice, valid & invalid status transitions, Rx gate (blocked / allowed), COD auto-paid, shipped/delivered timestamps, cancel releases stock, return approve / reject / receive, 404 | PASS |
| Prescriptions | record with PNG, JPG, PDF; .txt, fake PNG (PHP content), >5 MB rejected; missing name rejected; private storage (not under public/); file served only to signed-in staff; path-traversal value blocked; approve / reject / bogus status; delete by role | PASS |
| Products | list/search, create, validation, duplicate SKU, update, quick stock, delete (admin only); categories & brands create/update/delete, in-use category protected | PASS |
| Inventory | batch create/update/delete, expiry tab, low-stock threshold | PASS |
| Doctors | create (auto reference code), validation, duplicate e-mail, update, toggle, delete, delete blocked with referrals, view | PASS |
| Coupons / Offers | create/update/toggle/delete in both modes, doctor linkage | PASS |
| Customers | view, toggle, password reset (bcrypt), short password rejected | PASS |
| Courier | create/update/default/toggle/delete | PASS |
| Content | edit/update/toggle; staff blocked | PASS |
| Reports & Settings | reports, settings save (real `skey/svalue` columns), admin account create/sign-in/remove (demote), weak password, self-removal blocked, audit log | PASS |
| Search | global search incl. SQL-injection strings | PASS |

Permissions: 141 checks (3 roles × every module view / edit / delete endpoint, unauthenticated access, sidebar links) — 0 mismatches.
Enforced in `AdminAuthenticate` + `AdminModuleAccess` middleware and again in the controllers; hiding menu items is cosmetic only.
Security: every mutation is POST-only (GET → 405), CSRF token required (419 without), SQL injection probes on 9 search/filter inputs
(no 5xx, no leakage, tables intact), non-numeric ids rejected (404), stored XSS escaped, error pages show no stack traces with
`APP_DEBUG=false` (the shipped `.env.example` default).
Static checks: `php -l` on every PHP file, `view:cache` compiles all Blade views, `route:list` (39 routes), `bherb:verify` 40/40 on a clean install
(`migrate --seed` into an empty database, then boot and smoke tests).

## Defects found and fixed during testing
GET links could trigger admin delete/toggle without CSRF (original flaw) → POST-only; login redirect swallowed by a `catch` → fixed;
staff role could reach non-listed POST actions → any non-GET now needs edit rights; invoice print returned 500 → fixed; coupons Reset/pager link
404 → fixed; original settings code used non-existent columns → adapted to the real schema.
