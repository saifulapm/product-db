<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert available_sizes string to array for TagsInput
        if (isset($data['available_sizes']) && is_string($data['available_sizes'])) {
            $data['available_sizes'] = array_filter(
                array_map('trim', explode(',', $data['available_sizes']))
            );
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert available_sizes array to comma-separated string if it's an array
        if (isset($data['available_sizes']) && is_array($data['available_sizes'])) {
            $data['available_sizes'] = implode(', ', array_filter($data['available_sizes']));
        }
        
        return $data;
    }
}
