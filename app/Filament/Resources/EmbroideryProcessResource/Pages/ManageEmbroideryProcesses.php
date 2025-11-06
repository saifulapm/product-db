<?php

namespace App\Filament\Resources\EmbroideryProcessResource\Pages;

use App\Filament\Resources\EmbroideryProcessResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEmbroideryProcesses extends ManageRecords
{
    protected static string $resource = EmbroideryProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}




