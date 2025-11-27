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
    protected static ?string $navigationGroup = 'Socks';
    protected static ?int $navigationSort = 4;

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
                
                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('expected_date')
                            ->label('Expected Date')
                            ->displayFormat('M d, Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('received_date')
                            ->label('Received Date')
                            ->displayFormat('M d, Y')
                            ->native(false),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Items')
                    ->description('Enter items from the packing slip. Each row represents one line item.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\TextInput::make('carton_number')
                                    ->label('CTN#')
                                    ->maxLength(50)
                                    ->placeholder('e.g., 1, 2, 3')
                                    ->helperText('Carton number from packing slip'),
                                Forms\Components\TextInput::make('style')
                                    ->label('Style')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Ballet, Scallop, Crew, Double Stripe')
                                    ->helperText('Sock style name'),
                                Forms\Components\TextInput::make('color')
                                    ->label('Color')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Black, Blush / Cherry, White')
                                    ->helperText('Color or color combination'),
                                Forms\Components\Select::make('packing_way')
                                    ->label('Packing Way')
                                    ->options([
                                        'hook' => 'Hook',
                                        'Sleeve Wrap' => 'Sleeve Wrap',
                                    ])
                                    ->default('hook')
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('#PC/CTN')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->helperText('Pieces per carton'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Item')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                ($state['style'] ?? '') . 
                                ($state['color'] ? ' - ' . $state['color'] : '') . 
                                ($state['quantity'] ? ' (' . $state['quantity'] . ' pcs)' : '')
                            )
                            ->columns(5),
                    ]),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder('Enter any additional notes about this shipment...'),
                    ]),
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
                Tables\Columns\TextColumn::make('carrier')
                    ->label('Carrier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier')
                    ->label('Supplier')
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
                Tables\Columns\TextColumn::make('expected_date')
                    ->label('Expected Date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('received_date')
                    ->label('Received Date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items')
                    ->label('Items')
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || !is_array($state)) {
                            return 'No items';
                        }
                        $count = count($state);
                        $totalQty = array_sum(array_column($state, 'quantity'));
                        $uniqueStyles = count(array_unique(array_column($state, 'style')));
                        return "{$count} line(s) - {$uniqueStyles} style(s) - {$totalQty} total pcs";
                    })
                    ->wrap(),
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
                Tables\Filters\Filter::make('expected_date')
                    ->form([
                        Forms\Components\DatePicker::make('expected_from')
                            ->label('Expected From'),
                        Forms\Components\DatePicker::make('expected_until')
                            ->label('Expected Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['expected_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expected_date', '>=', $date),
                            )
                            ->when(
                                $data['expected_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expected_date', '<=', $date),
                            );
                    }),
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
            ->defaultSort('expected_date', 'desc');
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
