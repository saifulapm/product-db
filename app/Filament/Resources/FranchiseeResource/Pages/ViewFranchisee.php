<?php

namespace App\Filament\Resources\FranchiseeResource\Pages;

use App\Filament\Resources\FranchiseeResource;
use App\Filament\Resources\FranchiseeResource\Widgets\FranchiseeLogosViewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewFranchisee extends ViewRecord
{
    protected static string $resource = FranchiseeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Franchisee Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('company')
                            ->label('Company'),
                        Infolists\Components\TextEntry::make('location')
                            ->label('Location'),
                        Infolists\Components\TextEntry::make('franchisee_name')
                            ->label('Franchisee Name')
                            ->default('—'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            FranchiseeLogosViewWidget::class,
        ];
    }
}
