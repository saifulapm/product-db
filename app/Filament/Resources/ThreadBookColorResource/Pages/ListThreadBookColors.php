<?php

namespace App\Filament\Resources\ThreadBookColorResource\Pages;

use App\Filament\Resources\ThreadBookColorResource;
use App\Filament\Resources\ThreadBookColorResource\Widgets\ThreadBookColorsHeader;
use App\Filament\Resources\ThreadBookColorResource\Imports\ThreadBookColorImporter;
use App\Models\TeamNote;
use App\Models\ThreadBookColor;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Forms;

class ListThreadBookColors extends ListRecords
{
    protected static string $resource = ThreadBookColorResource::class;

    public function getHeaderWidgets(): array
    {
        return [
            ThreadBookColorsHeader::class,
        ];
    }

    public function getHeaderActions(): array
    {
        $actions = [
            Action::make('create_thread_book_color')
                ->label('New Thread Book Color')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->url(ThreadBookColorResource::getUrl('create')),
            ImportAction::make()
                ->label('Import from CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->importer(ThreadBookColorImporter::class),
            Action::make('add_rows')
                ->label('Add Rows')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->form([
                    Forms\Components\Wizard::make([
                        Forms\Components\Wizard\Step::make('Color Names')
                            ->schema([
                                Forms\Components\Textarea::make('color_names')
                                    ->label('Color Names (Column 1)')
                                    ->placeholder("Paste one color name per line\nExample:\nNavy Blue\nCrimson Red")
                                    ->rows(10)
                                    ->required(),
                            ])
                            ->description('Paste the first column containing color names.'),
                        Forms\Components\Wizard\Step::make('Thread Numbers')
                            ->schema([
                                Forms\Components\Textarea::make('thread_codes')
                                    ->label('Thread Book Numbers (Column 2)')
                                    ->placeholder("Paste the matching thread book number per line\nExample:\nBC07\nL118")
                                    ->rows(10)
                                    ->required(),
                            ])
                            ->description('Paste the second column containing thread numbers.'),
                        Forms\Components\Wizard\Step::make('Color Families')
                            ->schema([
                                Forms\Components\Textarea::make('color_families')
                                    ->label('Color Families (Column 3)')
                                    ->placeholder("Paste the matching color family/category per line (optional)\nExample:\nBlues\nReds")
                                    ->rows(10),
                            ])
                            ->description('Optional third column for color family / category.'),
                        Forms\Components\Wizard\Step::make('Hex Codes')
                            ->schema([
                                Forms\Components\Textarea::make('hex_codes')
                                    ->label('Hex Codes (Column 4)')
                                    ->placeholder("Paste the matching hex code per line (optional)\nExample:\n#000000\n#FFFFFF")
                                    ->rows(10),
                            ])
                            ->description('Optional fourth column for hex codes.'),
                    ])
                ])
                ->action(function (array $data): void {
                    $names = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $data['color_names'] ?? ''))));
                    $codes = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $data['thread_codes'] ?? ''))));
                    $families = array_values(array_map(fn ($value) => trim($value ?? ''), preg_split('/\r?\n/', $data['color_families'] ?? '')));
                    $hexes = array_values(array_map(fn ($value) => trim($value ?? ''), preg_split('/\r?\n/', $data['hex_codes'] ?? '')));

                    $pairs = min(count($names), count($codes));

                    $created = 0;
                    $updated = 0;
                    $skipped = 0;

                    for ($i = 0; $i < $pairs; $i++) {
                        $name = $names[$i];
                        $code = $codes[$i];
                        $family = $families[$i] ?? null;
                        $hex = $hexes[$i] ?? null;

                        if (blank($name) || blank($code)) {
                            $skipped++;
                            continue;
                        }

                        $record = ThreadBookColor::where('color_code', $code)->first()
                            ?? ThreadBookColor::where('name', $name)->first();

                        if ($record) {
                            $payload = [
                                'name' => $name,
                                'color_code' => $code,
                            ];

                            if (filled($family)) {
                                $payload['color_category'] = $family;
                            }

                            if (filled($hex)) {
                                $payload['hex_code'] = $hex;
                            }

                            $record->update($payload);
                            $updated++;
                            continue;
                        }

                        ThreadBookColor::create([
                            'name' => $name,
                            'color_code' => $code,
                            'color_category' => filled($family) ? $family : null,
                            'hex_code' => filled($hex) ? $hex : null,
                        ]);
                        $created++;
                    }

                    $skipped += abs(count($names) - count($codes));
                    $skipped += max(0, collect($families)->skip($pairs)->filter(fn ($value) => filled($value))->count());
                    $skipped += max(0, collect($hexes)->skip($pairs)->filter(fn ($value) => filled($value))->count());

                    Notification::make()
                        ->title('Thread book colors processed')
                        ->body("Created {$created}, updated {$updated}, skipped {$skipped} (blank rows or mismatched columns).")
                        ->success()
                        ->send();
                })
                ->modalHeading('Add Thread Book Colors from Columns')
                ->modalSubmitActionLabel('Save Colors'),
        ];

        // Add download button
        $actions[] = Action::make('download_all_cads')
            ->label('Download Thread Book CADs')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('info')
            ->url('https://cdn.shopify.com/s/files/1/0609/4752/9901/files/Grip_Pattern_Downloads.pdf?v=1761505527', shouldOpenInNewTab: true);

        // Add team notes edit action
        $teamNote = TeamNote::firstOrCreate(['page' => 'thread-book-colors'], ['content' => '']);
        
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
                
                $teamNote = TeamNote::firstOrNew(['page' => 'thread-book-colors']);
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
