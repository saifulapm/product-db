<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update the name field from first_name and last_name if they changed
        if (isset($data['first_name']) && isset($data['last_name'])) {
            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        }
        
        // Handle role assignment
        if (isset($data['roles'])) {
            $this->record->roles()->sync($data['roles']);
            unset($data['roles']);
        }
        
        // Handle permission assignment
        if (isset($data['permissions'])) {
            $this->record->permissions()->sync($data['permissions']);
            unset($data['permissions']);
        }
        
        return $data;
    }
}