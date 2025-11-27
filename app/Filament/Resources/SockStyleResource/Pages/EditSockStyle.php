<?php

namespace App\Filament\Resources\SockStyleResource\Pages;

use App\Filament\Resources\SockStyleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSockStyle extends EditRecord
{
    protected static string $resource = SockStyleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
