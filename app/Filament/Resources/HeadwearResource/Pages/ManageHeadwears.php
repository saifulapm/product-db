<?php

namespace App\Filament\Resources\HeadwearResource\Pages;

use App\Filament\Resources\HeadwearResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHeadwears extends ManageRecords
{
    protected static string $resource = HeadwearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

