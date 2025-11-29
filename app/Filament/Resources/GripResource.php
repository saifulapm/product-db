<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GripResource\Pages;
use App\Filament\Resources\GripResource\RelationManagers;
use App\Models\Grip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GripResource extends Resource
{
    protected static ?string $model = Grip::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationLabel = 'Grips';
    protected static ?string $modelLabel = 'Grip';
    protected static ?string $pluralModelLabel = 'Grips';
    protected static ?string $navigationGroup = 'Socks';
    protected static ?int $navigationSort = 3;
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('grips.view');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grip Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Grip Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Rubber Grip, Silicon Grip'),
                        Forms\Components\Textarea::make('description')
                            ->label('Bullet Points')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Enter each bullet point on a new line:' . PHP_EOL . '• Non-slip material' . PHP_EOL . '• Durable construction' . PHP_EOL . '• Easy to apply'),
                        Forms\Components\Textarea::make('images')
                            ->label('Grip Image URLs')
                            ->maxLength(1000)
                            ->placeholder('Enter image URLs (one per line):' . PHP_EOL . 'https://example.com/grip1.jpg' . PHP_EOL . 'https://example.com/grip2.jpg')
                            ->helperText('Enter 1-3 image URLs for the grip (one URL per line)')
                            ->rows(3),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Specifications')
                    ->schema([
                        Forms\Components\TextInput::make('material')
                            ->label('Material')
                            ->maxLength(255)
                            ->placeholder('e.g., Rubber, Silicon, PVC'),
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
                    ->defaultImageUrl('/images/placeholder-grip.png'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Grip Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->url(fn (Grip $record): string => route('filament.admin.resources.grips.view', $record))
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
                //
            ])
            ->actions([
                // No actions - clicking the grip name will navigate to view page
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_all')
                    ->label('Download All Grip CADs')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        // Google Drive file ID from the URL
                        $fileId = '1uamwwaWoM564xeKwEzDxKGDyZpme1cA0';
                        
                        // Create a zip file
                        $zipPath = storage_path('app/temp/grip-cads-' . now()->format('Y-m-d-His') . '.zip');
                        $zip = new \ZipArchive();
                        
                        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                            return Notification::make()
                                ->danger()
                                ->title('Error creating ZIP file')
                                ->send();
                        }
                        
                        // Download the file from Google Drive
                        $downloadUrl = "https://drive.google.com/uc?export=download&id=" . $fileId;
                        $fileContent = @file_get_contents($downloadUrl);
                        
                        if ($fileContent !== false) {
                            $zip->addFromString('grip-cads.zip', $fileContent);
                        } else {
                            return Notification::make()
                                ->danger()
                                ->title('Error downloading file')
                                ->body('Unable to download the file from Google Drive.')
                                ->send();
                        }
                        
                        $zip->close();
                        
                        return response()->download($zipPath)->deleteFileAfterSend(true);
                    }),
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
            'index' => Pages\GripsList::route('/'),
            'create' => Pages\CreateGrip::route('/create'),
            'view' => Pages\ViewGrip::route('/{record}'),
            'edit' => Pages\EditGrip::route('/{record}/edit'),
        ];
    }
}
