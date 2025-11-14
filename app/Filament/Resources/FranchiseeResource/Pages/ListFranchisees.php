<?php

namespace App\Filament\Resources\FranchiseeResource\Pages;

use App\Filament\Resources\FranchiseeResource;
use App\Filament\Resources\FranchiseeResource\Widgets\FranchiseesHeader;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFranchisees extends ListRecords
{
    protected static string $resource = FranchiseeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            FranchiseesHeader::class,
        ];
    }
}
