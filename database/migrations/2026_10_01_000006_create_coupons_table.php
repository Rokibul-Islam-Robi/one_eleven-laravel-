<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `coupons` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coupons')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('coupons', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('code', 50);
            $table->decimal('discount_percent', 5, 2);
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            $table->decimal('min_order', 10, 2)->default(0.00);
            $table->unsignedInteger('max_uses')->default(0);
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('active')->nullable()->default(1);
            $table->unsignedInteger('doctor_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->unique(['code'], $ix('code'));
            $table->index(['doctor_id'], $ix('idx_coupons_doctor'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
