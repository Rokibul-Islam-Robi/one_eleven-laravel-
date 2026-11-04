<?php

namespace App\Console\Commands;

use App\Support\Catalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * `php artisan bherb:verify` — post-install acceptance check.
 * Confirms the database holds the original bHerb schema and records, that the two product
 * catalogues are intact and separate, and that the key routes/folders are in place.
 */
class VerifyInstall extends Command
{
    protected $signature = 'bherb:verify';
    protected $description = 'Verify tables, original records, catalogues, routes and storage after installation';

    private int $fail = 0;

    private function check(string $label, bool $ok, string $info = ''): void
    {
        $ok ? $this->line("  <info>PASS</info> {$label}") : $this->line("  <error>FAIL</error> {$label} {$info}");
        $this->fail += $ok ? 0 : 1;
    }

    public function handle(): int
    {
        $dir = database_path('seeders/data');
        $json = glob($dir . '/*.json') ?: [];

        $this->info('Database');
        $tables = array_map(fn ($t) => $t['name'] ?? ((array) $t)['name'], Schema::getTables());
        $expected = glob(database_path('migrations/*_create_*_table.php')) ?: [];
        $this->check('all ' . count($expected) . ' original tables exist', count($expected) === 29 && count(array_filter(
            array_map(fn ($f) => preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_create_(.*)_table\.php$/', '$1', basename($f)), $expected),
            fn ($t) => in_array($t, $tables, true)
        )) === 29, '(tables found: ' . count($tables) . ')');

        $total = 0;
        foreach ($json as $f) {
            $t = basename($f, '.json');
            $n = count(json_decode((string) file_get_contents($f), true) ?: []);
            $total += $n;
            $have = Schema::hasTable($t) ? DB::table($t)->count() : -1;
            $this->check("{$t}: {$have} rows (original dump: {$n})", $have >= $n);
        }
        $this->check("original records present ({$total} in dump)", $total === 553);

        $this->info('Catalogues (kept separate by design)');
        $this->check('storefront catalogue: 64 products from database/data/products.json', count(Catalog::all()) === 64);
        $this->check('CMS catalogue: >= 100 rows in the products table', DB::table('products')->count() >= 100);
        $this->check('catalog_stock rows for the JSON catalogue', DB::table('catalog_stock')->count() >= 64);

        $this->info('Backup');
        $a = database_path('bherb_original.sql');
        $b = database_path('backup/bherb_original.sql');
        $this->check('original SQL backup present and both copies identical', is_file($a) && is_file($b) && md5_file($a) === md5_file($b));

        $this->info('Accounts');
        $this->check('staff accounts (admin/manager/staff) exist', DB::table('users')->whereIn('role', ['admin', 'manager', 'staff'])->count() >= 3);

        $this->info('Routes');
        foreach (['/', 'cart', 'checkout', 'api/cart.php', 'api/quote.php', 'api/checkout.php', 'payment/success', 'admin/login', 'admin/{module?}/{action?}/{id?}'] as $uri) {
            $this->check("route {$uri}", collect(Route::getRoutes())->contains(fn ($r) => $r->uri() === $uri));
        }

        $this->info('Storage');
        foreach (['storage/app/private', 'storage/framework/sessions', 'storage/logs', 'bootstrap/cache'] as $p) {
            $this->check("{$p} writable", is_dir(base_path($p)) && is_writable(base_path($p)));
        }

        $this->newLine();
        $this->fail === 0 ? $this->info('All checks passed.') : $this->error("{$this->fail} check(s) failed.");
        return $this->fail === 0 ? self::SUCCESS : self::FAILURE;
    }
}
