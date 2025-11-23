<?php

namespace App\Filament\Resources\MockupsSubmissionResource\Pages;

use App\Filament\Resources\MockupsSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMockupsSubmissions extends ListRecords
{
    protected static string $resource = MockupsSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
