<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Notifications\FeatureAnnouncementNotification;
use Filament\Widgets\Widget;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Support\Facades\Auth;

class SendNotificationWidget extends Widget implements HasActions
{
    use InteractsWithActions;

    protected static string $view = 'filament.widgets.send-notification-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public bool $hasFormsModalRendered = false;
    public bool $hasInfolistsModalRendered = false;
    public ?array $mountedFormComponentActions = [];

    public function mount(): void
    {
        foreach ($this->getActions() as $action) {
            if ($action instanceof Action) {
                $this->cacheAction($action);
            }
        }
    }

    public function getViewData(): array
    {
        return [];
    }

    public function canSendNotifications(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('super-admin');
    }

    public function sendNotification(): Action
    {
        return Action::make('send_notification')
            ->label('Send Feature Announcement')
            ->icon('heroicon-o-megaphone')
            ->color('primary')
            ->form([
                TextInput::make('title')
                    ->label('Notification Title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('New Feature: Enhanced Dashboard')
                    ->helperText('Enter a clear title for the feature announcement'),
                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(4)
                    ->maxLength(1000)
                    ->placeholder('We\'ve added new features to help you work more efficiently...')
                    ->helperText('Describe the new feature or update'),
            ])
            ->action(function (array $data): void {
                // Check if user has permission
                if (!$this->canSendNotifications()) {
                    FilamentNotification::make()
                        ->title('Permission Denied')
                        ->danger()
                        ->body('Only Super Admins can send feature announcements.')
                        ->send();
                    return;
                }

                $title = $data['title'];
                $message = $data['message'];

                // Get all active users
                $users = User::where('is_active', true)->get();

                // Send notification to all users
                $notification = new FeatureAnnouncementNotification($title, $message);
                
                foreach ($users as $user) {
                    $user->notify($notification);
                }

                FilamentNotification::make()
                    ->title('Notification sent successfully!')
                    ->success()
                    ->body('Feature announcement sent to ' . $users->count() . ' team member(s).')
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Send Feature Announcement')
            ->modalDescription('This will send a notification to all active team members.')
            ->modalSubmitActionLabel('Send Notification');
    }

    public function getActions(): array
    {
        return [
            $this->sendNotification(),
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
}

