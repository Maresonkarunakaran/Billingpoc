<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('series_type', [
                'sale',
                'sale_return',
                'purchase',
                'purchase_return',
                'transfer',
                'expense',
                'payment',
            ]);
            $table->string('prefix', 10)->default('INV');
            $table->string('financial_year', 10); // e.g., '2026-27'
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'series_type', 'financial_year'], 'inv_seq_branch_type_fy_unique');
        });

        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('setting_key', 100);
            $table->longText('setting_value')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'setting_key']);
        });

        Schema::create('tax_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50); // e.g. "GST 18%", "GST 12%", "GST 5%", "GST 0%"
            $table->decimal('rate', 5, 2);
            $table->decimal('cgst_rate', 5, 2);
            $table->decimal('sgst_rate', 5, 2);
            $table->decimal('igst_rate', 5, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_configurations');
        Schema::dropIfExists('business_settings');
        Schema::dropIfExists('invoice_sequences');
    }
};
