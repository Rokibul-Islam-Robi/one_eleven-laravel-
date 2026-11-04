<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `courier_methods` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courier_methods')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('courier_methods', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->string('name', 100);
            $table->string('code', 50);
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->decimal('cod_fee', 10, 2)->default(0.00);
            $table->decimal('free_delivery_min', 10, 2)->default(0.00);
            $table->string('estimated_days', 40)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('tracking_url', 255)->nullable();
            $table->boolean('is_default')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['code'], $ix('uq_courier_code'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_methods');
    }
};
