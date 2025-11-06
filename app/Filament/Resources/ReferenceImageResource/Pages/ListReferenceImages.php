<?php

namespace App\Filament\Resources\ReferenceImageResource\Pages;

use App\Filament\Resources\ReferenceImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferenceImages extends ListRecords
{
    protected static string $resource = ReferenceImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
