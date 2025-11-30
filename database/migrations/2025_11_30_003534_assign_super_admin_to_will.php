<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find or create the super-admin role
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full access to all features',
                'is_active' => true,
            ]
        );

        // Find the user by email
        $user = User::where('email', 'will@ethos.community')->first();

        if ($user && $superAdminRole) {
            // Assign super-admin role to the user (using syncWithoutDetaching to avoid duplicates)
            DB::table('user_roles')->insertOrIgnore([
                'user_id' => $user->id,
                'role_id' => $superAdminRole->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Find the user and super-admin role
        $user = User::where('email', 'will@ethos.community')->first();
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        if ($user && $superAdminRole) {
            // Remove super-admin role from the user
            DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $superAdminRole->id)
                ->delete();
        }
    }
};
