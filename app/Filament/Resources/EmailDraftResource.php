<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailDraftResource\Pages;
use App\Filament\Resources\EmailDraftResource\RelationManagers;
use App\Models\EmailDraft;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmailDraftResource extends Resource
{
    protected static ?string $model = EmailDraft::class;
    
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationLabel = 'Email Drafts';
    
    protected static ?string $navigationGroup = 'Customer Service';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Email Draft';
    
    protected static ?string $pluralModelLabel = 'Email Drafts';
    
    public static function getModelLabel(): string
    {
        return 'Email Draft';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Email Drafts';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('department')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan('full'),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                //
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ManageEmailDrafts::route('/'),
            'create' => Pages\CreateEmailDraft::route('/create'),
            'edit' => Pages\EditEmailDraft::route('/{record}/edit'),
        ];
    }
}

