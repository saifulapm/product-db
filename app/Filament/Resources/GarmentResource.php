<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GarmentResource\Pages;
use App\Filament\Resources\GarmentResource\RelationManagers;
use App\Models\Garment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GarmentResource extends Resource
{
    protected static ?string $model = Garment::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationLabel = 'Garments';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Garment Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Garment Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., T-Shirt, Hoodie, Polo'),
                        Forms\Components\TextInput::make('code')
                            ->label('Garment Code')
                            ->maxLength(50)
                            ->placeholder('e.g., TS-001, HD-001')
                            ->unique(ignoreRecord: true)
                            ->helperText('Short code or abbreviation for this garment'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Additional details about this garment')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('variants')
                            ->default([]),
                        Forms\Components\Hidden::make('measurements')
                            ->default([]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Garment Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Quantity')
                    ->state(function (Garment $record): int {
                        $variants = $record->variants ?? [];
                        if (empty($variants) || !is_array($variants)) {
                            return 0;
                        }
                        
                        $total = 0;
                        foreach ($variants as $variant) {
                            $total += (int)($variant['inventory'] ?? 0);
                        }
                        
                        return $total;
                    })
                    ->numeric()
                    ->default(0),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Garments'),
            ])
            ->defaultSort('name', 'asc')
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
            'index' => Pages\ListGarments::route('/'),
            'create' => Pages\CreateGarment::route('/create'),
            'view' => Pages\ViewGarment::route('/{record}'),
            'edit' => Pages\EditGarment::route('/{record}/edit'),
        ];
    }
}
