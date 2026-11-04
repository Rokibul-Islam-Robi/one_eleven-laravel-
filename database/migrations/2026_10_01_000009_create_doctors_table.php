<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `doctors` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctors')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('doctors', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->string('name', 150);
            $table->string('specialty', 120)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('alternate_phone', 40)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('registration_council', 120)->nullable();
            $table->date('registration_verified_at')->nullable();
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->string('reference_code', 20);
            $table->decimal('reference_discount', 5, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['reference_code'], $ix('uq_doctors_refcode'));
            $table->unique(['registration_number'], $ix('uq_doctors_reg'));
            $table->unique(['email'], $ix('uq_doctors_email'));
            $table->index(['status'], $ix('idx_doctors_status'));
            $table->index(['verification_status'], $ix('idx_doctors_verify'));
            $table->index(['name'], $ix('idx_doctors_name'));
            $table->index(['created_by'], $ix('fk_doctors_creator'));
        });

        // FULLTEXT KEY `ft_doctors` (name, specialty, email, phone) — MySQL/MariaDB only
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->fullText(['name', 'specialty', 'email', 'phone'], 'ft_doctors');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
