<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `payout_requests` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payout_requests')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('payout_requests', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->unsignedInteger('seller_id');
            $table->decimal('amount', 12, 2);
            $table->string('method', 10);
            $table->string('account', 120);
            $table->string('account_name', 120)->nullable();
            $table->enum('status', ['requested', 'approved', 'paid', 'rejected'])->default('requested');
            $table->string('reference', 120)->nullable();
            $table->string('note', 255)->nullable();
            $table->dateTime('requested_at');
            $table->integer('processed_by')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->string('proof_file', 255)->nullable();
            $table->unique(['reference'], $ix('uq_payout_ref'));
            $table->index(['status', 'requested_at'], $ix('idx_pr_status'));
            $table->index(['seller_id', 'requested_at'], $ix('idx_pr_seller'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};
