<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HowToResource\Pages;
use App\Filament\Resources\HowToResource\RelationManagers;
use App\Models\HowTo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HowToResource extends Resource
{
    protected static ?string $model = HowTo::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'How To\'s';
    
    protected static ?string $navigationGroup = 'Customer Service';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('how-tos.view');
    }
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('steps')
                    ->schema([
                        Forms\Components\Textarea::make('step')
                            ->required()
                            ->rows(2)
                            ->placeholder('Enter step description...'),
                    ])
                    ->defaultItems(3)
                    ->addActionLabel('Add Step')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->label('Sort Order'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('description')
                    ->limit(100)
                    ->searchable(),
                Tables\Columns\TextColumn::make('steps')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' steps' : '0 steps'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ManageHowTos::route('/'),
            'create' => Pages\CreateHowTo::route('/create'),
            'edit' => Pages\EditHowTo::route('/{record}/edit'),
        ];
    }
}
