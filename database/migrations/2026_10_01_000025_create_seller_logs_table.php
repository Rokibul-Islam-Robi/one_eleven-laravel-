<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `seller_logs` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_logs')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('seller_logs', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->bigIncrements('id');
            $table->unsignedInteger('seller_id')->nullable();
            $table->string('action', 60);
            $table->string('detail', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->dateTime('created_at');
            $table->index(['seller_id', 'created_at'], $ix('idx_sl_seller'));
            $table->index(['action', 'ip', 'created_at'], $ix('idx_sl_action'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_logs');
    }
};
