<?php

namespace App\Filament\Resources\FileResource\Pages;

use App\Filament\Resources\FileResource;
use App\Filament\Resources\FileResource\Widgets\FilesHeader;
use App\Models\File;
use App\Models\TeamNote;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageFiles extends ManageRecords
{
    protected static string $resource = FileResource::class;

    public function getHeaderWidgets(): array
    {
        return [
            FilesHeader::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\CreateAction::make()
                ->label('Upload Files')
                ->mutateFormDataUsing(function (array $data): array {
                    if (isset($data['path']) && is_array($data['path']) && count($data['path']) > 1) {
                        $data['_original_paths'] = $data['path'];
                        $firstFile = $data['path'][0];
                        $data['path'] = $firstFile;
                    } elseif (isset($data['path']) && is_array($data['path'])) {
                        $data['path'] = $data['path'][0] ?? null;
                    }

                    if (isset($data['path']) && $data['path']) {
                        $path = is_string($data['path'])
                            ? $data['path']
                            : ($data['path'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                                ? $data['path']->getFilename()
                                : (string) $data['path']);

                        $data['file_name'] = basename((string) $path);
                        $data['disk'] = 'public';

                        if (empty($data['name'])) {
                            $data['name'] = basename((string) $path);
                        }

                        $filePath = Storage::disk('public')->path($path);
                        if (file_exists($filePath)) {
                            if (!isset($data['size'])) {
                                $data['size'] = filesize($filePath);
                            }
                            if (!isset($data['mime_type'])) {
                                $data['mime_type'] = mime_content_type($filePath);
                            }
                            $data['url'] = Storage::disk('public')->url($path);
                        }
                    }

                    return $data;
                })
                ->after(function (array $data, File $record): void {
                    if (isset($data['_original_paths']) && is_array($data['_original_paths']) && count($data['_original_paths']) > 1) {
                        $uploadedFiles = $data['_original_paths'];
                        array_shift($uploadedFiles);

                        $createdCount = 1;

                        foreach ($uploadedFiles as $filePath) {
                            $path = is_string($filePath)
                                ? $filePath
                                : (is_array($filePath)
                                    ? ($filePath[0] ?? null)
                                    : (string) $filePath);

                            if (!$path) {
                                continue;
                            }

                            $storagePath = Storage::disk('public')->path($path);

                            if (file_exists($storagePath)) {
                                File::create([
                                    'path' => $path,
                                    'file_name' => basename($path),
                                    'name' => basename($path),
                                    'disk' => 'public',
                                    'size' => filesize($storagePath),
                                    'mime_type' => mime_content_type($storagePath),
                                    'url' => Storage::disk('public')->url($path),
                                ]);
                                $createdCount++;
                            }
                        }

                        if ($createdCount > 1) {
                            Notification::make()
                                ->title('Files uploaded successfully')
                                ->body("{$createdCount} file(s) have been uploaded.")
                                ->success()
                                ->send();
                        }
                    }
                }),
        ];

        $teamNote = TeamNote::firstOrCreate(['page' => 'files'], ['content' => '']);

        $actions[] = Action::make('edit_team_notes')
            ->label('Edit Team Notes')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->form([
                RichEditor::make('content')
                    ->label('Team Notes')
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
                    ])
                    ->default(mb_convert_encoding($teamNote->content ?: '', 'UTF-8', 'UTF-8')),
            ])
            ->action(function (array $data): void {
                $content = $data['content'] ?? '';

                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                $content = iconv('UTF-8', 'UTF-8//IGNORE', $content);

                $teamNote = TeamNote::firstOrNew(['page' => 'files']);
                $teamNote->content = $content;
                $teamNote->save();

                Notification::make()
                    ->title('Notes updated successfully!')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation(false)
            ->modalHeading('Edit Team Notes')
            ->modalSubmitActionLabel('Save');

        return $actions;
    }
}
