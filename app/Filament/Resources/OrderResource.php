<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $modelLabel = 'Order';
    protected static ?string $pluralModelLabel = 'Orders';
    protected static ?string $navigationGroup = 'Socks';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Order Number')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., BDR1399')
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('client_name')
                            ->label('Client Name')
                            ->maxLength(255)
                            ->placeholder('Enter client name'),
                        Forms\Components\Select::make('incoming_shipment_id')
                            ->label('Source Shipment')
                            ->relationship('incomingShipment', 'tracking_number', fn ($query) => 
                                $query->where('status', 'received')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                ($record->tracking_number ?: 'No Tracking') . 
                                ($record->supplier ? ' - ' . $record->supplier : '') .
                                ($record->expected_date ? ' (' . $record->expected_date->format('M d, Y') . ')' : '')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the incoming shipment to pick items from'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'picking' => 'Picking',
                                'picked' => 'Picked',
                                'shipped' => 'Shipped',
                                'completed' => 'Completed',
                            ])
                            ->default('pending')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Order Items')
                    ->description('Enter items needed for this order. These will be allocated from the selected shipment.')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., BODYROK 1/2 Crew Sock - Black - Hook')
                                    ->helperText('Full item description'),
                                Forms\Components\TextInput::make('quantity_required')
                                    ->label('# Required')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->helperText('Quantity needed for this order'),
                                Forms\Components\TextInput::make('warehouse_location')
                                    ->label('Warehouse Location')
                                    ->maxLength(255)
                                    ->placeholder('Optional location'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Item')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                ($state['description'] ?? '') . 
                                ($state['quantity_required'] ? ' (' . $state['quantity_required'] . ' pcs)' : '')
                            )
                            ->columns(3),
                    ]),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder('Enter any additional notes about this order...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('incomingShipment.tracking_number')
                    ->label('Shipment')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'picking' => 'info',
                        'picked' => 'success',
                        'shipped' => 'primary',
                        'completed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'picking' => 'Picking',
                        'picked' => 'Picked',
                        'shipped' => 'Shipped',
                        'completed' => 'Completed',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('items')
                    ->label('Items')
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || !is_array($state)) {
                            return 'No items';
                        }
                        $count = count($state);
                        $totalQty = array_sum(array_column($state, 'quantity_required'));
                        return "{$count} item(s) - {$totalQty} total qty";
                    }),
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
                        'picking' => 'Picking',
                        'picked' => 'Picked',
                        'shipped' => 'Shipped',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('incoming_shipment_id')
                    ->label('Shipment')
                    ->relationship('incomingShipment', 'tracking_number'),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
