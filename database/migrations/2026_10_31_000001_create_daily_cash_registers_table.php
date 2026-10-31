<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('register_date');
            $table->decimal('opening_cash', 15, 2)->default(0.00);
            $table->decimal('sales_amount', 15, 2)->default(0.00);
            $table->decimal('expenses_amount', 15, 2)->default(0.00);
            $table->decimal('other_expenses', 15, 2)->default(0.00);
            $table->string('other_expenses_note', 255)->nullable();
            $table->decimal('purchases_amount', 15, 2)->default(0.00);
            $table->decimal('closing_cash', 15, 2)->default(0.00);
            $table->decimal('closing_cash_physical', 15, 2)->nullable();
            $table->enum('status', ['open', 'closed'])->default('closed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'register_date']);
            $table->index(['business_id', 'register_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cash_registers');
    }
};
