<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('name');
            $table->string('sku', 50);
            $table->string('barcode', 100)->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('purchase_price', 15, 4)->default(0.0000);
            $table->decimal('retail_price', 15, 4)->default(0.0000);
            $table->decimal('wholesale_price', 15, 4)->default(0.0000);
            $table->decimal('min_wholesale_qty', 15, 4)->default(10.0000);
            $table->decimal('gst_rate', 5, 2)->default(18.00);
            $table->enum('tax_type', ['exclusive', 'inclusive'])->default('exclusive');
            $table->decimal('alert_quantity', 15, 4)->default(10.0000);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'sku']);
            $table->index(['business_id', 'barcode']);
            $table->index(['business_id', 'name']);
        });

        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('conversion_factor', 15, 4); // e.g. 1 Box = 12 PCS -> factor 12
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'unit_id']);
        });

        Schema::create('product_branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->decimal('custom_retail_price', 15, 4)->nullable();
            $table->decimal('custom_wholesale_price', 15, 4)->nullable();
            $table->decimal('reorder_level', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_branch_settings');
        Schema::dropIfExists('product_unit_conversions');
        Schema::dropIfExists('products');
    }
};
