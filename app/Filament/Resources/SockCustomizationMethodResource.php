<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SockCustomizationMethodResource\Pages;
use App\Filament\Resources\SockCustomizationMethodResource\RelationManagers;
use App\Models\SockCustomizationMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SockCustomizationMethodResource extends Resource
{
    protected static ?string $model = SockCustomizationMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Customization Methods';
    protected static ?string $modelLabel = 'Customization Method';
    protected static ?string $pluralModelLabel = 'Customization Methods';
    protected static ?string $navigationGroup = 'Socks';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('customization-methods.view');
    }
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customization Method Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Method Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Embroidery, Screen Print'),
                        Forms\Components\Textarea::make('description')
                            ->label('Bullet Points')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Enter each bullet point on a new line:' . PHP_EOL . '• High-quality embroidery' . PHP_EOL . '• Various thread colors available'),
                        Forms\Components\Textarea::make('images')
                            ->label('Image URLs')
                            ->maxLength(2000)
                            ->rows(3)
                            ->placeholder('Enter image URLs (one per line):' . PHP_EOL . 'https://example.com/embroidery1.jpg' . PHP_EOL . 'https://example.com/embroidery2.jpg')
                            ->helperText('Enter 1-3 image URLs (one URL per line)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(1),
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
                    ->defaultImageUrl('/images/placeholder-sock.png'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Method Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Bullet Points')
                    ->wrap(false)
                    ->formatStateUsing(function (string $state): string {
                        // Split by line breaks and format each point with bullets
                        $lines = array_filter(array_map('trim', explode("\n", $state)));
                        return implode("<br>• ", array_map(function($line) {
                            // Remove existing bullets/dashes and add clean bullet
                            return ltrim($line, '• -');
                        }, $lines));
                    })
                    ->html(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('name', 'asc')
            ->paginated(false);
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
            'index' => Pages\ListSockCustomizationMethods::route('/'),
            'create' => Pages\CreateSockCustomizationMethod::route('/create'),
            'edit' => Pages\EditSockCustomizationMethod::route('/{record}/edit'),
        ];
    }
}
