<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `schema_migrations` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schema_migrations')) {
            return;
        }

        Schema::create('schema_migrations', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->string('id', 80);
            $table->string('title', 190)->nullable();
            $table->dateTime('applied_at');
            $table->primary(['id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_migrations');
    }
};
