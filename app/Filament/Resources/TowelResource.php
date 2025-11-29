<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TowelResource\Pages;
use App\Models\Towel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TowelResource extends Resource
{
    protected static ?string $model = Towel::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Towels';

    protected static ?string $navigationGroup = 'Towels';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('towels.view');
    }

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Towel Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Towel Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('size')
                            ->label('Size / Dimensions')
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
                            ->placeholder('Add color names or hex codes'),
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
                    ->label('Towel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('size')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('material')
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('material')
                    ->label('Material')
                    ->options(fn () => Towel::query()
                        ->distinct()
                        ->pluck('material', 'material')
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
            'index' => Pages\ManageTowels::route('/'),
        ];
    }
}


