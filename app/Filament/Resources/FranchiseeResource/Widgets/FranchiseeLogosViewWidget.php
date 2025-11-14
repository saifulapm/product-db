<?php

namespace App\Filament\Resources\FranchiseeResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            ->map(fn ($logo, $index) => $this->formatLogoEntry($logo, $index))
            ->filter()
            ->values()
            ->all();
    }

    public function getLogoUrl(string $logoPath): string
    {
        $cleanPath = ltrim($logoPath, '/');

        if (Str::startsWith($cleanPath, 'storage/')) {
            $cleanPath = Str::after($cleanPath, 'storage/');
        }

        return Storage::disk('public')->url($cleanPath);
    }

    protected function formatLogoEntry($logo, int $index): ?array
    {
        $url = null;
        $path = null;
        $filename = null;

        if (is_string($logo)) {
            if (Str::startsWith($logo, ['http://', 'https://', '//'])) {
                $url = $logo;
                $filename = basename(parse_url($logo, PHP_URL_PATH) ?: $logo);
            } else {
                $path = $logo;
            }
        } elseif (is_array($logo)) {
            $path = $logo['path'] ?? $logo['file'] ?? null;
            $explicitUrl = $logo['url'] ?? null;

            if ($explicitUrl && Str::startsWith($explicitUrl, ['http://', 'https://', '//'])) {
                $url = $explicitUrl;
            }

            $filename = $logo['name']
                ?? basename($path ?? (parse_url($explicitUrl ?? '', PHP_URL_PATH) ?: 'logo-' . ($index + 1)));
        }

        if (!$url && $path) {
            $url = $this->getLogoUrl($path);
            $filename = $filename ?: basename($path);
        }

        if (!$url) {
            return null;
        }

        return [
            'url' => $url,
            'download_url' => $url,
            'filename' => $filename ?: 'logo-' . ($index + 1),
            'label' => 'Logo ' . ($index + 1),
        ];
    }
}

