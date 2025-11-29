<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SockGripResource\Pages;
use App\Filament\Resources\SockGripResource\RelationManagers;
use App\Models\SockGrip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SockGripResource extends Resource
{
    protected static ?string $model = SockGrip::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationLabel = 'Grips';
    protected static ?string $modelLabel = 'Grip';
    protected static ?string $pluralModelLabel = 'Grips';
    protected static ?string $navigationGroup = 'Socks';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('sock-grips.view');
    }
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sock Grip Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Sock Grip Style')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Athletic Crew Socks, Dress Socks'),
                        Forms\Components\Textarea::make('description')
                            ->label('Bullet Points')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Enter each bullet point on a new line:' . PHP_EOL . '• Moisture-wicking technology' . PHP_EOL . '• Cushioned sole for comfort' . PHP_EOL . '• Reinforced heel and toe'),
                        Forms\Components\Textarea::make('images')
                            ->label('Sock Grip Image URLs')
                            ->maxLength(1000)
                            ->placeholder('Enter image URLs (one per line):' . PHP_EOL . 'https://example.com/sock1.jpg' . PHP_EOL . 'https://example.com/sock2.jpg')
                            ->helperText('Enter 1-3 image URLs for the sock grip style (one URL per line)')
                            ->rows(3),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Specifications')
                    ->schema([
                        Forms\Components\TextInput::make('ribbing_height')
                            ->label('Height of Sock Ribbing')
                            ->maxLength(255)
                            ->placeholder('e.g., 2 inches, 5cm'),
                        Forms\Components\TextInput::make('fabric')
                            ->label('Fabric')
                            ->maxLength(255)
                            ->placeholder('e.g., Cotton Blend, Merino Wool'),
                        Forms\Components\TextInput::make('price')
                            ->label('Starting Price')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00'),
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
                    ->defaultImageUrl('/images/placeholder-sock.png'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Sock Grip Style')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->url(fn (SockGrip $record): string => route('filament.admin.resources.sock-grips.view', $record))
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
                    ->label('Available Socks'),
                Tables\Filters\SelectFilter::make('material')
                    ->options([
                        'cotton' => 'Cotton',
                        'wool' => 'Wool',
                        'synthetic' => 'Synthetic',
                        'bamboo' => 'Bamboo',
                        'merino' => 'Merino Wool',
                    ]),
                Tables\Filters\SelectFilter::make('size')
                    ->options([
                        'XS' => 'Extra Small',
                        'S' => 'Small',
                        'M' => 'Medium',
                        'L' => 'Large',
                        'XL' => 'Extra Large',
                    ]),
            ])
            ->actions([
                // No actions - clicking the sock grip style name will navigate to view page
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->paginated(false); // Show all sock grips on one page for info sheet feel
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
            'index' => Pages\ListSockGrips::route('/'),
            'create' => Pages\CreateSockGrip::route('/create'),
            'view' => Pages\ViewSockGrip::route('/{record}'),
            'edit' => Pages\EditSockGrip::route('/{record}/edit'),
        ];
    }
}
