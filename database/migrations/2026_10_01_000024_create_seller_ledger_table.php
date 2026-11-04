<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `seller_ledger` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_ledger')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('seller_ledger', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->bigIncrements('id');
            $table->unsignedInteger('seller_id');
            $table->enum('entry_type', ['sale', 'refund', 'payout', 'payout_reversal', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->dateTime('available_at');
            $table->string('ref_type', 30)->nullable();
            $table->bigInteger('ref_id')->nullable();
            $table->string('idem_key', 80);
            $table->string('note', 255)->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('created_at');
            $table->unique(['idem_key'], $ix('uq_ledger_idem'));
            $table->index(['seller_id', 'available_at'], $ix('idx_ledger_seller'));
            $table->index(['seller_id', 'created_at'], $ix('idx_ledger_created'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_ledger');
    }
};
