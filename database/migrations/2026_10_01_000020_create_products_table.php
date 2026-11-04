<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `products` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('products', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('category_id')->nullable();
            $table->integer('brand_id')->nullable();
            $table->string('name', 255);
            $table->string('slug', 280);
            $table->string('short_description', 255)->nullable()->default('');
            $table->text('description')->nullable();
            $table->string('sku', 80)->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('requires_prescription')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->decimal('rating', 2, 1)->nullable()->default(4.5);
            $table->integer('reviews_count')->nullable()->default(0);
            $table->string('badge', 50)->nullable()->default('');
            $table->string('tone', 30)->nullable()->default('#eef2ed');
            $table->string('image', 255)->nullable();
            $table->boolean('featured')->nullable()->default(0);
            $table->boolean('bestseller')->nullable()->default(0);
            $table->boolean('trending')->nullable()->default(0);
            $table->boolean('new_arrival')->nullable()->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedInteger('seller_id')->nullable();
            $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected'])->default('approved');
            $table->string('rejection_reason', 255)->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->integer('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->integer('json_id')->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->unique(['slug'], $ix('slug'));
            $table->unique(['sku'], $ix('sku'));
            $table->unique(['json_id'], $ix('uq_products_json'));
            $table->index(['category_id'], $ix('category_id'));
            $table->index(['brand_id'], $ix('brand_id'));
            $table->index(['price'], $ix('price'));
            $table->index(['seller_id'], $ix('idx_products_seller'));
            $table->index(['is_active', 'approval_status'], $ix('idx_products_visible'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
