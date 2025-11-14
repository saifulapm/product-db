<?php

namespace App\Filament\Resources\PatchResource\Pages;

use App\Filament\Resources\PatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPatch extends EditRecord
{
    protected static string $resource = PatchResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure defaults are set
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
