<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `inventory_batches` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_batches')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('inventory_batches', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->integer('product_id');
            $table->string('batch_number', 80);
            $table->unsignedInteger('qty_received')->default(0);
            $table->unsignedInteger('qty_remaining')->default(0);
            $table->decimal('cost_price', 10, 2)->default(0.00);
            $table->date('expiry_date')->nullable();
            $table->string('supplier', 120)->nullable();
            $table->date('received_at')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id'], $ix('idx_batch_product'));
            $table->index(['expiry_date'], $ix('idx_batch_expiry'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
