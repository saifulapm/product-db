<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BottleResource\Pages;
use App\Models\Bottle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BottleResource extends Resource
{
    protected static ?string $model = Bottle::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Bottles';
    protected static ?string $modelLabel = 'Bottle';
    protected static ?string $pluralModelLabel = 'Bottles';
    protected static ?string $navigationGroup = 'Bottles';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bottle Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Bottle Style')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Water Bottle, Sport Bottle'),
                        Forms\Components\Textarea::make('description')
                            ->label('Bullet Points')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Enter each bullet point on a new line:' . PHP_EOL . '• Leak-proof design' . PHP_EOL . '• Durable material' . PHP_EOL . '• BPA-free'),
                        Forms\Components\Textarea::make('images')
                            ->label('Bottle Image URLs')
                            ->maxLength(1000)
                            ->placeholder('Enter image URLs (one per line):' . PHP_EOL . 'https://example.com/bottle1.jpg' . PHP_EOL . 'https://example.com/bottle2.jpg')
                            ->helperText('Enter 1-3 image URLs for the bottle style (one URL per line)')
                            ->rows(3),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Specifications')
                    ->schema([
                        Forms\Components\TextInput::make('material')
                            ->label('Material')
                            ->maxLength(255)
                            ->placeholder('e.g., Stainless Steel, Plastic'),
                        Forms\Components\TextInput::make('price')
                            ->label('Starting Price')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('minimums')
                            ->label('Minimums')
                            ->maxLength(255)
                            ->placeholder('e.g., Minimum order 12 units'),
                        Forms\Components\TextInput::make('color')
                            ->label('Color')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('size')
                            ->label('Size')
                            ->maxLength(255)
                            ->placeholder('e.g., 500ml, 750ml'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->height(120)
                    ->width(96)
                    ->circular(false)
                    ->defaultImageUrl('/images/placeholder-product.png')
                    ->getStateUsing(fn (Bottle $record): ?string => $record->images[0] ?? null),
                Tables\Columns\TextColumn::make('name')
                    ->label('Bottle Style')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->url(fn (Bottle $record): string => route('filament.admin.resources.bottles.view', $record))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Bullet Points')
                    ->limit(100)
                    ->wrap()
                    ->formatStateUsing(function (string $state): string {
                        // Split by line breaks and format each point
                        $lines = array_filter(array_map('trim', explode("\n", $state)));
                        return implode("\n• ", array_map(function($line) {
                            // Remove existing bullets/dashes and add clean bullet
                            return ltrim($line, '• -');
                        }, $lines));
                    })
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 100) {
                            return null;
                        }
                        return $state;
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Bottles'),
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
            ->defaultSort('name', 'asc')
            ->paginated(false); // Show all bottles on one page
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBottles::route('/'),
            'create' => Pages\CreateBottle::route('/create'),
            'view' => Pages\ViewBottle::route('/{record}'),
            'edit' => Pages\EditBottle::route('/{record}/edit'),
        ];
    }
}
