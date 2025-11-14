<?php

namespace App\Filament\Resources\SocksTwoResource\Pages;

use App\Filament\Resources\SocksTwoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocksTwo extends EditRecord
{
    protected static string $resource = SocksTwoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

