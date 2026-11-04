<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `sellers` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sellers')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('sellers', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->string('shop_name', 150);
            $table->string('slug', 160);
            $table->string('owner_name', 120);
            $table->string('email', 190);
            $table->string('phone', 40);
            $table->string('password_hash', 255);
            $table->string('address', 255)->nullable();
            $table->string('city', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('trade_license', 80)->nullable();
            $table->string('drug_license', 80)->nullable();
            $table->enum('status', ['pending', 'active', 'rejected', 'suspended'])->default('pending');
            $table->string('status_reason', 255)->nullable();
            $table->enum('payout_method', ['bkash', 'nagad', 'bank'])->nullable();
            $table->string('payout_account', 120)->nullable();
            $table->string('payout_account_name', 120)->nullable();
            $table->boolean('payout_verified')->default(0);
            $table->integer('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('created_ip', 45)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->unique(['slug'], $ix('uq_sellers_slug'));
            $table->unique(['email'], $ix('uq_sellers_email'));
            $table->index(['status'], $ix('idx_sellers_status'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
