<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplyResource\Pages;
use App\Filament\Resources\SupplyResource\RelationManagers;
use App\Models\Supply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplyResource extends Resource
{
    protected static ?string $model = Supply::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Supplies';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'box' => 'Box',
                        'mailer' => 'Mailer',
                        'envelope' => 'Envelope',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->step(0.01)
                    ->suffix('lbs')
                    ->label('Weight (lbs)'),
                Forms\Components\TextInput::make('reorder_link')
                    ->url()
                    ->maxLength(255)
                    ->label('Reorder Link')
                    ->placeholder('https://example.com/reorder'),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->label('Quantity'),
                Forms\Components\TextInput::make('reorder_point')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->label('Reorder Point')
                    ->helperText('Email reminder will be sent when quantity reaches this level or below'),
                Forms\Components\Section::make('Cubic Measurements')
                    ->schema([
                        Forms\Components\TextInput::make('cubic_measurements.length')
                            ->label('Length (inches)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('in'),
                        Forms\Components\TextInput::make('cubic_measurements.width')
                            ->label('Width (inches)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('in'),
                        Forms\Components\TextInput::make('cubic_measurements.height')
                            ->label('Height (inches)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('in'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'box' => 'success',
                        'mailer' => 'info',
                        'envelope' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->reorder_point !== null && $record->quantity <= $record->reorder_point 
                            ? 'danger' 
                            : 'success'
                    ),
                Tables\Columns\TextColumn::make('reorder_point')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('average_monthly_usage')
                    ->label('Avg/Month')
                    ->state(function ($record) {
                        $tracking = $record->shipment_tracking ?? [];
                        
                        if (empty($tracking) || !is_array($tracking)) {
                            return 0;
                        }

                        $totalUsed = count($tracking);
                        $firstShipmentDate = null;
                        $lastShipmentDate = null;

                        foreach ($tracking as $shipment) {
                            if (empty($shipment['used_at'])) {
                                continue;
                            }

                            try {
                                $date = \Carbon\Carbon::parse($shipment['used_at']);
                                
                                if ($firstShipmentDate === null || $date->lt($firstShipmentDate)) {
                                    $firstShipmentDate = $date;
                                }
                                if ($lastShipmentDate === null || $date->gt($lastShipmentDate)) {
                                    $lastShipmentDate = $date;
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }

                        if ($firstShipmentDate && $lastShipmentDate) {
                            $monthsTracked = $firstShipmentDate->diffInMonths($lastShipmentDate) + 1;
                            if ($monthsTracked < 1) {
                                $monthsTracked = 1;
                            }
                            return $monthsTracked > 0 ? round($totalUsed / $monthsTracked, 1) : 0;
                        }

                        return 0;
                    })
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reorder_link')
                    ->label('Reorder')
                    ->formatStateUsing(fn ($state) => $state ? 'Reorder' : '—')
                    ->url(fn ($record) => $record->reorder_link)
                    ->openUrlInNewTab()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSupplies::route('/'),
            'create' => Pages\CreateSupply::route('/create'),
            'view' => Pages\ViewSupply::route('/{record}'),
            'edit' => Pages\EditSupply::route('/{record}/edit'),
        ];
    }
}
