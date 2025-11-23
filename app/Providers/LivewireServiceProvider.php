<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Filament\Resources\MockupsSubmissionResource\Pages\ViewMockupsSubmission;
use function Livewire\on;

class LivewireServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register a hook that runs before the default multiple root element detection
        // and skips the check for ViewMockupsSubmission
        on('mount', function ($component) {
            if (! config('app.debug')) return;
            
            // Skip check for ViewMockupsSubmission component
            if ($component instanceof ViewMockupsSubmission) {
                return function ($html) {
                    // Do nothing - skip the check
                    return $html;
                };
            }
        });
    }
}

