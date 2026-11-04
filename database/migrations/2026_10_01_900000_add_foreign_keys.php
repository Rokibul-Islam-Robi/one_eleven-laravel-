<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Foreign keys of the original schema (the `ALTER TABLE ... ADD CONSTRAINT`
 * block of bherb.sql). Added after every table exists. Already-present
 * constraints (adopted database) are skipped.
 */
return new class extends Migration
{
    private function hasForeignKey(string $table, string $name): bool
    {
        foreach (Schema::getForeignKeys($table) as $fk) {
            if (($fk['name'] ?? null) === $name) {
                return true;
            }
        }
        return false;
    }

    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            return; // SQLite cannot add foreign keys to existing tables
        }

        if (! $this->hasForeignKey('coupons', 'fk_coupons_doctor')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreign('doctor_id', 'fk_coupons_doctor')->references('id')->on('doctors')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('doctors', 'fk_doctors_creator')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->foreign('created_by', 'fk_doctors_creator')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('doctor_referrals', 'fk_ref_customer')) {
            Schema::table('doctor_referrals', function (Blueprint $table) {
                $table->foreign('customer_id', 'fk_ref_customer')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('doctor_referrals', 'fk_ref_doctor')) {
            Schema::table('doctor_referrals', function (Blueprint $table) {
                $table->foreign('doctor_id', 'fk_ref_doctor')->references('id')->on('doctors')->cascadeOnDelete();
            });
        }
        if (! $this->hasForeignKey('doctor_referrals', 'fk_ref_order')) {
            Schema::table('doctor_referrals', function (Blueprint $table) {
                $table->foreign('order_id', 'fk_ref_order')->references('id')->on('orders')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('inventory_batches', 'fk_batch_product')) {
            Schema::table('inventory_batches', function (Blueprint $table) {
                $table->foreign('product_id', 'fk_batch_product')->references('id')->on('products')->cascadeOnDelete();
            });
        }
        if (! $this->hasForeignKey('orders', 'fk_orders_coupon')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('coupon_id', 'fk_orders_coupon')->references('id')->on('coupons')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('orders', 'fk_orders_courier')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('courier_id', 'fk_orders_courier')->references('id')->on('courier_methods')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('orders', 'fk_orders_rx')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('prescription_id', 'fk_orders_rx')->references('id')->on('prescriptions')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('orders', 'orders_ibfk_1')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('user_id', 'orders_ibfk_1')->references('id')->on('users')->cascadeOnDelete();
            });
        }
        if (! $this->hasForeignKey('order_items', 'order_items_ibfk_1')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('order_id', 'order_items_ibfk_1')->references('id')->on('orders')->cascadeOnDelete();
            });
        }
        if (! $this->hasForeignKey('order_returns', 'fk_or_order')) {
            Schema::table('order_returns', function (Blueprint $table) {
                $table->foreign('order_id', 'fk_or_order')->references('id')->on('orders')->cascadeOnDelete();
            });
        }
        if (! $this->hasForeignKey('payout_requests', 'fk_pr_seller')) {
            Schema::table('payout_requests', function (Blueprint $table) {
                $table->foreign('seller_id', 'fk_pr_seller')->references('id')->on('sellers');
            });
        }
        if (! $this->hasForeignKey('prescriptions', 'fk_rx_customer')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreign('customer_id', 'fk_rx_customer')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('prescriptions', 'fk_rx_doctor')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreign('doctor_id', 'fk_rx_doctor')->references('id')->on('doctors')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('prescriptions', 'fk_rx_order')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreign('order_id', 'fk_rx_order')->references('id')->on('orders')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('products', 'products_ibfk_1')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('category_id', 'products_ibfk_1')->references('id')->on('categories')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('products', 'products_ibfk_2')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('brand_id', 'products_ibfk_2')->references('id')->on('brands')->nullOnDelete();
            });
        }
        if (! $this->hasForeignKey('reviews', 'reviews_ibfk_1')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('user_id', 'reviews_ibfk_1')->references('id')->on('users')->cascadeOnDelete();
            });
        }
        if (! $this->hasForeignKey('reviews', 'reviews_ibfk_2')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('product_id', 'reviews_ibfk_2')->references('id')->on('products')->cascadeOnDelete();
            });
        }
        if (! $this->hasForeignKey('seller_ledger', 'fk_ledger_seller')) {
            Schema::table('seller_ledger', function (Blueprint $table) {
                $table->foreign('seller_id', 'fk_ledger_seller')->references('id')->on('sellers');
            });
        }
        if (! $this->hasForeignKey('seller_order_items', 'fk_soi_seller')) {
            Schema::table('seller_order_items', function (Blueprint $table) {
                $table->foreign('seller_id', 'fk_soi_seller')->references('id')->on('sellers');
            });
        }
        if (! $this->hasForeignKey('stock_movements', 'fk_sm_order')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreign('order_id', 'fk_sm_order')->references('id')->on('orders')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            return;
        }
        if ($this->hasForeignKey('coupons', 'fk_coupons_doctor')) {
            Schema::table('coupons', fn (Blueprint $table) => $table->dropForeign('fk_coupons_doctor'));
        }
        if ($this->hasForeignKey('doctors', 'fk_doctors_creator')) {
            Schema::table('doctors', fn (Blueprint $table) => $table->dropForeign('fk_doctors_creator'));
        }
        if ($this->hasForeignKey('doctor_referrals', 'fk_ref_customer')) {
            Schema::table('doctor_referrals', fn (Blueprint $table) => $table->dropForeign('fk_ref_customer'));
        }
        if ($this->hasForeignKey('doctor_referrals', 'fk_ref_doctor')) {
            Schema::table('doctor_referrals', fn (Blueprint $table) => $table->dropForeign('fk_ref_doctor'));
        }
        if ($this->hasForeignKey('doctor_referrals', 'fk_ref_order')) {
            Schema::table('doctor_referrals', fn (Blueprint $table) => $table->dropForeign('fk_ref_order'));
        }
        if ($this->hasForeignKey('inventory_batches', 'fk_batch_product')) {
            Schema::table('inventory_batches', fn (Blueprint $table) => $table->dropForeign('fk_batch_product'));
        }
        if ($this->hasForeignKey('orders', 'fk_orders_coupon')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropForeign('fk_orders_coupon'));
        }
        if ($this->hasForeignKey('orders', 'fk_orders_courier')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropForeign('fk_orders_courier'));
        }
        if ($this->hasForeignKey('orders', 'fk_orders_rx')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropForeign('fk_orders_rx'));
        }
        if ($this->hasForeignKey('orders', 'orders_ibfk_1')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropForeign('orders_ibfk_1'));
        }
        if ($this->hasForeignKey('order_items', 'order_items_ibfk_1')) {
            Schema::table('order_items', fn (Blueprint $table) => $table->dropForeign('order_items_ibfk_1'));
        }
        if ($this->hasForeignKey('order_returns', 'fk_or_order')) {
            Schema::table('order_returns', fn (Blueprint $table) => $table->dropForeign('fk_or_order'));
        }
        if ($this->hasForeignKey('payout_requests', 'fk_pr_seller')) {
            Schema::table('payout_requests', fn (Blueprint $table) => $table->dropForeign('fk_pr_seller'));
        }
        if ($this->hasForeignKey('prescriptions', 'fk_rx_customer')) {
            Schema::table('prescriptions', fn (Blueprint $table) => $table->dropForeign('fk_rx_customer'));
        }
        if ($this->hasForeignKey('prescriptions', 'fk_rx_doctor')) {
            Schema::table('prescriptions', fn (Blueprint $table) => $table->dropForeign('fk_rx_doctor'));
        }
        if ($this->hasForeignKey('prescriptions', 'fk_rx_order')) {
            Schema::table('prescriptions', fn (Blueprint $table) => $table->dropForeign('fk_rx_order'));
        }
        if ($this->hasForeignKey('products', 'products_ibfk_1')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropForeign('products_ibfk_1'));
        }
        if ($this->hasForeignKey('products', 'products_ibfk_2')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropForeign('products_ibfk_2'));
        }
        if ($this->hasForeignKey('reviews', 'reviews_ibfk_1')) {
            Schema::table('reviews', fn (Blueprint $table) => $table->dropForeign('reviews_ibfk_1'));
        }
        if ($this->hasForeignKey('reviews', 'reviews_ibfk_2')) {
            Schema::table('reviews', fn (Blueprint $table) => $table->dropForeign('reviews_ibfk_2'));
        }
        if ($this->hasForeignKey('seller_ledger', 'fk_ledger_seller')) {
            Schema::table('seller_ledger', fn (Blueprint $table) => $table->dropForeign('fk_ledger_seller'));
        }
        if ($this->hasForeignKey('seller_order_items', 'fk_soi_seller')) {
            Schema::table('seller_order_items', fn (Blueprint $table) => $table->dropForeign('fk_soi_seller'));
        }
        if ($this->hasForeignKey('stock_movements', 'fk_sm_order')) {
            Schema::table('stock_movements', fn (Blueprint $table) => $table->dropForeign('fk_sm_order'));
        }
    }
};
