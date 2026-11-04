<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `doctor_referrals` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctor_referrals')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('doctor_referrals', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->unsignedInteger('doctor_id');
            $table->integer('order_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->string('coupon_code', 50)->nullable();
            $table->string('customer_name', 150)->nullable();
            $table->decimal('order_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('credit_amount', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'credited', 'cancelled'])->default('pending');
            $table->date('referred_at');
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['doctor_id'], $ix('idx_ref_doctor'));
            $table->index(['order_id'], $ix('idx_ref_order'));
            $table->index(['referred_at'], $ix('idx_ref_date'));
            $table->index(['customer_id'], $ix('fk_ref_customer'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_referrals');
    }
};
