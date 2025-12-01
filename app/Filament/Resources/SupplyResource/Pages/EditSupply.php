<?php

namespace App\Filament\Resources\SupplyResource\Pages;

use App\Filament\Resources\SupplyResource;
use App\Filament\Resources\SupplyResource\Widgets\SupplyShipmentTracking;
use App\Models\User;
use App\Notifications\SupplyReorderNotification;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSupply extends EditRecord
{
    protected static string $resource = SupplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SupplyShipmentTracking::class,
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function afterSave(): void
    {
        $supply = $this->record;
        
        // Check if quantity has reached or fallen below reorder point
        if ($supply->reorder_point !== null && 
            $supply->quantity <= $supply->reorder_point && 
            $supply->quantity >= 0) {
            
            // Send notification to all super-admin users
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->where('slug', 'super-admin')
                      ->orWhere('name', 'like', '%admin%')
                      ->orWhere('name', 'like', '%super%');
            })->get();
            
            // If no admin users found, send to all users (fallback)
            if ($adminUsers->isEmpty()) {
                $adminUsers = User::all();
            }
            
            // Send notification to each admin user
            foreach ($adminUsers as $user) {
                $user->notify(new SupplyReorderNotification($supply));
            }
            
            Notification::make()
                ->title('Reorder Alert Sent')
                ->body("Email notifications have been sent to administrators because {$supply->name} has reached its reorder point.")
                ->warning()
                ->send();
        }
    }
}
