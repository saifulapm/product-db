<?php

namespace App\Filament\Resources\MasterTaskResource\Pages;

use App\Filament\Resources\MasterTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterTask extends EditRecord
{
    protected static string $resource = MasterTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
