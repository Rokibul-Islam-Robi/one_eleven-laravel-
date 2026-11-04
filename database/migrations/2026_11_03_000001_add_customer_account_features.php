<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'gender')) {
                $table->string('gender', 30)->nullable();
            }
            if (! Schema::hasColumn('customers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (! Schema::hasColumn('customers', 'profile_photo_path')) {
                $table->string('profile_photo_path', 255)->nullable();
            }
            if (! Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (! Schema::hasColumn('customers', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable();
            }
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id');
            $table->string('full_name', 190);
            $table->string('phone', 40);
            $table->string('division', 80);
            $table->string('district', 80);
            $table->string('area', 120);
            $table->string('street_address', 255);
            $table->text('instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['customer_id', 'is_default'], 'customer_addresses_default_idx');
            $table->foreign('customer_id', 'customer_addresses_customer_fk')
                ->references('id')->on('customers')->cascadeOnDelete();
        });

        Schema::create('customer_patients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id');
            $table->string('full_name', 190);
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('relationship', 80);
            $table->timestamps();
            $table->index(['customer_id', 'full_name'], 'customer_patients_owner_idx');
            $table->foreign('customer_id', 'customer_patients_customer_fk')
                ->references('id')->on('customers')->cascadeOnDelete();
        });

        Schema::create('storefront_customer_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id');
            $table->string('catalog_product_id', 50);
            $table->string('product_name', 255);
            $table->unsignedTinyInteger('rating');
            $table->text('review');
            $table->timestamps();
            $table->unique(['customer_id', 'catalog_product_id'], 'store_reviews_customer_product_unique');
            $table->foreign('customer_id', 'store_reviews_customer_fk')
                ->references('id')->on('customers')->cascadeOnDelete();
        });

        if (! Schema::hasColumn('orders', 'store_customer_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('store_customer_id')->nullable()->index('orders_store_customer_idx');
            });
        }

        if (! Schema::hasColumn('prescriptions', 'store_customer_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('store_customer_id')->nullable()->index('prescriptions_store_customer_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_customer_reviews');
        Schema::dropIfExists('customer_patients');
        Schema::dropIfExists('customer_addresses');

        foreach (['orders', 'prescriptions'] as $tableName) {
            if (Schema::hasColumn($tableName, 'store_customer_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropIndex($tableName . '_store_customer_idx');
                    $table->dropColumn('store_customer_id');
                });
            }
        }

        $columns = ['gender', 'date_of_birth', 'profile_photo_path', 'email_verified_at', 'phone_verified_at'];
        Schema::table('customers', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
