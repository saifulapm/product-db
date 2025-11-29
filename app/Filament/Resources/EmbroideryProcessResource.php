<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmbroideryProcessResource\Pages;
use App\Models\EmbroideryProcess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmbroideryProcessResource extends Resource
{
    protected static ?string $model = EmbroideryProcess::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Embroidery Process';

    protected static ?string $navigationGroup = 'Embroidery';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('embroidery-process.view');
    }

    protected static ?int $navigationSort = -1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('step_number')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->label('Step Number'),
                Forms\Components\TextInput::make('step_title')
                    ->maxLength(255)
                    ->label('Step Title'),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->maxLength(1000)
                    ->label('Description'),
                Forms\Components\TextInput::make('equipment_required')
                    ->maxLength(255)
                    ->label('Equipment Required'),
                Forms\Components\TextInput::make('materials_needed')
                    ->maxLength(255)
                    ->label('Materials Needed'),
                Forms\Components\TextInput::make('estimated_time')
                    ->maxLength(255)
                    ->label('Estimated Time'),
                Forms\Components\Textarea::make('special_notes')
                    ->rows(3)
                    ->maxLength(500)
                    ->label('Special Notes'),
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
                Tables\Columns\TextColumn::make('step_number')
                    ->sortable(),
                Tables\Columns\TextColumn::make('step_title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_time')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
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
            'index' => Pages\ManageEmbroideryProcesses::route('/'),
            'create' => Pages\CreateEmbroideryProcess::route('/create'),
            'edit' => Pages\EditEmbroideryProcess::route('/{record}/edit'),
        ];
    }
}

