<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 60)->nullable()->unique()->after('client_id');
            }
            if (! Schema::hasColumn('users', 'staff_id')) {
                $table->string('staff_id', 30)->nullable()->unique()->after('client_id');
            }
        });

        if (! Schema::hasTable('staff_permissions')) {
            Schema::create('staff_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('permission_key', 60);
                $table->boolean('is_allowed')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'permission_key']);
                $table->index(['user_id', 'permission_key', 'is_allowed']);
            });
        }

        // Ensure BILLING_STAFF role exists
        if (! Role::where('slug', 'billing-staff')->exists()) {
            Role::create([
                'name' => 'Billing Staff',
                'slug' => 'billing-staff',
                'description' => 'Billing counter and operations staff with owner-assigned page permissions.',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_permissions');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
