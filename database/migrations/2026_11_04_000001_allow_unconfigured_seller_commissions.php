<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seller_order_items')) {
            return;
        }

        Schema::table('seller_order_items', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->change();
            $table->decimal('commission_amount', 10, 2)->nullable()->change();
            $table->decimal('seller_earning', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('seller_order_items')) {
            return;
        }

        $hasUnconfiguredRows = DB::table('seller_order_items')
            ->whereNull('commission_rate')
            ->orWhereNull('commission_amount')
            ->orWhereNull('seller_earning')
            ->exists();

        if ($hasUnconfiguredRows) {
            throw new \RuntimeException('Cannot require commission values while seller order items still have unconfigured commissions.');
        }

        Schema::table('seller_order_items', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable(false)->change();
            $table->decimal('commission_amount', 10, 2)->nullable(false)->change();
            $table->decimal('seller_earning', 10, 2)->nullable(false)->change();
        });
    }
};
