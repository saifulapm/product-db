<?php

namespace App\Filament\Resources\MockupsSubmissionResource\Pages;

use App\Filament\Resources\MockupsSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMockupsSubmission extends CreateRecord
{
    protected static string $resource = MockupsSubmissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate tracking number
        $lastSubmission = \App\Models\MockupsSubmission::orderBy('tracking_number', 'desc')->first();
        $data['tracking_number'] = $lastSubmission ? $lastSubmission->tracking_number + 1 : 1;
        
        // Set created_by
        $data['created_by'] = auth()->id();
        
        return $data;
    }
}
