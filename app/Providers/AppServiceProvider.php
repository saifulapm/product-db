<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register notifications bell component globally
        \Livewire\Livewire::component('notifications-bell', \App\Filament\Components\NotificationsBell::class);
        
        // Add notifications bell to top bar before user menu
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn () => view('filament.components.notifications-bell-wrapper'),
        );
    }
}
