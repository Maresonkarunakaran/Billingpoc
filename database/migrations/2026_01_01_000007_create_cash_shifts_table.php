<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0.00);
            $table->decimal('cash_sales', 15, 2)->default(0.00);
            $table->decimal('cash_collections', 15, 2)->default(0.00);
            $table->decimal('cash_expenses', 15, 2)->default(0.00);
            $table->decimal('cash_refunds', 15, 2)->default(0.00);
            $table->decimal('closing_cash_system', 15, 2)->default(0.00);
            $table->decimal('closing_cash_physical', 15, 2)->nullable();
            $table->decimal('discrepancy', 15, 2)->default(0.00);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_shifts');
    }
};
