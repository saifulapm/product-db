<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Filament\Resources\EventResource\Widgets\EventReminderStatus;
use App\Models\EventReminder;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;
    
    protected function getFooterWidgets(): array
    {
        return [
            EventReminderStatus::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_to_google_calendar')
                ->label('Add to Google Calendar')
                ->icon('heroicon-o-calendar')
                ->color('success')
                ->url(fn () => $this->getGoogleCalendarUrl())
                ->openUrlInNewTab(),
            Actions\Action::make('set_reminder')
                ->label('Set Reminder')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->placeholder('Optional notes for this reminder')
                        ->columnSpanFull(),
                ])
                ->modalSubmitAction(false)
                ->extraModalFooterActions([
                    Actions\Action::make('email_me')
                        ->label('Email Me')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->action(function (array $data, Actions\Action $action): void {
                            $user = auth()->user();

                            if (!$user->email) {
                                Notification::make()
                                    ->title('Email required')
                                    ->body('Please add your email address in your profile settings.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Calculate reminder time (5 minutes before event start)
                            $startTime = $this->record->start_time;
                            // Handle start_time - it's stored as TIME in DB, so it's a string like "09:00:00"
                            if (is_string($startTime)) {
                                // Extract just the time part (HH:MM:SS)
                                $startTime = substr($startTime, 0, 8);
                            } elseif ($startTime instanceof \DateTime || $startTime instanceof \Carbon\Carbon) {
                                $startTime = $startTime->format('H:i:s');
                            } else {
                                $startTime = '09:00:00';
                            }
                            $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $this->record->start_date->format('Y-m-d') . ' ' . $startTime);
                            $reminderDateTime = $startDateTime->copy()->subMinutes(5);

                            EventReminder::updateOrCreate(
                                [
                                    'event_id' => $this->record->id,
                                    'user_id' => $user->id,
                                ],
                                [
                                    'reminder_date' => $reminderDateTime,
                                    'notes' => $data['notes'] ?? null,
                                    'is_sent' => false,
                                ]
                            );

                            Notification::make()
                                ->title('Email reminder set')
                                ->body('You will receive an email reminder 5 minutes before the event starts.')
                                ->success()
                                ->send();
                            
                            $action->close();
                        }),
                    Actions\Action::make('text_me')
                        ->label('Text Me')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->color('success')
                        ->action(function (array $data, Actions\Action $action): void {
                            $user = auth()->user();

                            if (!$user->phone) {
                                Notification::make()
                                    ->title('Phone number required')
                                    ->body('Please add your phone number in your profile settings to receive SMS reminders.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Calculate reminder time (5 minutes before event start)
                            $startTime = $this->record->start_time;
                            // Handle start_time - it's stored as TIME in DB, so it's a string like "09:00:00"
                            if (is_string($startTime)) {
                                // Extract just the time part (HH:MM:SS)
                                $startTime = substr($startTime, 0, 8);
                            } elseif ($startTime instanceof \DateTime || $startTime instanceof \Carbon\Carbon) {
                                $startTime = $startTime->format('H:i:s');
                            } else {
                                $startTime = '09:00:00';
                            }
                            $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $this->record->start_date->format('Y-m-d') . ' ' . $startTime);
                            $reminderDateTime = $startDateTime->copy()->subMinutes(5);

                            EventReminder::updateOrCreate(
                                [
                                    'event_id' => $this->record->id,
                                    'user_id' => $user->id,
                                ],
                                [
                                    'reminder_date' => $reminderDateTime,
                                    'notes' => $data['notes'] ?? null,
                                    'is_sent' => false,
                                ]
                            );

                            Notification::make()
                                ->title('SMS reminder set')
                                ->body('You will receive an SMS reminder 5 minutes before the event starts.')
                                ->success()
                                ->send();
                            
                            $action->close();
                        }),
                ]),
            Actions\EditAction::make(),
        ];
    }

    protected function getGoogleCalendarUrl(): string
    {
        $event = $this->record;
        
        // Format dates for Google Calendar (YYYYMMDD)
        $startDate = $event->start_date->format('Ymd');
        $endDate = $event->end_date 
            ? $event->end_date->format('Ymd')
            : Carbon::parse($event->start_date)->addDay()->format('Ymd');
        
        // Build event details
        $title = urlencode($event->name);
        $details = [];
        if ($event->description) {
            $details[] = $event->description;
        }
        if ($event->company_name) {
            $details[] = 'Company: ' . $event->company_name;
        }
        $detailsStr = urlencode(implode("\n\n", $details));
        
        // Google Calendar URL format
        $url = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
        $url .= '&text=' . $title;
        $url .= '&dates=' . $startDate . '/' . $endDate;
        if ($detailsStr) {
            $url .= '&details=' . $detailsStr;
        }
        
        return $url;
    }
}
