<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_cash_registers', function (Blueprint $table) {
            $table->decimal('wasteages', 15, 2)->default(0.00)->after('other_expenses_note');
            $table->string('wasteages_note', 255)->nullable()->after('wasteages');
        });
    }

    public function down(): void
    {
        Schema::table('daily_cash_registers', function (Blueprint $table) {
            $table->dropColumn(['wasteages', 'wasteages_note']);
        });
    }
};
