<?php

namespace App\Filament\Resources\PhotoshootResource\Pages;

use App\Filament\Resources\PhotoshootResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhotoshoots extends ListRecords
{
    protected static string $resource = PhotoshootResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
