<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">User Permissions Management</x-slot>
            <x-slot name="description">
                Manage user roles and permissions. Assign the Super Admin role to give team members full access to all features.
            </x-slot>
            
            <div class="pt-4">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

