<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Loads the ORIGINAL bHerb records (extracted verbatim from bherb.sql into
 * database/seeders/data/*.json). Nothing is invented or replaced with dummy
 * data. A table that already contains rows is left untouched, so running the
 * seeder against an existing/adopted bHerb database never duplicates data.
 */
class DatabaseSeeder extends Seeder
{
    /** Parents before children (foreign keys are also disabled while loading). */
    private const TABLES = [
        'users', 'categories', 'brands', 'doctors', 'courier_methods', 'coupons',
        'customers', 'catalog_stock', 'products', 'prescriptions', 'orders',
        'order_items', 'order_returns', 'inventory_batches', 'stock_movements',
        'doctor_referrals', 'page_content', 'settings', 'login_attempts',
        'admin_logs', 'schema_migrations',
    ];

    public function run(): void
    {
        $dir = database_path('seeders/data');

        Schema::disableForeignKeyConstraints();
        try {
            foreach (self::TABLES as $table) {
                $file = "{$dir}/{$table}.json";
                if (! is_file($file) || ! Schema::hasTable($table)) {
                    continue;
                }
                if (DB::table($table)->exists()) {
                    $this->command?->warn("{$table}: already has data — skipped");
                    continue;
                }
                $rows = json_decode((string) file_get_contents($file), true) ?: [];
                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
                $this->command?->info("{$table}: " . count($rows) . ' rows');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
