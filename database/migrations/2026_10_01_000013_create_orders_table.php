<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `orders` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('orders', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('order_number', 40)->nullable();
            $table->string('idempotency_key', 64)->nullable();
            $table->integer('user_id')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0.00);
            $table->decimal('cod_fee', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'processing', 'awaiting_prescription', 'shipped', 'delivered', 'failed_delivery', 'cancelled', 'return_requested', 'returned'])->default('pending');
            $table->string('payment_method', 40)->nullable()->default('Cash on Delivery');
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed', 'refunded'])->default('unpaid');
            $table->string('val_id', 100)->nullable();
            $table->string('bank_tran_id', 100)->nullable();
            $table->string('card_type', 40)->nullable();
            $table->boolean('demo_payment')->default(0);
            $table->integer('coupon_id')->nullable();
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->boolean('requires_prescription')->default(0);
            $table->unsignedInteger('prescription_id')->nullable();
            $table->unsignedInteger('courier_id')->nullable();
            $table->string('courier_code', 50)->nullable();
            $table->string('tracking_code', 80)->nullable();
            $table->string('shipping_name', 120);
            $table->string('shipping_phone', 40);
            $table->string('customer_email', 190)->nullable();
            $table->string('customer_ip', 45)->nullable();
            $table->text('shipping_address');
            $table->string('shipping_area', 120)->nullable();
            $table->string('shipping_postal', 20)->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('paid_at')->nullable();
            $table->unique(['order_number'], $ix('uq_orders_number'));
            $table->unique(['idempotency_key'], $ix('uq_orders_idem'));
            $table->index(['user_id'], $ix('user_id'));
            $table->index(['status'], $ix('idx_orders_status'));
            $table->index(['courier_id'], $ix('fk_orders_courier'));
            $table->index(['coupon_id'], $ix('fk_orders_coupon'));
            $table->index(['prescription_id'], $ix('fk_orders_rx'));
            $table->index(['val_id'], $ix('idx_orders_val_id'));
            $table->index(['created_at'], $ix('idx_orders_created'));
            $table->index(['payment_status'], $ix('idx_orders_payment'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
