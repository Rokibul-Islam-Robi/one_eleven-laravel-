<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `page_content` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_content')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('page_content', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->string('section_key', 80);
            $table->string('kicker', 100)->nullable();
            $table->string('title', 190)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->text('body')->nullable();
            $table->string('link_url', 255)->nullable();
            $table->string('link_label', 120)->nullable();
            $table->string('image_url', 255)->nullable();
            $table->string('image_url_2', 255)->nullable();
            $table->boolean('is_active')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['section_key'], $ix('uq_content_key'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_content');
    }
};
