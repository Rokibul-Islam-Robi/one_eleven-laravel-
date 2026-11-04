<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `login_attempts` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_attempts')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('login_attempts', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->bigIncrements('id');
            $table->string('email', 190);
            $table->string('ip', 45);
            $table->boolean('success')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['email', 'created_at'], $ix('idx_attempt_email'));
            $table->index(['ip', 'created_at'], $ix('idx_attempt_ip'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
