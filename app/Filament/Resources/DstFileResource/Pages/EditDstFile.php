<?php

namespace App\Filament\Resources\DstFileResource\Pages;

use App\Filament\Resources\DstFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDstFile extends EditRecord
{
    protected static string $resource = DstFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}











