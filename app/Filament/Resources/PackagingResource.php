<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackagingResource\Pages;
use App\Filament\Resources\PackagingResource\RelationManagers;
use App\Models\Packaging;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackagingResource extends Resource
{
    protected static ?string $model = Packaging::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Packaging';
    protected static ?string $modelLabel = 'Packaging';
    protected static ?string $pluralModelLabel = 'Packaging';
    protected static ?string $navigationGroup = 'Socks';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('packaging.view');
    }
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Packaging Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Bullet Points')
                    ->maxLength(1000)
                    ->rows(4),
                Forms\Components\Textarea::make('images')
                    ->label('Packaging Image URLs')
                    ->maxLength(1000)
                    ->rows(3),
                Forms\Components\TextInput::make('type')
                    ->label('Type')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Packaging Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Bullet Points')
                    ->limit(50),
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
            'index' => Pages\ListPackagings::route('/'),
            'create' => Pages\CreatePackaging::route('/create'),
            'view' => Pages\ViewPackaging::route('/{record}'),
            'edit' => Pages\EditPackaging::route('/{record}/edit'),
        ];
    }
}
