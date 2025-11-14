<?php

namespace App\Filament\Resources\HeadwearResource\Pages;

use App\Filament\Resources\HeadwearResource;
use App\Filament\Resources\HeadwearResource\Widgets\HeadwearHeader;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHeadwears extends ManageRecords
{
    protected static string $resource = HeadwearResource::class;

    public function getHeaderWidgets(): array
    {
        return [
            HeadwearHeader::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

