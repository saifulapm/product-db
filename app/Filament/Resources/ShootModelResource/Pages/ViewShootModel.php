<?php

namespace App\Filament\Resources\ShootModelResource\Pages;

use App\Filament\Resources\ShootModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShootModel extends ViewRecord
{
    protected static string $resource = ShootModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
