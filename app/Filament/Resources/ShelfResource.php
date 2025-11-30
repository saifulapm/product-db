<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShelfResource\Pages;
use App\Filament\Resources\ShelfResource\RelationManagers;
use App\Models\Shelf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShelfResource extends Resource
{
    protected static ?string $model = Shelf::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Shelves';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 3;
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shelf Information')
                    ->schema([
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->relationship('location', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->maxLength(50)
                                    ->unique(),
                            ]),
                        Forms\Components\TextInput::make('name')
                            ->label('Shelf Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Shelf A1, Row 3 Shelf 2'),
                        Forms\Components\TextInput::make('code')
                            ->label('Shelf Code')
                            ->maxLength(50)
                            ->placeholder('e.g., SH-A1, R3-S2')
                            ->unique(ignoreRecord: true)
                            ->helperText('Short code or abbreviation for this shelf'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Additional details about this shelf'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive shelves will not appear in inventory operations'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Shelf Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('location.name', 'asc')
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
            'index' => Pages\ListShelves::route('/'),
            'create' => Pages\CreateShelf::route('/create'),
            'view' => Pages\ViewShelf::route('/{record}'),
            'edit' => Pages\EditShelf::route('/{record}/edit'),
        ];
    }
}
