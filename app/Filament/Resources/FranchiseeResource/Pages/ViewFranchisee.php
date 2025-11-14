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
            Actions\Action::make('previous')
                ->label('Previous')
                ->icon('heroicon-o-chevron-left')
                ->color('gray')
                ->url(function () {
                    $previousFranchisee = \App\Models\Franchisee::where('id', '<', $this->record->id)
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    if ($previousFranchisee) {
                        return $this->getResource()::getUrl('view', ['record' => $previousFranchisee]);
                    }
                    
                    return null;
                })
                ->disabled(function () {
                    return !\App\Models\Franchisee::where('id', '<', $this->record->id)->exists();
                })
                ->tooltip('View previous franchisee'),
            Actions\Action::make('next')
                ->label('Next')
                ->icon('heroicon-o-chevron-right')
                ->color('gray')
                ->url(function () {
                    $nextFranchisee = \App\Models\Franchisee::where('id', '>', $this->record->id)
                        ->orderBy('id', 'asc')
                        ->first();
                    
                    if ($nextFranchisee) {
                        return $this->getResource()::getUrl('view', ['record' => $nextFranchisee]);
                    }
                    
                    return null;
                })
                ->disabled(function () {
                    return !\App\Models\Franchisee::where('id', '>', $this->record->id)->exists();
                })
                ->tooltip('View next franchisee'),
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
