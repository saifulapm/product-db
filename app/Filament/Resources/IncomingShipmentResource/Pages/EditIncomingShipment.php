<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncomingShipment extends EditRecord
{
    protected static string $resource = IncomingShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
