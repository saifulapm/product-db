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

    protected static ?int $navigationSort = 1;

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
                    ->sortable(),
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
            'edit' => Pages\EditSupply::route('/{record}/edit'),
        ];
    }
}
