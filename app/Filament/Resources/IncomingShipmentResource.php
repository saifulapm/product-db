<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomingShipmentResource\Pages;
use App\Filament\Resources\IncomingShipmentResource\RelationManagers;
use App\Models\IncomingShipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IncomingShipmentResource extends Resource
{
    protected static ?string $model = IncomingShipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Incoming Shipments';
    protected static ?string $modelLabel = 'Incoming Shipment';
    protected static ?string $pluralModelLabel = 'Incoming Shipments';
    protected static ?string $navigationGroup = 'Sock Pre Orders';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('incoming-shipments.view');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipment Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Shipment Name')
                            ->maxLength(255)
                            ->placeholder('e.g., BDR1399 Shipment, November Order, etc.')
                            ->helperText('A descriptive name for this shipment'),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Date Created')
                            ->displayFormat('M d, Y g:i A')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn ($record) => $record?->created_at ?? now())
                            ->visible(fn ($record) => $record !== null),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Optional description for this shipment')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking Number')
                            ->maxLength(255)
                            ->placeholder('Enter tracking number'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'shipped' => 'Shipped',
                                'shipped_track' => 'Shipped - Track',
                                'received' => 'Received',
                            ])
                            ->default('shipped')
                            ->required(),
                        Forms\Components\Hidden::make('items')
                            ->default([]),
                    ])
                    ->columns(2),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->default('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'shipped' => 'gray',
                        'shipped_track' => 'info',
                        'received' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'shipped' => 'Shipped',
                        'shipped_track' => 'Shipped - Track',
                        'received' => 'Received',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Tracking Number')
                    ->searchable()
                    ->sortable()
                    ->url(function ($record) {
                        if ($record->status === 'shipped_track' && !empty($record->tracking_number)) {
                            return static::getTrackingUrl($record->tracking_number, strtolower($record->carrier ?? ''));
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_transit' => 'In Transit',
                        'received' => 'Received',
                        'delayed' => 'Delayed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getTrackingUrl(string $trackingNumber, string $carrier = ''): string
    {
        $carrier = strtolower(trim($carrier));
        
        // Common tracking URL patterns
        if (strpos($carrier, 'ups') !== false) {
            return 'https://www.ups.com/track?tracknum=' . urlencode($trackingNumber);
        } elseif (strpos($carrier, 'fedex') !== false || strpos($carrier, 'fed') !== false) {
            return 'https://www.fedex.com/fedextrack/?trknbr=' . urlencode($trackingNumber);
        } elseif (strpos($carrier, 'dhl') !== false) {
            return 'https://www.dhl.com/en/express/tracking.html?AWB=' . urlencode($trackingNumber);
        } elseif (strpos($carrier, 'usps') !== false || strpos($carrier, 'us postal') !== false) {
            return 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=' . urlencode($trackingNumber);
        }
        
        // Default: try to detect carrier from tracking number format or use generic search
        // For now, return a generic tracking search URL
        return 'https://www.google.com/search?q=' . urlencode($trackingNumber . ' tracking');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomingShipments::route('/'),
            'create' => Pages\CreateIncomingShipment::route('/create'),
            'view' => Pages\ViewIncomingShipment::route('/{record}'),
            'view-pick-list' => Pages\ViewPickList::route('/{shipmentId}/pick-list/{pickListIndex}'),
            'edit' => Pages\EditIncomingShipment::route('/{record}/edit'),
        ];
    }
}
