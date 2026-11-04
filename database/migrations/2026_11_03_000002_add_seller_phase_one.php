<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->string('application_status', 20)->default('pending');
            $table->string('category', 80)->nullable();
            $table->string('legal_business_name', 150)->nullable();
            $table->text('pickup_address')->nullable();
            $table->text('return_address')->nullable();
            $table->string('tax_identifier', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('application_submitted_at')->nullable();
            $table->integer('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->integer('suspended_by')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->integer('activated_by')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->index(['application_status', 'status'], 'sellers_application_status_idx');
        });

        Schema::table('seller_logs', function (Blueprint $table) {
            $table->string('actor_type', 20)->nullable();
            $table->integer('actor_id')->nullable();
        });

        Schema::create('seller_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('seller_id');
            $table->string('document_type', 60);
            $table->string('storage_path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 80);
            $table->unsignedInteger('file_size');
            $table->string('status', 20)->default('pending');
            $table->integer('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['seller_id', 'created_at'], 'seller_documents_owner_idx');
            $table->index(['status', 'created_at'], 'seller_documents_status_idx');
            $table->foreign('seller_id', 'seller_documents_seller_fk')
                ->references('id')->on('sellers')->cascadeOnDelete();
        });

        Schema::create('seller_password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 190)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_password_reset_tokens');
        Schema::dropIfExists('seller_documents');

        Schema::table('seller_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_type', 'actor_id']);
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->dropIndex('sellers_application_status_idx');
            $table->dropColumn([
                'application_status',
                'category',
                'legal_business_name',
                'pickup_address',
                'return_address',
                'tax_identifier',
                'email_verified_at',
                'phone_verified_at',
                'is_active',
                'application_submitted_at',
                'rejected_by',
                'rejected_at',
                'suspended_by',
                'suspended_at',
                'activated_by',
                'activated_at',
            ]);
        });
    }
};
