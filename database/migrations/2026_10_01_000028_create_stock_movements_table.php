<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `stock_movements` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_movements')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('stock_movements', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->integer('product_id');
            $table->integer('order_id')->nullable();
            $table->integer('delta');
            $table->enum('type', ['order_hold', 'order_release', 'order_ship', 'order_return', 'manual_adjust']);
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id'], $ix('idx_sm_product'));
            $table->index(['order_id'], $ix('idx_sm_order'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
