<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;
use App\Models\Role;

echo "Adding incoming shipments permissions...\n\n";

// Create the permissions
$permissions = [
    ['name' => 'View Incoming Shipments', 'slug' => 'incoming-shipments.view', 'resource' => 'sock-pre-orders', 'action' => 'incoming-shipments.view'],
    ['name' => 'Create Incoming Shipments', 'slug' => 'incoming-shipments.create', 'resource' => 'sock-pre-orders', 'action' => 'incoming-shipments.create'],
    ['name' => 'Update Incoming Shipments', 'slug' => 'incoming-shipments.update', 'resource' => 'sock-pre-orders', 'action' => 'incoming-shipments.update'],
];

foreach ($permissions as $permissionData) {
    $permission = Permission::firstOrCreate(
        ['slug' => $permissionData['slug']],
        $permissionData
    );
    
    if ($permission->wasRecentlyCreated) {
        echo "✓ Created permission: {$permission->name} ({$permission->slug})\n";
    } else {
        echo "→ Permission already exists: {$permission->name} ({$permission->slug})\n";
    }
}

// Assign to Super Admin role
$superAdminRole = Role::where('slug', 'super-admin')->first();
if ($superAdminRole) {
    $permissionIds = Permission::whereIn('slug', array_column($permissions, 'slug'))->pluck('id');
    $superAdminRole->permissions()->syncWithoutDetaching($permissionIds);
    echo "\n✓ Assigned permissions to Super Admin role\n";
} else {
    echo "\n⚠ Super Admin role not found\n";
}

echo "\nDone!\n";

