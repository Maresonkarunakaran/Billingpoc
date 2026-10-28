<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0.0000);
            $table->decimal('avg_cost', 15, 4)->default(0.0000);
            $table->decimal('total_value', 15, 4)->default(0.0000);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
            $table->index(['business_id', 'quantity']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('movement_type', [
                'purchase_receive',
                'sale',
                'sale_return',
                'purchase_return',
                'transfer_in',
                'transfer_out',
                'adjustment',
                'opening_stock',
            ]);
            $table->decimal('quantity_change', 15, 4); // positive or negative
            $table->decimal('previous_balance', 15, 4);
            $table->decimal('current_balance', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->decimal('total_cost', 15, 4)->default(0.0000);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['branch_id', 'product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_balances');
    }
};
