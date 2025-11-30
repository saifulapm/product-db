<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public ?array $permissions = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store permissions for afterCreate
        $this->permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Assign permissions after role is created
        if (isset($this->permissions) && !empty($this->permissions)) {
            $this->record->permissions()->sync($this->permissions);
        }
    }
}
