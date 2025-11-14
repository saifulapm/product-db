<?php

namespace App\Filament\Resources\PatchResource\Pages;

use App\Filament\Resources\PatchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePatch extends CreateRecord
{
    protected static string $resource = PatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values
        if (empty($data['minimums'])) {
            $data['minimums'] = '10pcs';
        }
        
        if (empty($data['backing'])) {
            $data['backing'] = 'Iron On';
        }
        
        // Handle file upload - convert array to string for single file upload
        if (isset($data['image_reference']) && is_array($data['image_reference'])) {
            $data['image_reference'] = !empty($data['image_reference']) ? $data['image_reference'][0] : null;
        }
        
        return $data;
    }
}
