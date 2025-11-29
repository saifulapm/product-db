<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileResource\Pages;
use App\Filament\Resources\FileResource\RelationManagers;
use App\Models\File;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationLabel = 'Files';

    protected static ?string $navigationGroup = 'Data';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('files.view');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('File')
                    ->required()
                    ->disk(function ($record) {
                        // If we have a full URL, don't use disk - ImageColumn detects URLs automatically
                        if ($record && $record->file_url && filter_var($record->file_url, FILTER_VALIDATE_URL)) {
                            return null;
                        }
                        return $record && $record->disk ? $record->disk : 'public';
                    })
                    ->directory('files')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/zip'])
                    ->maxSize(10240) // 10MB
                    ->downloadable()
                    ->openable()
                    ->previewable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $file = is_array($state) ? $state[0] : $state;
                            $originalFileName = null;
                            
                            // If it's a TemporaryUploadedFile, get the original name
                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                $originalFileName = $file->getClientOriginalName();
                            } else {
                                // If it's already stored, get from path (preserveFilenames keeps original name)
                                $path = (string) $file;
                                $originalFileName = basename($path);
                                $filePath = Storage::disk('public')->path($path);
                                
                                if (file_exists($filePath)) {
                                    $set('size', filesize($filePath));
                                    $set('mime_type', mime_content_type($filePath));
                                    $set('file_name', basename($path));
                                    // Automatically generate the full URL
                                    $fileUrl = Storage::disk('public')->url($path);
                                    $set('url', $fileUrl);
                                }
                            }
                            
                            // Auto-fill the name field with the original filename
                            if ($originalFileName && !$get('name')) {
                                $set('name', $originalFileName);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('name')
                    ->label('File Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Will be auto-filled from uploaded file'),
                Forms\Components\TextInput::make('url')
                    ->label('File URL')
                    ->disabled()
                    ->dehydrated(true)
                    ->default(function ($get) {
                        // Auto-generate URL from path if available
                        $path = $get('path');
                        if ($path && !is_array($path)) {
                            return Storage::disk('public')->url($path);
                        }
                        return null;
                    })
                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record, $get) {
                        // If no URL is set, generate it from the path
                        if (!$state) {
                            $path = $get('path') ?? ($record?->path ?? null);
                            if ($path) {
                                $url = Storage::disk('public')->url($path);
                                $component->state($url);
                            } elseif ($record) {
                                $component->state($record->file_url);
                            }
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('preview')
                    ->label('Preview')
                    ->size(50)
                    ->circular(false)
                    ->getStateUsing(function ($record) {
                        // Only show preview for image files
                        if (!$record || !$record->mime_type || !str_starts_with($record->mime_type, 'image/')) {
                            return null;
                        }
                        
                        // Prioritize file_url (full URL) - works for both local and remote servers
                        if ($record->file_url) {
                            return $record->file_url;
                        }
                        
                        // Fallback to path if URL not available
                        if ($record->path) {
                            return $record->path;
                        }
                        return null;
                    })
                    ->disk(function ($record) {
                        // If we have a full URL, don't use disk - ImageColumn detects URLs automatically
                        if ($record && $record->file_url && filter_var($record->file_url, FILTER_VALIDATE_URL)) {
                            return null;
                        }
                        return $record && $record->disk ? $record->disk : 'public';
                    })
                    ->extraImgAttributes([
                        'class' => 'rounded object-cover',
                    ]),
                Tables\Columns\TextColumn::make('name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('File name copied!')
                    ->weight('medium')
                    ->action(fn ($record) => redirect(static::getUrl('edit', ['record' => $record]))),
                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Size')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('disk')
                    ->options([
                        'public' => 'Public',
                        'local' => 'Local',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (File $record) {
                        if ($record->exists()) {
                            return Storage::disk($record->disk)->download($record->path, $record->name);
                        }
                        Notification::make()
                            ->danger()
                            ->title('File not found')
                            ->body('The file could not be found in storage.')
                            ->send();
                    }),
                Tables\Actions\Action::make('copy_url')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard')
                    ->requiresConfirmation(false)
                    ->action(function (File $record) {
                        Notification::make()
                            ->success()
                            ->title('URL Copied!')
                            ->body('File URL: ' . $record->file_url)
                            ->send();
                    }),
                                    Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('download')
                        ->label('Download Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $tempDir = storage_path('app/temp');
                            if (!file_exists($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }
                            
                            $zipPath = $tempDir . '/downloads_' . time() . '.zip';
                            
                            $zip = new \ZipArchive();
                            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                                $addedFiles = 0;
                                foreach ($records as $file) {
                                    if ($file->exists()) {
                                        try {
                                            $filePath = Storage::disk($file->disk)->path($file->path);
                                            if (file_exists($filePath)) {
                                                $zip->addFile($filePath, $file->name);
                                                $addedFiles++;
                                            }
                                        } catch (\Exception $e) {
                                            // Skip files that can't be added
                                            continue;
                                        }
                                    }
                                }
                                $zip->close();
                                
                                if ($addedFiles > 0) {
                                    return response()->download($zipPath)->deleteFileAfterSend(true);
                                } else {
                                    unlink($zipPath);
                                    Notification::make()
                                        ->warning()
                                        ->title('No Files to Download')
                                        ->body('None of the selected files could be found.')
                                        ->send();
                                }
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Download Failed')
                                    ->body('Could not create zip file.')
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable();
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
            'index' => Pages\ManageFiles::route('/'),
            'edit' => Pages\EditFile::route('/{record}/edit'),
        ];
    }
}
