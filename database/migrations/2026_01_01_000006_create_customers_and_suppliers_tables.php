<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('state_code', 2)->default('33');
            $table->string('state_name')->default('Tamil Nadu');
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0.00);
            $table->decimal('opening_balance', 15, 2)->default(0.00); // positive = customer owes us
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'phone']);
            $table->index(['business_id', 'name']);
            $table->index(['business_id', 'gstin']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('state_code', 2)->default('33');
            $table->string('state_name')->default('Tamil Nadu');
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0.00); // positive = we owe supplier
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'phone']);
            $table->index(['business_id', 'company_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
