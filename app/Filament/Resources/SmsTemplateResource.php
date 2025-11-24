<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsTemplateResource\Pages;
use App\Filament\Resources\SmsTemplateResource\RelationManagers;
use App\Models\SmsTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SmsTemplateResource extends Resource
{
    protected static ?string $model = SmsTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Admin';
    
    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Ready for Review'),
                Forms\Components\Textarea::make('description')
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder('Brief description of when to use this template'),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull()
                    ->placeholder('Hi {{customer_name}},

{{#tracking_number}}Your mockup submission #{{tracking_number}}{{/tracking_number}}{{#company_name}} for {{company_name}}{{/company_name}} is ready for review.

{{#submission_link}}View your submission: {{submission_link}}{{/submission_link}}

{{#notes}}Notes: {{notes}}{{/notes}}

Thank you!')
                    ->helperText('Available variables: {{customer_name}}, {{tracking_number}}, {{company_name}}, {{notes}}, {{submission_link}}. Use {{#variable}}...{{/variable}} for conditional blocks (only shows if variable has a value).'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('name')
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
            'index' => Pages\ListSmsTemplates::route('/'),
            'create' => Pages\CreateSmsTemplate::route('/create'),
            'edit' => Pages\EditSmsTemplate::route('/{record}/edit'),
        ];
    }
}
