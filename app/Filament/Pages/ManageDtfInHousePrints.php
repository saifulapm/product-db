<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DtfInHousePrintResource\Widgets\DtfSourcing;
use App\Filament\Resources\DtfInHousePrintResource\Widgets\HexCodeColors;
use App\Filament\Resources\DtfInHousePrintResource\Widgets\LeadTimesMinimums;
use App\Filament\Resources\DtfInHousePrintResource\Widgets\CareInstructions;
use App\Filament\Resources\DtfInHousePrintResource\Widgets\PressSettings;
use App\Filament\Resources\DtfInHousePrintResource\Widgets\DtfInHousePrintHeader;
use App\Filament\Resources\DtfInHousePrintResource\Widgets\VisualReference;
use App\Models\TeamNote;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageDtfInHousePrints extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Direct To Film';
    
    protected static ?string $navigationGroup = 'In House Print';
    
    protected static ?int $navigationSort = 0;
    
    protected static string $view = 'filament.pages.manage-dtf-in-house-prints';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('dtf-in-house-print.view');
    }
    
    protected static string $routePath = 'dtf-in-house-prints';
    
    protected static ?string $title = 'DTF In House Prints';

    public function getHeaderWidgets(): array
    {
        return [
            DtfInHousePrintHeader::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            PressSettings::class,
            VisualReference::class,
        ];
    }

    public function getHeaderActions(): array
    {
        $actions = [
            Action::make('orderDtfLogo')
                ->label('Order DTF Logo')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url('https://secure.supacolor.com/secure/quickjob/quickjob.aspx')
                ->openUrlInNewTab(),
            Action::make('artworkSpecifications')
                ->label('Artwork Specifications')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url('https://drive.google.com/file/d/1CNe0oBKVDd5cFUYo8mGi7l7hEwgoe7pr/view')
                ->openUrlInNewTab(),
        ];

        // Add team notes edit action
        $teamNote = TeamNote::firstOrCreate(['page' => 'dtf-in-house-prints'], ['content' => '']);
        
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
                
                $teamNote = TeamNote::firstOrNew(['page' => 'dtf-in-house-prints']);
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

