<?php

namespace App\Filament\Resources\FranchiseeResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class FranchiseeLogosViewWidget extends Widget
{
    protected static string $view = 'filament.resources.franchisee-resource.widgets.franchisee-logos-view-widget';

    protected int | string | array $columnSpan = 'full';

    public function mount(): void
    {
        // Widget will get record from parent page
    }

    protected function getRecord()
    {
        // Get record ID from route parameter
        $recordId = request()->route('record');
        
        if ($recordId) {
            return \App\Models\Franchisee::find($recordId);
        }
        
        return null;
    }

    public function getLogos(): array
    {
        $record = $this->getRecord();

        if (!$record || empty($record->logos)) {
            return [];
        }

        return collect($record->logos)
            ->map(function ($logo, $index) {
                $path = null;

                if (is_string($logo)) {
                    $path = $logo;
                } elseif (is_array($logo)) {
                    $path = $logo['path'] ?? $logo['url'] ?? $logo['file'] ?? null;
                }

                if (!$path) {
                    return null;
                }

                $url = $this->getLogoUrl($path);

                return [
                    'path' => $path,
                    'url' => $url,
                    'filename' => basename($path),
                    'label' => 'Logo ' . ($index + 1),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function getLogoUrl(string $logoPath): string
    {
        return Storage::disk('public')->url($logoPath);
    }
}

