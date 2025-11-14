<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_active'] = true;
        
        // Ensure solutions is properly formatted as array
        if (isset($data['solutions']) && is_array($data['solutions'])) {
            // Filter out empty solutions (must have either title or solution)
            $data['solutions'] = array_filter($data['solutions'], function($solution) {
                return !empty($solution['solution']) || !empty($solution['title']);
            });
            // Re-index array
            $data['solutions'] = array_values($data['solutions']);
        }
        
        return $data;
    }
}
