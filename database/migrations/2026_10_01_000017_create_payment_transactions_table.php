<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `payment_transactions` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_transactions')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('payment_transactions', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->bigIncrements('id');
            $table->integer('order_id')->nullable();
            $table->string('order_number', 40);
            $table->string('gateway', 20);
            $table->string('gateway_ref', 160);
            $table->string('trx_id', 160)->nullable();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->char('currency', 3)->default('BDT');
            $table->enum('status', ['initiated', 'success', 'failed', 'cancelled'])->default('initiated');
            $table->boolean('is_demo')->default(0);
            $table->mediumText('raw')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->unique(['gateway', 'gateway_ref'], $ix('uq_ptx_ref'));
            $table->index(['order_number'], $ix('idx_ptx_order'));
            $table->index(['status', 'created_at'], $ix('idx_ptx_status'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
