<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `settings` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->string('skey', 80);
            $table->mediumText('svalue')->nullable();
            $table->boolean('is_secret')->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->primary(['skey']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
