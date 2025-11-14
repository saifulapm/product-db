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
        
        if (!$record || !$record->logos) {
            return [];
        }

        return $record->logos;
    }

    public function getLogoUrl(string $logoPath): string
    {
        return Storage::disk('public')->url($logoPath);
    }
}

