<?php

namespace App\Filament\Resources\EmbroideryProcessResource\Pages;

use App\Filament\Resources\EmbroideryProcessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmbroideryProcess extends EditRecord
{
    protected static string $resource = EmbroideryProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}










