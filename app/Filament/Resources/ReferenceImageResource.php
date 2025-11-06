<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferenceImageResource\Pages;
use App\Filament\Resources\ReferenceImageResource\RelationManagers;
use App\Models\ReferenceImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReferenceImageResource extends Resource
{
    protected static ?string $model = ReferenceImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $navigationLabel = 'Reference Images';
    
    protected static ?string $modelLabel = 'Reference Image';
    
    protected static ?string $pluralModelLabel = 'Reference Images';
    
    protected static ?string $navigationGroup = 'Embroidery';
    
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form not used - we use the gallery page instead
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Table not used - we use the gallery page instead
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
            'index' => Pages\ReferenceImagesGallery::route('/'),
        ];
    }
}
