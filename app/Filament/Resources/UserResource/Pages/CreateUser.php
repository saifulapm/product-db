<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public ?array $roles = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the name field from first_name and last_name (required by database)
        if (!isset($data['name']) && isset($data['first_name']) && isset($data['last_name'])) {
            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        }
        
        // Store roles for afterCreate
        $this->roles = $data['roles'] ?? [];
        unset($data['roles']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Assign roles after user is created
        if (isset($this->roles) && !empty($this->roles)) {
            $this->record->roles()->sync($this->roles);
        }
    }
}