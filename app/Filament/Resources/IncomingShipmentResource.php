<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomingShipmentResource\Pages;
use App\Filament\Resources\IncomingShipmentResource\RelationManagers;
use App\Models\IncomingShipment;
use App\Models\SockStyle;
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
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking Number')
                            ->maxLength(255)
                            ->placeholder('Enter tracking number'),
                        Forms\Components\TextInput::make('carrier')
                            ->label('Carrier')
                            ->maxLength(255)
                            ->placeholder('e.g., UPS, FedEx, DHL'),
                        Forms\Components\TextInput::make('supplier')
                            ->label('Supplier')
                            ->maxLength(255)
                            ->placeholder('Enter supplier name'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'in_transit' => 'In Transit',
                                'received' => 'Received',
                                'delayed' => 'Delayed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
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
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Tracking Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_transit' => 'info',
                        'received' => 'success',
                        'delayed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'in_transit' => 'In Transit',
                        'received' => 'Received',
                        'delayed' => 'Delayed',
                        'cancelled' => 'Cancelled',
                        default => $state,
                    })
                    ->sortable(),
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
