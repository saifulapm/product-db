<?php

namespace App\Filament\Resources\SocksTwoResource\Pages;

use App\Filament\Resources\SocksTwoResource;
use App\Filament\Resources\SocksTwoResource\Widgets\SocksTwoHeader;
use App\Models\Sock;
use App\Models\TeamNote;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SocksTwoGallery extends ListRecords
{
    protected static string $resource = SocksTwoResource::class;
    protected static ?string $title = 'Socks 2 Styles';

    public function getHeaderWidgets(): array
    {
        return [
            SocksTwoHeader::class,
        ];
    }

    public function getHeaderActions(): array
    {
        $actions = [
            Action::make('add_sock_two')
                ->label('Add New Sock Style')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    Section::make('Sock Information')
                        ->schema([
                            TextInput::make('name')
                                ->label('Sock Style')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Athletic Crew Socks, Dress Socks'),
                            Textarea::make('description')
                                ->label('Bullet Points')
                                ->maxLength(1000)
                                ->rows(4)
                                ->placeholder('Enter each bullet point on a new line:' . PHP_EOL . '• Moisture-wicking technology' . PHP_EOL . '• Cushioned sole for comfort' . PHP_EOL . '• Reinforced heel and toe'),
                            Textarea::make('images')
                                ->label('Sock Cover Image')
                                ->maxLength(1000)
                                ->placeholder('Enter cover image URL:' . PHP_EOL . 'https://example.com/sock-cover.jpg')
                                ->helperText('Enter the main cover image URL for this sock style')
                                ->rows(2),
                        ])
                        ->columns(1),

                    Section::make('Specifications')
                        ->schema([
                            TextInput::make('ribbing_height')
                                ->label('Height of Sock Ribbing')
                                ->maxLength(255)
                                ->placeholder('e.g., 2 inches, 5cm'),
                            TextInput::make('fabric')
                                ->label('Fabric')
                                ->maxLength(255)
                                ->placeholder('e.g., Cotton Blend, Merino Wool'),
                            TextInput::make('price')
                                ->label('Starting Price')
                                ->numeric()
                                ->prefix('$')
                                ->placeholder('0.00'),
                            TextInput::make('minimums')
                                ->label('Minimums')
                                ->maxLength(255)
                                ->placeholder('e.g., 12 pairs, 24 pairs'),
                        ])
                        ->columns(2),

                    Section::make('Gallery')
                        ->schema([
                            \Filament\Forms\Components\Repeater::make('gallery_images')
                                ->label('Gallery Images')
                                ->schema([
                                    TextInput::make('url')
                                        ->label('Image URL')
                                        ->required()
                                        ->url()
                                        ->maxLength(500)
                                        ->placeholder('https://example.com/image.jpg'),
                                    TextInput::make('description')
                                        ->label('Description')
                                        ->maxLength(255)
                                        ->placeholder('Short description of the image'),
                                ])
                                ->defaultItems(0)
                                ->itemLabel(fn (array $state): ?string => $state['description'] ?? $state['url'] ?? 'New Image')
                                ->collapsible()
                                ->addActionLabel('Add Gallery Image')
                                ->reorderable(true),
                        ])
                        ->columns(1),
                ])
                ->action(function (array $data): void {
                    Sock::create([
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'images' => $data['images'],
                        'gallery_images' => $data['gallery_images'] ?? null,
                        'ribbing_height' => $data['ribbing_height'] ?? null,
                        'fabric' => $data['fabric'] ?? null,
                        'price' => $data['price'] ?: 0,
                        'minimums' => $data['minimums'] ?? null,
                        'is_active' => true,
                    ]);

                    Notification::make()
                        ->title('Sock style added successfully!')
                        ->success()
                        ->send();
                }),
        ];

        $actions[] = Action::make('download_all_cads')
            ->label('Download All Sock CADs')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('info')
            ->url('https://drive.google.com/uc?export=download&id=1ZjqvCv5dyiYYtjIHNu-22NhoP7H9OprB', shouldOpenInNewTab: true);

        $teamNote = TeamNote::firstOrCreate(['page' => 'socks-2'], ['content' => '']);

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

                $teamNote = TeamNote::firstOrNew(['page' => 'socks-2']);
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
            ->columns([
                ImageColumn::make('images')
                    ->label('Image')
                    ->height(120)
                    ->width(96)
                    ->circular(false)
                    ->defaultImageUrl('/images/placeholder-sock.png'),
                TextColumn::make('name')
                    ->label('Sock Style')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->url(fn (Sock $record): string => route('filament.admin.resources.socks-2.view', $record))
                    ->color('primary'),
                TextColumn::make('description')
                    ->label('Bullet Points')
                    ->limit(100)
                    ->wrap()
                    ->formatStateUsing(function (string $state): string {
                        $lines = array_filter(array_map('trim', explode("\n", $state)));
                        return implode("\n• ", array_map(function ($line) {
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
            ->filters([])
            ->actions([])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

