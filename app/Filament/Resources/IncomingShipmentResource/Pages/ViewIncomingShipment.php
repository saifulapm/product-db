<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Notifications\Notification;

class ViewIncomingShipment extends ViewRecord
{
    protected static string $resource = IncomingShipmentResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('status')
                ->label(fn () => match($this->record->status) {
                    'shipped' => 'Shipped',
                    'shipped_track' => 'Shipped with Tracking',
                    'partially_received' => 'Partially Received',
                    'received' => 'Received',
                    default => $this->record->status ?? 'Shipped',
                })
                ->badge()
                ->color(fn () => match($this->record->status) {
                    'shipped' => 'gray',
                    'shipped_track' => 'info',
                    'partially_received' => 'warning',
                    'received' => 'success',
                    default => 'gray',
                })
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'shipped' => 'Shipped',
                            'shipped_track' => 'Shipped with Tracking',
                            'partially_received' => 'Partially Received',
                            'received' => 'Received',
                        ])
                        ->default($this->record->status ?? 'shipped')
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['status' => $data['status']]);
                    Notification::make()
                        ->title('Status updated successfully')
                        ->success()
                        ->send();
                })
                ->icon('heroicon-o-flag'),
            Actions\EditAction::make(),
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            IncomingShipmentResource\Widgets\ShipmentContentsWidget::class,
            IncomingShipmentResource\Widgets\ShipmentTimelineWidget::class,
        ];
    }
}
