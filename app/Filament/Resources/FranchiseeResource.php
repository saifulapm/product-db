<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FranchiseeResource\Pages;
use App\Filament\Resources\FranchiseeResource\RelationManagers;
use App\Models\Franchisee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FranchiseeResource extends Resource
{
    protected static ?string $model = Franchisee::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Franchisees';

    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Franchisee Information')
                    ->schema([
                        Forms\Components\Select::make('company')
                            ->label('Company')
                            ->required()
                            ->options([
                                'alpha-fit-club' => 'Alpha Fit Club',
                                'barre3' => 'Barre3',
                                'basecamp-fitness' => 'Basecamp Fitness',
                                'bodybar' => 'Bodybar',
                                'bodyrok' => 'Bodyrok',
                                'f45' => 'F45',
                                'fitstop' => 'Fitstop',
                                'pvolve' => 'Pvolve',
                                'starcycle' => 'Starcycle',
                                'the-bar-method' => 'The Bar Method',
                                'title-boxing' => 'Title Boxing',
                            ])
                            ->searchable(),
                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('franchisee_name')
                            ->label('Franchisee Name')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Franchisee Logos')
                    ->description('Upload up to 10 logo files for this franchisee.')
                    ->schema([
                        Forms\Components\FileUpload::make('logos')
                            ->label('Logos')
                            ->image()
                            ->multiple()
                            ->maxFiles(10)
                            ->reorderable()
                            ->directory('franchisees/logos')
                            ->disk('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('100')
                            ->downloadable()
                            ->openable()
                            ->helperText('PNG, JPG, SVG — up to 10 files.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('franchisee_name')
                    ->label('Franchisee Name')
                    ->searchable()
                    ->sortable(),
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
            'index' => Pages\ListFranchisees::route('/'),
            'create' => Pages\CreateFranchisee::route('/create'),
            'view' => Pages\ViewFranchisee::route('/{record}'),
            'edit' => Pages\EditFranchisee::route('/{record}/edit'),
        ];
    }
}
