<?php

namespace App\Filament\Resources\DtfInHousePrintResource\Widgets;

use App\Models\DtfWidgetContent;
use Filament\Widgets\Widget;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Support\Contracts\TranslatableContentDriver;

class IccProfiling extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static string $view = 'filament.resources.dtf-in-house-print-resource.widgets.icc-profiling';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 7;
    
    protected static bool $isLazy = false;

    public $content = '';
    public $existingImages = [];
    public $imageUrl = '';
    public bool $hasFormsModalRendered = false;
    public bool $hasInfolistsModalRendered = false;
    public ?array $mountedFormComponentActions = [];

    public function mount(): void
    {
        $widget = DtfWidgetContent::firstOrCreate(
            ['widget_name' => 'icc_profiling'],
            ['content' => '']
        );
        
        // Load data from JSON in content field
        $data = json_decode($widget->content ?: '{}', true) ?: [];
        $this->content = $data['text'] ?? '';
        $this->existingImages = $data['images'] ?? [];
        
        foreach ($this->getActions() as $action) {
            if ($action instanceof Action) {
                $this->cacheAction($action);
            }
        }
    }

    public function editContent(): Action
    {
        return Action::make('edit_content')
            ->label('Edit Content')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->form([
                RichEditor::make('content')
                    ->label('Content')
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
                    ->default(mb_convert_encoding($this->content ?: '', 'UTF-8', 'UTF-8')),
                TextInput::make('imageUrl')
                    ->label('Add Image from URL (Optional)')
                    ->url()
                    ->placeholder('https://example.com/image.jpg')
                    ->helperText('Paste an image URL (jpg, png, gif, webp, svg). Images are saved separately.')
                    ->required(false),
            ])
            ->action(function (array $data): void {
                // Handle image URL if provided
                if (!empty($data['imageUrl'] ?? '')) {
                    $imageUrl = trim($data['imageUrl']);
                    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                        if (in_array($extension, $allowedExtensions)) {
                            $this->existingImages[] = $imageUrl;
                        }
                    }
                }
                
                // Clean and ensure UTF-8 encoding
                $content = $data['content'] ?? '';
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                $content = iconv('UTF-8', 'UTF-8//IGNORE', $content);
                
                $widget = DtfWidgetContent::firstOrNew(['widget_name' => 'icc_profiling']);
                $widget->content = json_encode([
                    'text' => $content,
                    'images' => $this->existingImages
                ]);
                $widget->save();

                $this->content = $content;

                Notification::make()
                    ->title('Content updated successfully!')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation(false)
            ->modalHeading('Edit Content')
            ->modalSubmitActionLabel('Save')
            ->modalWidth('4xl');
    }

    protected function getActions(): array
    {
        return [
            $this->editContent(),
        ];
    }


    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function getMountedFormComponentAction() { return null; }
    public function mountedFormComponentActionShouldOpenModal(): bool { return false; }
    public function mountedFormComponentActionHasForm(): bool { return false; }
    public function getMountedFormComponentActionForm() { return null; }
    public function unmountFormComponentAction(bool $shouldCancelParentActions = true, bool $shouldCloseModal = true): void {}

    public function removeImage($index): void
    {
        if (isset($this->existingImages[$index])) {
            $imagePath = $this->existingImages[$index];
            // Remove from array
            unset($this->existingImages[$index]);
            $this->existingImages = array_values($this->existingImages);
            
            // Save to database
            $widget = DtfWidgetContent::firstOrNew(['widget_name' => 'icc_profiling']);
            $widget->content = json_encode([
                'text' => $this->content,
                'images' => $this->existingImages
            ]);
            $widget->save();

            \Filament\Notifications\Notification::make()
                ->title('Image removed successfully!')
                ->success()
                ->send();

            $this->dispatch('image-removed');
        }
    }

    public function saveContent(): void
    {
        $widget = DtfWidgetContent::firstOrNew(['widget_name' => 'icc_profiling']);
        $widget->content = json_encode([
            'text' => $this->content,
            'images' => $this->existingImages
        ]);
        $widget->save();

        $this->showForm = false;

        \Filament\Notifications\Notification::make()
            ->title('Content updated successfully!')
            ->success()
            ->send();
    }

    public function addImageFromUrl(): void
    {
        if (empty($this->imageUrl)) {
            return;
        }

        // Validate URL
        if (!filter_var($this->imageUrl, FILTER_VALIDATE_URL)) {
            \Filament\Notifications\Notification::make()
                ->title('Invalid URL')
                ->danger()
                ->send();
            return;
        }

        // Check if it's an image URL
        $extension = strtolower(pathinfo(parse_url($this->imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        if (!in_array($extension, $allowedExtensions)) {
            \Filament\Notifications\Notification::make()
                ->title('Please use an image URL (jpg, png, gif, webp, svg)')
                ->danger()
                ->send();
            return;
        }

        $this->existingImages[] = $this->imageUrl;
        $this->imageUrl = '';
        
        // Auto-save to database
        $widget = DtfWidgetContent::firstOrNew(['widget_name' => 'icc_profiling']);
        $widget->content = json_encode([
            'text' => $this->content,
            'images' => $this->existingImages
        ]);
        $widget->save();

        \Filament\Notifications\Notification::make()
            ->title('Image added successfully!')
            ->success()
            ->send();
    }
}

