<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Filament\Resources\FaqResource\RelationManagers;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    
    protected static ?string $navigationLabel = 'FAQs';
    
    protected static ?string $navigationGroup = 'Customer Service';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('faqs.view');
    }
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('question')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('solutions')
                    ->label('Solutions')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Solution Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter a title for this solution...'),
                        Forms\Components\Textarea::make('solution')
                            ->label('Solution')
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter a solution...'),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('Add Solution')
                    ->reorderable(true)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? $state['solution'] ?? 'New Solution')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->searchable()
                    ->limit(50)
                    ->url(fn (Faq $record): string => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false)
                    ->color('primary'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'view' => Pages\ViewFaq::route('/{record}'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
