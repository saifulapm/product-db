<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThreadColorResource\Pages;
use App\Models\ThreadColor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ThreadColorResource extends Resource
{
    protected static ?string $model = ThreadColor::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Thread Colors';

    protected static ?string $navigationGroup = 'Embroidery';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('thread-colors.view');
    }

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('color_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Thread Number'),
                Forms\Components\ColorPicker::make('hex_code')
                    ->label('Color Code / Hex Code')
                    ->required()
                    ->helperText('Select the hex color code for this thread')
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Sync hex_code to color_code
                        $set('color_code', $state);
                    })
                    ->default(fn ($record) => $record?->color_code ?: $record?->hex_code),
                Forms\Components\TextInput::make('color_code')
                    ->label('Color Code (Auto-filled)')
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Automatically synced from hex code')
                    ->default(fn ($record) => $record?->hex_code ?: $record?->color_code),
                Forms\Components\TextInput::make('image_url')
                    ->label('Thread Color Image URL')
                    ->url()
                    ->maxLength(500)
                    ->helperText('Paste the Shopify-hosted image URL here (e.g., https://cdn.shopify.com/s/files/1/.../1500.png)')
                    ->placeholder('https://cdn.shopify.com/s/files/1/.../thread-color.png'),
                Forms\Components\Textarea::make('used_in')
                    ->label('Used In')
                    ->rows(3)
                    ->helperText('List product colors where this thread is used (e.g., Navy Blue, Black Socks)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('color_name')
                    ->label('Thread Number')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.thread-colors.edit', $record))
                    ->color('primary'),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Swatch Image'),
                Tables\Columns\TextColumn::make('hex_code')
                    ->label('Color Code / Hex Code')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        // Use hex_code if available, fallback to color_code
                        $colorValue = $state ?: $record->color_code;
                        
                        if (!$colorValue) {
                            return '—';
                        }
                        
                        // Display color swatch and hex code
                        $hex = strtoupper($colorValue);
                        return view('filament.resources.thread-color-resource.columns.hex-code', [
                            'hex' => $hex,
                            'color' => $colorValue,
                        ])->render();
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('used_in')
                    ->label('Used In')
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
            ])
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('color_name');
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
            'index' => Pages\ManageThreadColors::route('/'),
            'create' => Pages\CreateThreadColor::route('/create'),
            'edit' => Pages\EditThreadColor::route('/{record}/edit'),
        ];
    }
}

