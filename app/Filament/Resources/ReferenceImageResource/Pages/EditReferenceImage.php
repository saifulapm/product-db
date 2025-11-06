<?php

namespace App\Filament\Resources\ReferenceImageResource\Pages;

use App\Filament\Resources\ReferenceImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReferenceImage extends EditRecord
{
    protected static string $resource = ReferenceImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
