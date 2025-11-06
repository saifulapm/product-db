<?php

namespace App\Filament\Resources\ReferenceImageResource\Pages;

use App\Filament\Resources\ReferenceImageResource;
use App\Models\ReferenceImage;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Table;

class ReferenceImagesGallery extends ListRecords
{
    protected static string $resource = ReferenceImageResource::class;
    
    protected static ?string $title = 'Reference Images';
    
    public $galleryItems = [];

    public function mount(): void
    {
        parent::mount();
        $this->loadGallery();
    }

    public function loadGallery(): void
    {
        $referenceImage = ReferenceImage::first();
        $this->galleryItems = $referenceImage ? ($referenceImage->gallery_images ?? []) : [];
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('add_image')
                ->label('Add Image')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    Section::make('Add Image')
                        ->schema([
                            TextInput::make('url')
                                ->label('Image URL')
                                ->required()
                                ->url()
                                ->maxLength(500)
                                ->placeholder('https://example.com/image.jpg'),
                            Textarea::make('description')
                                ->label('Description')
                                ->maxLength(500)
                                ->rows(3)
                                ->placeholder('Description of the image'),
                        ])
                        ->columns(1),
                ])
                ->action(function (array $data): void {
                    // Get or create the single reference image record
                    $referenceImage = ReferenceImage::first();
                    if (!$referenceImage) {
                        $referenceImage = ReferenceImage::create([
                            'gallery_images' => [],
                        ]);
                    }

                    // Add new image to gallery
                    $galleryImages = $referenceImage->gallery_images ?? [];
                    $galleryImages[] = [
                        'url' => $data['url'],
                        'description' => $data['description'] ?? '',
                    ];

                    $referenceImage->update([
                        'gallery_images' => $galleryImages,
                    ]);

                    Notification::make()
                        ->title('Image added successfully!')
                        ->success()
                        ->send();
                    
                    $this->loadGallery();
                }),
        ];
    }
    
    public function table(Table $table): Table
    {
        // Return empty table to hide it
        return $table->columns([])->paginated(false);
    }
}
