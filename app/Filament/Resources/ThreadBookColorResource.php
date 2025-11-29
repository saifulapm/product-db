<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThreadBookColorResource\Pages;
use App\Filament\Resources\ThreadBookColorResource\RelationManagers;
use App\Models\ThreadBookColor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ThreadBookColorResource extends Resource
{
    protected static ?string $model = ThreadBookColor::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'Thread Book Colors';
    protected static ?string $modelLabel = 'Thread Book Color';
    protected static ?string $pluralModelLabel = 'Thread Book Colors';
    protected static ?string $navigationGroup = 'Socks';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('thread-book-colors.view');
    }
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Color Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Color Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Navy Blue, Crimson Red'),
                        Forms\Components\TextInput::make('color_code')
                            ->label('Thread Book Color Code')
                            ->maxLength(255)
                            ->placeholder('e.g., BC07, L118, M75'),
                        Forms\Components\TextInput::make('hex_code')
                            ->label('Hex Code')
                            ->maxLength(255)
                            ->placeholder('e.g., 000000, FFFFFF')
                            ->helperText('Enter the hex code without the # symbol'),
                        Forms\Components\TextInput::make('image_url')
                            ->label('Image URL')
                            ->maxLength(255)
                            ->url()
                            ->placeholder('e.g., https://example.com/image.jpg')
                            ->helperText('Enter the full URL to the color swatch image (4:5 ratio recommended)'),
                        Forms\Components\Select::make('color_category')
                            ->label('Color Category')
                            ->options([
                                'reds' => 'Reds',
                                'blues' => 'Blues',
                                'greens' => 'Greens',
                                'yellows' => 'Yellows',
                                'oranges' => 'Oranges',
                                'purples' => 'Purples',
                                'pinks' => 'Pinks',
                                'browns' => 'Browns',
                                'grays' => 'Grays',
                                'neutrals' => 'Neutrals',
                                'blacks' => 'Blacks',
                                'whites' => 'Whites',
                                'metallics' => 'Metallics',
                                'multi' => 'Multi',
                                'glitter' => 'Glitter',
                                'heather' => 'Heather',
                            ])
                            ->placeholder('Select a color category'),
                        Forms\Components\RichEditor::make('description')
                            ->label('Description/Notes')
                            ->placeholder('Enter your notes here. You can use HTML tags like <h3>Heading</h3> and <br> for line breaks.')
                            ->helperText('You can use HTML tags like <h3>, <h2>, <br>, <p>, <strong>, <em>, etc.')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Color')
                    ->state(fn (ThreadBookColor $record): ?string => $record->image_url ?: "https://via.placeholder.com/100/{$record->hex_code}/{$record->hex_code}?text=")
                    ->url(fn (ThreadBookColor $record): ?string => $record->image_url ?: "https://via.placeholder.com/100/{$record->hex_code}/{$record->hex_code}?text=")
                    ->width('100px')
                    ->height('125px'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Color Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (ThreadBookColor $record): string => route('filament.admin.resources.thread-book-colors.view', $record))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('color_code')
                    ->label('Thread Book Color Code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('hex_code')
                    ->label('Hex Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('color_category')
                    ->label('Color Category')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : ''),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('color_category')
                    ->label('Color Category')
                    ->options([
                        'reds' => 'Reds',
                        'blues' => 'Blues',
                        'greens' => 'Greens',
                        'yellows' => 'Yellows',
                        'oranges' => 'Oranges',
                        'purples' => 'Purples',
                        'pinks' => 'Pinks',
                        'browns' => 'Browns',
                        'grays' => 'Grays',
                        'neutrals' => 'Neutrals',
                        'blacks' => 'Blacks',
                        'whites' => 'Whites',
                        'metallics' => 'Metallics',
                        'multi' => 'Multi',
                        'glitter' => 'Glitter',
                        'heather' => 'Heather',
                    ]),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)
            ->persistFiltersInSession()
            ->filtersLayout(Tables\Enums\FiltersLayout::Dropdown)
            ->bulkActions([
                Tables\Actions\BulkAction::make('set_color_category')
                    ->label('Set Color Category')
                    ->icon('heroicon-o-swatch')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('color_category')
                            ->label('Color Category')
                            ->options([
                                'reds' => 'Reds',
                                'blues' => 'Blues',
                                'greens' => 'Greens',
                                'yellows' => 'Yellows',
                                'oranges' => 'Oranges',
                                'purples' => 'Purples',
                                'pinks' => 'Pinks',
                                'browns' => 'Browns',
                                'grays' => 'Grays',
                                'neutrals' => 'Neutrals',
                                'blacks' => 'Blacks',
                                'whites' => 'Whites',
                                'metallics' => 'Metallics',
                                'multi' => 'Multi',
                                'glitter' => 'Glitter',
                                'heather' => 'Heather',
                            ])
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $updated = 0;
                        foreach ($records as $record) {
                            $record->update(['color_category' => $data['color_category']]);
                            $updated++;
                        }

                        Notification::make()
                            ->title("Updated {$updated} thread color(s)")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Delete Threads')
                    ->icon('heroicon-o-trash'),
            ])
            ->actions([
                // No actions - clicking the color name will navigate to view page
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListThreadBookColors::route('/'),
            'create' => Pages\CreateThreadBookColor::route('/create'),
            'view' => Pages\ViewThreadBookColor::route('/{record}'),
            'edit' => Pages\EditThreadBookColor::route('/{record}/edit'),
        ];
    }
}
