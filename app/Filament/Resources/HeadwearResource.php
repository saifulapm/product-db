<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeadwearResource\Pages;
use App\Models\Headwear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HeadwearResource extends Resource
{
    protected static ?string $model = Headwear::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Headwear';

    protected static ?string $navigationGroup = 'Headwear';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Headwear Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('style')
                            ->label('Style')
                            ->maxLength(150),
                        Forms\Components\TextInput::make('material')
                            ->label('Material')
                            ->maxLength(150),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(2000)
                            ->rows(5),
                        Forms\Components\TagsInput::make('colorways')
                            ->label('Colorways')
                            ->placeholder('Add color names or variations'),
                        Forms\Components\TextInput::make('decorations')
                            ->label('Decoration Methods')
                            ->helperText('e.g., Embroidery, Patch, Screenprint')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('image_url')
                            ->label('Reference Image URL')
                            ->url()
                            ->maxLength(500),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Listing')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Headwear')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('style')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('material')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('decorations')
                    ->label('Decorations')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\SelectFilter::make('style')
                    ->label('Style')
                    ->options(fn () => Headwear::query()
                        ->distinct()
                        ->pluck('style', 'style')
                        ->filter()
                        ->toArray()),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHeadwears::route('/'),
        ];
    }
}


