<?php

namespace App\Filament\Resources\SocksTwoResource\Widgets;

use App\Models\Sock;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class SockTwoGallery extends Widget
{
    protected static string $view = 'filament.resources.socks-two-resource.widgets.sock-two-gallery';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?Sock $record = null;

    protected function getViewData(): array
    {
        $galleryItems = [];

        if ($this->record instanceof Sock) {
            $galleryData = $this->record->gallery_images;

            if (is_array($galleryData)) {
                foreach ($galleryData as $item) {
                    if (is_array($item) && !empty($item['url'])) {
                        $galleryItems[] = [
                            'url' => trim($item['url']),
                            'description' => trim($item['description'] ?? ''),
                        ];
                    } elseif (is_string($item)) {
                        $galleryItems[] = [
                            'url' => trim($item),
                            'description' => '',
                        ];
                    }
                }
            }
        }

        return [
            'galleryItems' => $galleryItems,
        ];
    }
}

