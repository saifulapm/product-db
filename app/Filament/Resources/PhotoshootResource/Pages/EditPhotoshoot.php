<?php

namespace App\Filament\Resources\PhotoshootResource\Pages;

use App\Filament\Resources\PhotoshootResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhotoshoot extends EditRecord
{
    protected static string $resource = PhotoshootResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
