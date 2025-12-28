<?php

namespace App\Filament\Resources\PhotoshootResource\Pages;

use App\Filament\Resources\PhotoshootResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPhotoshoot extends ViewRecord
{
    protected static string $resource = PhotoshootResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
