<?php

namespace App\Filament\Resources\SockGripResource\Pages;

use App\Filament\Resources\SockGripResource;
use App\Filament\Resources\SockGripResource\Widgets\SockGripsHeader;
use App\Models\SockGrip;
use App\Models\TeamNote;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;

class ListSockGrips extends ListRecords
{
    protected static string $resource = SockGripResource::class;
    protected static ?string $title = 'Grips';

    public function getHeaderWidgets(): array
    {
        return [
            SockGripsHeader::class,
        ];
    }

    public function getHeaderActions(): array
    {
        $actions = [
            Action::make('add_sock_grip')
                ->label('Add New Sock Grip')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    Section::make('Sock Grip Information')
                        ->schema([
                            TextInput::make('name')
                                ->label('Sock Grip Style')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Athletic Crew Socks, Dress Socks'),
                            Textarea::make('description')
                                ->label('Bullet Points')
                                ->maxLength(1000)
                                ->rows(4)
                                ->placeholder('Enter each bullet point on a new line:' . PHP_EOL . '• Moisture-wicking technology' . PHP_EOL . '• Cushioned sole for comfort' . PHP_EOL . '• Reinforced heel and toe'),
                            Textarea::make('images')
                                ->label('Sock Grip Image URLs')
                                ->maxLength(1000)
                                ->placeholder('Enter image URLs (one per line):' . PHP_EOL . 'https://example.com/sock1.jpg' . PHP_EOL . 'https://example.com/sock2.jpg')
                                ->helperText('Enter 1-3 image URLs for the sock grip style (one URL per line)')
                                ->rows(3),
                        ])
                        ->columns(1),
                ])
                ->action(function (array $data): void {
                    SockGrip::create([
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'images' => $data['images'],
                        'is_active' => true, // Always available
                    ]);

                    Notification::make()
                        ->title('Sock grip added successfully!')
                        ->success()
                        ->send();
                }),
        ];

        // Add download button
        $actions[] = Action::make('download_all_cads')
            ->label('Download All Sock Grip CADs')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('info')
            ->url('https://drive.google.com/uc?export=download&id=1hlgMc_wGtAVw91SQfnDWu_Ej_Agtt6vF', shouldOpenInNewTab: true);

        // Add team notes edit action
        $teamNote = TeamNote::firstOrCreate(['page' => 'sock-grips'], ['content' => '']);
        
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
                
                $teamNote = TeamNote::firstOrNew(['page' => 'sock-grips']);
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

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->paginated([
                10,
                25,
                50,
                100,
                999 => 'All',
            ])
            ->columns([
                ImageColumn::make('images')
                    ->label('Image')
                    ->height(120)
                    ->width(96)
                    ->circular(false)
                    ->defaultImageUrl('/images/placeholder-sock.png'),
                TextColumn::make('name')
                    ->label('Sock Grip Style')
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->url(fn (SockGrip $record): string => route('filament.admin.resources.sock-grips.view', $record))
                    ->color('primary'),
                TextColumn::make('description')
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
                    ->tooltip(function (TextColumn $column): ?string {
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
                // No actions - clicking the sock grip style name will navigate to view page
            ])
            ->defaultSort('created_at', 'desc');
    }
}
