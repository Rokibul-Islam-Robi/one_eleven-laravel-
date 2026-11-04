<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `admin_logs` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_logs')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('admin_logs', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action', 80);
            $table->string('entity', 80)->nullable();
            $table->unsignedInteger('entity_id')->nullable();
            $table->string('detail', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id'], $ix('idx_log_user'));
            $table->index(['created_at'], $ix('idx_log_created'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
    }
};
