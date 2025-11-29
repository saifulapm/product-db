<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactInfoResource\Pages;
use App\Filament\Resources\ContactInfoResource\RelationManagers;
use App\Models\ContactInfo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactInfoResource extends Resource
{
    protected static ?string $model = ContactInfo::class;
    
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    
    protected static ?string $navigationLabel = 'Contact Information';
    
    protected static ?string $navigationGroup = 'Customer Service';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('contact-info.view');
    }
    
    protected static ?int $navigationSort = 0;

    protected static ?string $modelLabel = 'Contact Info';
    
    protected static ?string $pluralModelLabel = 'Contact Info';
    
    public static function getModelLabel(): string
    {
        return 'Contact Info';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Contact Info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('department')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255)
                    ->label('Phone Number')
                    ->helperText('Enter the contact phone number'),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->maxLength(500)
                    ->helperText('Enter contact instructions or details'),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->label('Sort Order'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),
                Forms\Components\Toggle::make('is_emergency')
                    ->default(false)
                    ->label('Emergency Contact (24/7)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_emergency')
                    ->label('24/7')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_emergency')
                    ->label('Emergency Contacts'),
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
            'index' => Pages\ManageContactInfos::route('/'),
            'create' => Pages\CreateContactInfo::route('/create'),
            'edit' => Pages\EditContactInfo::route('/{record}/edit'),
        ];
    }
}
