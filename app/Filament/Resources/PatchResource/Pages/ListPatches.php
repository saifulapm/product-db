<?php

namespace App\Filament\Resources\PatchResource\Pages;

use App\Filament\Resources\PatchResource;
use App\Filament\Resources\PatchResource\Widgets\PatchesHeader;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPatches extends ListRecords
{
    protected static string $resource = PatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            PatchesHeader::class,
        ];
    }
}
