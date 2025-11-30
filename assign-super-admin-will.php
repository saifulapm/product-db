<?php

/**
 * Script to assign super-admin role to will@ethos.community
 * Run this on production: php assign-super-admin-will.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "Assigning super-admin role to will@ethos.community...\n";

// Find or create the super-admin role
$superAdminRole = Role::firstOrCreate(
    ['slug' => 'super-admin'],
    [
        'name' => 'Super Admin',
        'description' => 'Full access to all features',
        'is_active' => true,
    ]
);

echo "Super-admin role found/created: {$superAdminRole->name} (ID: {$superAdminRole->id})\n";

// Find the user
$user = User::where('email', 'will@ethos.community')->first();

if (!$user) {
    echo "ERROR: User with email 'will@ethos.community' not found!\n";
    exit(1);
}

echo "User found: {$user->name} ({$user->email}) (ID: {$user->id})\n";

// Assign super-admin role
$user->assignRole($superAdminRole);

echo "Super-admin role assigned successfully!\n";

// Verify assignment
$hasRole = $user->hasRole('super-admin');
echo "Verification: User has super-admin role: " . ($hasRole ? 'YES' : 'NO') . "\n";

if ($hasRole) {
    echo "\n✅ Success! will@ethos.community now has super-admin access.\n";
} else {
    echo "\n❌ Warning: Role assignment may have failed. Please check manually.\n";
}

