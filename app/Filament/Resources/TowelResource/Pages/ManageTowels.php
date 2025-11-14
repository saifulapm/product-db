<?php

namespace App\Filament\Resources\TowelResource\Pages;

use App\Filament\Resources\TowelResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTowels extends ManageRecords
{
    protected static string $resource = TowelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
