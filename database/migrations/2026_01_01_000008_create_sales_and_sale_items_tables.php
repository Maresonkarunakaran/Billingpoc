<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('cash_shifts')->nullOnDelete();
            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->enum('pricing_type', ['retail', 'wholesale'])->default('retail');
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_rate', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('taxable_amount', 15, 2)->default(0.00);
            $table->decimal('cgst_amount', 15, 2)->default(0.00);
            $table->decimal('sgst_amount', 15, 2)->default(0.00);
            $table->decimal('igst_amount', 15, 2)->default(0.00);
            $table->decimal('total_tax', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('round_off', 10, 2)->default(0.00);
            $table->decimal('final_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('balance_amount', 15, 2)->default(0.00);
            $table->decimal('cost_of_goods_sold', 15, 2)->default(0.00); // Historical COGS
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
            $table->enum('invoice_status', ['finalized', 'returned', 'cancelled'])->default('finalized');
            $table->string('place_of_supply', 100)->nullable();
            $table->boolean('is_interstate')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'invoice_number']);
            $table->index(['business_id', 'invoice_date']);
            $table->index(['branch_id', 'invoice_date']);
            $table->index(['payment_status', 'invoice_status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('cost_price', 15, 4); // Historical unit cost at checkout
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('taxable_amount', 15, 2);
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('gst_rate', 5, 2)->default(0.00);
            $table->decimal('cgst_rate', 5, 2)->default(0.00);
            $table->decimal('cgst_amount', 15, 2)->default(0.00);
            $table->decimal('sgst_rate', 5, 2)->default(0.00);
            $table->decimal('sgst_amount', 15, 2)->default(0.00);
            $table->decimal('igst_rate', 5, 2)->default(0.00);
            $table->decimal('igst_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();

            $table->index(['sale_id', 'product_id']);
        });

        Schema::create('sale_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->string('tax_name', 20); // CGST, SGST, IGST
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('cash_shifts')->nullOnDelete();
            $table->string('return_number', 50);
            $table->date('return_date');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('taxable_amount', 15, 2)->default(0.00);
            $table->decimal('cgst_amount', 15, 2)->default(0.00);
            $table->decimal('sgst_amount', 15, 2)->default(0.00);
            $table->decimal('igst_amount', 15, 2)->default(0.00);
            $table->decimal('total_tax', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('refund_amount', 15, 2)->default(0.00);
            $table->decimal('credit_note_amount', 15, 2)->default(0.00);
            $table->decimal('cost_reversed', 15, 2)->default(0.00);
            $table->string('refund_method', 30)->default('cash');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'return_number']);
            $table->index(['business_id', 'return_date']);
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('cost_price', 15, 4);
            $table->decimal('taxable_amount', 15, 2);
            $table->decimal('gst_rate', 5, 2)->default(0.00);
            $table->decimal('cgst_amount', 15, 2)->default(0.00);
            $table->decimal('sgst_amount', 15, 2)->default(0.00);
            $table->decimal('igst_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('sale_item_taxes');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
