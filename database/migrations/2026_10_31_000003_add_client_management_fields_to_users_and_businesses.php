<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('client_id', 30)->nullable()->unique()->after('id');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->text('admin_notes')->nullable()->after('last_login_at');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('store_type', 50)->default('shop')->after('name');
            $table->text('admin_notes')->nullable()->after('is_active');
        });

        // Backfill existing client owners with unique client IDs
        $clientUsers = DB::table('users')
            ->where('is_master_admin', false)
            ->whereNull('client_id')
            ->orderBy('id', 'asc')
            ->get();

        $index = 1;
        foreach ($clientUsers as $user) {
            $clientId = sprintf('CLT%04d', $index++);
            DB::table('users')->where('id', $user->id)->update(['client_id' => $clientId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'must_change_password', 'last_login_at', 'admin_notes']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['store_type', 'admin_notes']);
        });
    }
};
