<?php

namespace App\Filament\Resources\DstFileResource\Pages;

use App\Filament\Resources\DstFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDstFiles extends ManageRecords
{
    protected static string $resource = DstFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}











