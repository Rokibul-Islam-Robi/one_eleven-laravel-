<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `commission_rules` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_rules')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('commission_rules', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->enum('scope', ['global', 'category', 'seller', 'product']);
            $table->integer('ref_id')->nullable();
            $table->decimal('rate', 5, 2);
            $table->dateTime('effective_from');
            $table->boolean('is_active')->default(1);
            $table->string('note', 255)->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('effective_to')->nullable();
            $table->index(['scope', 'ref_id', 'is_active', 'effective_from'], $ix('idx_cr_lookup'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
