# Two product catalogues (kept separate on purpose)

| | Storefront catalogue | CMS catalogue |
|---|---|---|
| Source | `database/data/products.json` (unchanged, 64 products) | `products` table (100 rows from the original dump) |
| Used by | all storefront pages, cart, checkout, search | Admin → Products & Categories, Inventory & Expiry, dashboard counts |
| Stock | `catalog_stock` (64 rows) | `products.stock`, `inventory_batches` |

Decision (project owner): the storefront keeps reading `products.json`; the 100 database products are preserved and are not
merged, deduplicated or switched over. The dump also contains the setting `catalog_source = db` and a migration note
("Website catalogue now reads from the CMS database"), i.e. the SQL file appears to be newer than the PHP code. Switching the
storefront to the database catalogue would be a deliberate, separate change.
`php artisan bherb:verify` checks that both catalogues are intact.
