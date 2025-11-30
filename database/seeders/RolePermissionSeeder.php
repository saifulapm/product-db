<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for all navigation items
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'resource' => 'dashboard', 'action' => 'view'],
            
            // Tasks Group
            ['name' => 'View Tasks Home', 'slug' => 'tasks.home.view', 'resource' => 'tasks', 'action' => 'home.view'],
            ['name' => 'View All Tasks', 'slug' => 'tasks.all.view', 'resource' => 'tasks', 'action' => 'all.view'],
            ['name' => 'View Tasks Calendar', 'slug' => 'tasks.calendar.view', 'resource' => 'tasks', 'action' => 'calendar.view'],
            ['name' => 'View Task Types', 'slug' => 'tasks.types.view', 'resource' => 'tasks', 'action' => 'types.view'],
            
            // Design Tools Group
            ['name' => 'View Product Database', 'slug' => 'products.view', 'resource' => 'products', 'action' => 'view'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'resource' => 'products', 'action' => 'create'],
            ['name' => 'Update Products', 'slug' => 'products.update', 'resource' => 'products', 'action' => 'update'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'resource' => 'products', 'action' => 'delete'],
            ['name' => 'Import Products', 'slug' => 'products.import', 'resource' => 'products', 'action' => 'import'],
            ['name' => 'View Quick CAD Builder', 'slug' => 'quick-cad-builder.view', 'resource' => 'design-tools', 'action' => 'quick-cad.view'],
            ['name' => 'View Fabric Calculator', 'slug' => 'fabric-calculator.view', 'resource' => 'design-tools', 'action' => 'fabric-calculator.view'],
            ['name' => 'View Product Pricing', 'slug' => 'product-pricing.view', 'resource' => 'design-tools', 'action' => 'product-pricing.view'],
            
            // Mockups Group
            ['name' => 'View Mockup Submissions', 'slug' => 'mockups.submissions.view', 'resource' => 'mockups', 'action' => 'submissions.view'],
            
            // In House Print Group
            ['name' => 'View Direct To Film', 'slug' => 'dtf-in-house-print.view', 'resource' => 'in-house-print', 'action' => 'dtf.view'],
            ['name' => 'View Puff Print', 'slug' => 'puff-print.view', 'resource' => 'in-house-print', 'action' => 'puff-print.view'],
            
            // Embroidery Group
            ['name' => 'View Thread Colors', 'slug' => 'thread-colors.view', 'resource' => 'embroidery', 'action' => 'thread-colors.view'],
            ['name' => 'View Reference Images', 'slug' => 'reference-images.view', 'resource' => 'embroidery', 'action' => 'reference-images.view'],
            ['name' => 'View DST Files', 'slug' => 'dst-files.view', 'resource' => 'embroidery', 'action' => 'dst-files.view'],
            ['name' => 'View Embroidery Process', 'slug' => 'embroidery-process.view', 'resource' => 'embroidery', 'action' => 'embroidery-process.view'],
            
            // Headwear Group
            ['name' => 'View Hats', 'slug' => 'headwear.view', 'resource' => 'headwear', 'action' => 'view'],
            
            // Patches Group
            ['name' => 'View Patches', 'slug' => 'patches.view', 'resource' => 'patches', 'action' => 'view'],
            
            // Socks Group
            ['name' => 'View Sock Styles', 'slug' => 'socks.styles.view', 'resource' => 'socks', 'action' => 'styles.view'],
            ['name' => 'View Thread Book Colors', 'slug' => 'thread-book-colors.view', 'resource' => 'socks', 'action' => 'thread-book-colors.view'],
            ['name' => 'View Packaging', 'slug' => 'packaging.view', 'resource' => 'socks', 'action' => 'packaging.view'],
            ['name' => 'View Sock Grips', 'slug' => 'sock-grips.view', 'resource' => 'socks', 'action' => 'sock-grips.view'],
            ['name' => 'View Customization Methods', 'slug' => 'customization-methods.view', 'resource' => 'socks', 'action' => 'customization-methods.view'],
            ['name' => 'View Grips', 'slug' => 'grips.view', 'resource' => 'socks', 'action' => 'grips.view'],
            
            // Sock Pre Orders Group
            ['name' => 'View Incoming Shipments', 'slug' => 'incoming-shipments.view', 'resource' => 'sock-pre-orders', 'action' => 'incoming-shipments.view'],
            ['name' => 'Create Incoming Shipments', 'slug' => 'incoming-shipments.create', 'resource' => 'sock-pre-orders', 'action' => 'incoming-shipments.create'],
            ['name' => 'Update Incoming Shipments', 'slug' => 'incoming-shipments.update', 'resource' => 'sock-pre-orders', 'action' => 'incoming-shipments.update'],
            ['name' => 'View Packing Lists', 'slug' => 'packing-lists.view', 'resource' => 'sock-pre-orders', 'action' => 'packing-lists.view'],
            
            // Bottles Group
            ['name' => 'View Bottles', 'slug' => 'bottles.view', 'resource' => 'bottles', 'action' => 'view'],
            
            // Towels Group
            ['name' => 'View Towels', 'slug' => 'towels.view', 'resource' => 'towels', 'action' => 'view'],
            
            // Customer Service Group
            ['name' => 'View Email Drafts', 'slug' => 'email-drafts.view', 'resource' => 'customer-service', 'action' => 'email-drafts.view'],
            ['name' => 'View FAQs', 'slug' => 'faqs.view', 'resource' => 'customer-service', 'action' => 'faqs.view'],
            ['name' => 'View Contact Information', 'slug' => 'contact-info.view', 'resource' => 'customer-service', 'action' => 'contact-info.view'],
            ['name' => 'View Login Info', 'slug' => 'login-info.view', 'resource' => 'customer-service', 'action' => 'login-info.view'],
            ['name' => 'View How To\'s', 'slug' => 'how-tos.view', 'resource' => 'customer-service', 'action' => 'how-tos.view'],
            
            // Data Group
            ['name' => 'View Franchisees', 'slug' => 'franchisees.view', 'resource' => 'data', 'action' => 'franchisees.view'],
            ['name' => 'View Files', 'slug' => 'files.view', 'resource' => 'data', 'action' => 'files.view'],
            
            // Admin Group
            ['name' => 'View Team Members', 'slug' => 'users.view', 'resource' => 'users', 'action' => 'view'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'resource' => 'users', 'action' => 'create'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'resource' => 'users', 'action' => 'update'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'resource' => 'users', 'action' => 'delete'],
            ['name' => 'View Roles', 'slug' => 'roles.view', 'resource' => 'roles', 'action' => 'view'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'resource' => 'roles', 'action' => 'create'],
            ['name' => 'Update Roles', 'slug' => 'roles.update', 'resource' => 'roles', 'action' => 'update'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'resource' => 'roles', 'action' => 'delete'],
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'resource' => 'permissions', 'action' => 'view'],
            ['name' => 'View SMS Templates', 'slug' => 'sms-templates.view', 'resource' => 'admin', 'action' => 'sms-templates.view'],
            
            // System permissions
            ['name' => 'Access Admin Panel', 'slug' => 'admin.access', 'resource' => 'admin', 'action' => 'access'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'resource' => 'settings', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create roles
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full access to all features and settings',
                'permissions' => Permission::all()->pluck('slug')->toArray(),
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access to products and users',
                'permissions' => [
                    'dashboard.view',
                    'tasks.home.view', 'tasks.all.view', 'tasks.calendar.view', 'tasks.types.view',
                    'products.view', 'products.create', 'products.update', 'products.delete', 'products.import',
                    'quick-cad-builder.view', 'fabric-calculator.view', 'product-pricing.view',
                    'mockups.submissions.view',
                    'dtf-in-house-print.view', 'puff-print.view',
                    'thread-colors.view', 'reference-images.view', 'dst-files.view', 'embroidery-process.view',
                    'headwear.view',
                    'patches.view',
                    'socks.styles.view', 'thread-book-colors.view', 'packaging.view', 'sock-grips.view', 'customization-methods.view', 'grips.view',
                    'incoming-shipments.view', 'incoming-shipments.create', 'incoming-shipments.update', 'packing-lists.view',
                    'bottles.view',
                    'towels.view',
                    'email-drafts.view', 'faqs.view', 'contact-info.view', 'login-info.view', 'how-tos.view',
                    'franchisees.view', 'files.view',
                    'users.view', 'users.create', 'users.update',
                    'roles.view',
                    'permissions.view',
                    'sms-templates.view',
                    'admin.access',
                ],
            ],
            [
                'name' => 'Product Manager',
                'slug' => 'product-manager',
                'description' => 'Manage products and import data',
                'permissions' => [
                    'dashboard.view',
                    'tasks.home.view', 'tasks.all.view', 'tasks.calendar.view',
                    'products.view', 'products.create', 'products.update', 'products.import',
                    'quick-cad-builder.view', 'fabric-calculator.view', 'product-pricing.view',
                    'mockups.submissions.view',
                    'dtf-in-house-print.view', 'puff-print.view',
                    'thread-colors.view', 'reference-images.view',
                    'headwear.view',
                    'patches.view',
                    'socks.styles.view', 'thread-book-colors.view', 'packaging.view', 'sock-grips.view',
                    'incoming-shipments.view', 'incoming-shipments.create', 'incoming-shipments.update',
                    'bottles.view',
                    'towels.view',
                    'admin.access',
                ],
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'View-only access to products',
                'permissions' => [
                    'dashboard.view',
                    'tasks.home.view', 'tasks.all.view',
                    'products.view',
                    'quick-cad-builder.view',
                    'mockups.submissions.view',
                    'dtf-in-house-print.view',
                    'thread-colors.view',
                    'headwear.view',
                    'patches.view',
                    'socks.styles.view', 'thread-book-colors.view', 'packaging.view',
                    'bottles.view',
                    'towels.view',
                    'admin.access',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);
            
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
            
            // Assign permissions to role
            $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        // Assign Super Admin role to existing admin users
        $adminEmails = ['admin@ethos.com', 'admin@example.com', 'will@ethos.community'];
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        
        if ($superAdminRole) {
            foreach ($adminEmails as $email) {
                $adminUser = User::where('email', $email)->first();
                if ($adminUser) {
                    $adminUser->assignRole($superAdminRole);
                }
            }
        }
    }
}
