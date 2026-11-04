<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `customers` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('customers', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->bigIncrements('id');
            $table->string('full_name', 190);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('password_hash', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['email'], $ix('email'));
            $table->unique(['phone'], $ix('phone'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
