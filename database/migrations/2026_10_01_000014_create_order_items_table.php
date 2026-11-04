<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `order_items` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('order_items', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('order_id');
            $table->integer('product_id');
            $table->string('product_name', 255);
            $table->string('variant_label', 120)->nullable();
            $table->string('image_url', 255)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('qty');
            $table->index(['order_id'], $ix('order_id'));
            $table->index(['product_id'], $ix('product_id'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
