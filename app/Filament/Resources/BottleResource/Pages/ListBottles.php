<?php

namespace App\Filament\Resources\BottleResource\Pages;

use App\Filament\Resources\BottleResource;
use App\Filament\Resources\BottleResource\Widgets\BottlesHeader;
use App\Models\TeamNote;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBottles extends ListRecords
{
    protected static string $resource = BottleResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\CreateAction::make()
                ->color('success'),
            Actions\Action::make('download_bottle_cads')
                ->label('Download Bottle CADs')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url('https://drive.google.com/uc?export=download&id=1x5Uf8Y6Oi-74TaXjpJtcdLhK5UaFIFiP', shouldOpenInNewTab: true),
        ];

        // Add team notes edit action
        $teamNote = TeamNote::firstOrCreate(['page' => 'bottles'], ['content' => '']);
        
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
                // Clean and ensure UTF-8 encoding
                $content = $data['content'] ?? '';
                
                // Strip invalid UTF-8 characters
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                $content = iconv('UTF-8', 'UTF-8//IGNORE', $content);
                
                $teamNote = TeamNote::firstOrNew(['page' => 'bottles']);
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

    protected function getHeaderWidgets(): array
    {
        return [
            BottlesHeader::class,
        ];
    }
}
