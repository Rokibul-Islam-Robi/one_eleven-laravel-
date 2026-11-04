# Known limitations / not verified

1. **MySQL/MariaDB was never run.** All tests ran on SQLite. The MySQL-only parts — foreign-key migration (`…_900000_add_foreign_keys.php`,
   23 FKs), the `ft_doctors` FULLTEXT index and ENUM/charset options — were generated from the dump but are unexecuted. Run
   `php artisan migrate --seed && php artisan bherb:verify` on your server first; alternatively import `database/bherb_original.sql` and run `migrate`.
2. **Payment gateways were tested in demo mode only.** The live SSLCommerz / bKash / Nagad request, validation and IPN code is a port of the
   original and has not been exercised against their sandboxes (no network access / credentials).
3. **No PHPUnit suite** (PHPUnit could not be installed offline). Verification was by scripted HTTP tests (not shipped) and `bherb:verify`.
   `composer install` itself was not exercised against Packagist (blocked here; vendor was assembled from GitHub), and there is no `composer.lock`.
4. **Admin views are a hybrid.** The layout, login and all routing/auth/middleware are Laravel/Blade, but the 12 module pages still build their HTML
   in PHP strings (as the original did) inside controller classes, so markup/CSS is identical to the original. They are not split into Blade partials.
5. **Admin could not be compared with the original visually** (the original admin needs MySQL). Admin CSS/JS are byte-identical; layout was ported
   line by line. The original markup lacks an icon-size rule for the top search icon (it renders large); this was kept as is. Bootstrap/Google-Fonts CDNs
   were not reachable in the test sandbox.
6. The storefront catalogue (64, `products.json`) and the CMS catalogue (100, `products` table) differ by design — see `CATALOGUE_NOTES.md`.
7. Demo staff passwords are public in the original login page; change them. The original archive contained `.env.local` with a live Vercel OIDC token;
   it was **not** copied — consider rotating that token.
8. Behaviour changes vs original admin (intentional): POST-only mutations, Laravel CSRF, settings tab on real columns, footer reads "Laravel + MySQL".
