<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SockStyleResource\Pages;
use App\Filament\Resources\SockStyleResource\RelationManagers;
use App\Models\SockStyle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SockStyleResource extends Resource
{
    protected static ?string $model = SockStyle::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Unique Sock Product';
    protected static ?string $modelLabel = 'Unique Sock Product';
    protected static ?string $pluralModelLabel = 'Unique Sock Products';
    protected static ?string $navigationGroup = 'Sock Pre Orders';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Unique Sock Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., Athletic Crew - Black, Dress Socks - Navy')
                            ->helperText('Include style and color in the product name'),
                        Forms\Components\TextInput::make('eid')
                            ->label('Ethos ID (EiD)')
                            ->maxLength(255)
                            ->placeholder('e.g., EID-12345')
                            ->helperText('Enter the Ethos ID for this product'),
                        Forms\Components\Select::make('packaging_style')
                            ->label('Packaging Style')
                            ->options([
                                'Hook' => 'Hook',
                                'Sleeve Wrap' => 'Sleeve Wrap',
                                'Elastic Loop' => 'Elastic Loop',
                            ])
                            ->placeholder('Select packaging style'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn ($record) => SockStyleResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('eid')
                    ->label('Ethos ID (EiD)')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('packaging_style')
                    ->label('Packaging Style')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Hook' => 'info',
                        'Sleeve Wrap' => 'success',
                        'Elastic Loop' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(fn ($record) => true)
            ->defaultSort('name', 'asc');
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
            'index' => Pages\ListSockStyles::route('/'),
            'create' => Pages\CreateSockStyle::route('/create'),
            'view' => Pages\ViewSockStyle::route('/{record}'),
            'edit' => Pages\EditSockStyle::route('/{record}/edit'),
        ];
    }
}
