<?php

namespace App\Filament\Resources\ShootModelResource\Pages;

use App\Filament\Resources\ShootModelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateShootModel extends CreateRecord
{
    protected static string $resource = ShootModelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate name from first_name + last_name if name is empty
        if (empty($data['name'])) {
            $nameParts = array_filter([
                $data['first_name'] ?? '',
                $data['last_name'] ?? ''
            ]);
            $data['name'] = !empty($nameParts) 
                ? trim(implode(' ', $nameParts)) 
                : ($data['email'] ?? 'Unknown');
        }
        
        return $data;
    }
}
