<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the original `prescriptions` table from bherb.sql (MariaDB dump).
 * Skipped when the table already exists, so the migration is safe to run
 * against an existing bHerb database that is being adopted by Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescriptions')) {
            return;
        }

        // Index names are kept as in the original MySQL schema; SQLite needs db-wide unique names.
        $ix = fn (string $name) => Schema::getConnection()->getDriverName() === 'sqlite' ? null : $name;

        Schema::create('prescriptions', function (Blueprint $table) use ($ix) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id');
            $table->integer('customer_id')->nullable();
            $table->unsignedInteger('doctor_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->string('customer_name', 150);
            $table->string('phone', 40)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->index(['status'], $ix('idx_rx_status'));
            $table->index(['doctor_id'], $ix('idx_rx_doctor'));
            $table->index(['customer_id'], $ix('fk_rx_customer'));
            $table->index(['order_id'], $ix('fk_rx_order'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
