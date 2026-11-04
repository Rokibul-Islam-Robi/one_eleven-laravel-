<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `seller_order_items` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_order_items')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('seller_order_items', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->bigIncrements('id');
            $table->integer('order_id');
            $table->integer('order_item_id');
            $table->unsignedInteger('seller_id');
            $table->integer('product_id');
            $table->string('product_name', 255);
            $table->integer('qty');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->decimal('discount_alloc', 10, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->string('commission_source', 40)->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->decimal('seller_earning', 10, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'packed', 'shipped', 'delivered', 'cancelled', 'returned'])->default('pending');
            $table->string('tracking_code', 80)->nullable();
            $table->string('seller_note', 255)->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->unique(['order_item_id'], $ix('uq_soi_item'));
            $table->index(['seller_id', 'status'], $ix('idx_soi_seller'));
            $table->index(['order_id'], $ix('idx_soi_order'));
            $table->index(['seller_id', 'created_at'], $ix('idx_soi_created'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_order_items');
    }
};
