<?php

namespace App\Filament\Resources\ShootModelResource\Pages;

use App\Filament\Resources\ShootModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ListShootModels extends ListRecords
{
    protected static string $resource = ShootModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_from_jotform')
                ->label('Sync from JotForm')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sync Models from JotForm')
                ->modalDescription('This will fetch all form submissions from JotForm and create/update model entries.')
                ->action(function () {
                    try {
                        Artisan::call('models:sync-from-jotform');
                        $output = Artisan::output();
                        
                        Notification::make()
                            ->title('Sync Completed')
                            ->body('Models have been synced from JotForm successfully.')
                            ->success()
                            ->send();
                        
                        // Refresh the page to show new entries
                        $this->dispatch('refresh');
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body('Error syncing from JotForm: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}
