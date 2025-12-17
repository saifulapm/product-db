<?php

namespace App\Filament\Resources\ShootModelResource\Pages;

use App\Filament\Resources\ShootModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShootModel extends EditRecord
{
    protected static string $resource = ShootModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-generate name from first_name + last_name if name is empty
        if (empty($data['name'])) {
            $nameParts = array_filter([
                $data['first_name'] ?? '',
                $data['last_name'] ?? ''
            ]);
            $data['name'] = !empty($nameParts) 
                ? trim(implode(' ', $nameParts)) 
                : ($data['email'] ?? $this->record->name ?? 'Unknown');
        }
        
        return $data;
    }
}
