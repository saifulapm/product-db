<?php

namespace App\Filament\Resources\GarmentResource\Pages;

use App\Filament\Resources\GarmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGarments extends ListRecords
{
    protected static string $resource = GarmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
