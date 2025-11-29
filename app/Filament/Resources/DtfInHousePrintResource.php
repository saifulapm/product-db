<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DtfInHousePrintResource\Pages;
use App\Filament\Resources\DtfInHousePrintResource\RelationManagers;
use App\Models\DtfInHousePrint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DtfInHousePrintResource extends Resource
{
    protected static ?string $model = DtfInHousePrint::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Direct To Film';
    protected static ?string $modelLabel = 'DTF In House Print';
    protected static ?string $pluralModelLabel = 'DTF In House Prints';
    protected static ?string $navigationGroup = 'In House Print';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('dtf-in-house-print.view');
    }
    protected static ?int $navigationSort = 1;
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Color Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('e.g., Bright White, Neon Yellow'),
                Forms\Components\TextInput::make('hex_code')
                    ->label('Hex Code')
                    ->maxLength(255)
                    ->placeholder('e.g., #FF5733')
                    ->helperText('Enter the hex color code (e.g., #FF5733)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([]);
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
            'index' => Pages\ListDtfInHousePrints::route('/'),
        ];
    }

    public static function getTable(): Table
    {
        return parent::getTable()->columns([]);
    }
}
