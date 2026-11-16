<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_distributor')->default(false)->after('is_active');
            $table->index(['business_id', 'is_distributor']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'is_distributor']);
            $table->dropColumn('is_distributor');
        });
    }
};
