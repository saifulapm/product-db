<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventReminder;
use App\Models\User;
use App\Notifications\EventReminderNotification;
use App\Services\TwilioService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS and email reminders 5 minutes before events start';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for events starting in 5 minutes...');

        // Find events starting in 5 minutes (with a 1-minute window to account for cron timing)
        $now = Carbon::now();
        $targetTime = $now->copy()->addMinutes(5);
        
        // Get all active events
        $events = Event::where('is_active', true)->get();

        if ($events->isEmpty()) {
            $this->info('No active events found.');
            return;
        }

        // Check if Twilio is configured (optional for SMS)
        $twilioService = null;
        try {
            $twilioService = new TwilioService();
        } catch (\Exception $e) {
            $this->warn('Twilio is not configured. SMS reminders will be skipped, but email reminders will still be sent.');
            Log::warning('Twilio not configured - SMS reminders disabled');
        }

        $sentSmsCount = 0;
        $sentEmailCount = 0;
        $skippedCount = 0;

        foreach ($events as $event) {
            // Calculate event start datetime (date + time)
            $startTime = $event->start_time;
            if (is_string($startTime)) {
                $startTime = substr($startTime, 0, 8); // Ensure HH:MM:SS format
            } else {
                $startTime = '09:00:00';
            }
            $eventStart = Carbon::parse($event->start_date->format('Y-m-d') . ' ' . $startTime);
            
            // Check if event starts within 4-6 minutes from now
            $minutesUntilStart = $now->diffInMinutes($eventStart, false);
            
            // Only send if event starts between 4 and 6 minutes from now
            if ($minutesUntilStart < 4 || $minutesUntilStart > 6) {
                continue;
            }
            
            $this->info("Event '{$event->name}' starts in {$minutesUntilStart} minutes.");

            // Get all reminders for this event that haven't been sent
            $reminders = EventReminder::where('event_id', $event->id)
                ->where('is_sent', false)
                ->with('user')
                ->get();

            foreach ($reminders as $reminder) {
                $user = $reminder->user;
                
                // Send email notification (always)
                try {
                    $user->notify(new EventReminderNotification($event, $reminder));
                    $this->info("Sent email reminder to {$user->email} for event: {$event->name}");
                    $sentEmailCount++;
                    
                    Log::info('Event reminder email sent', [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                } catch (\Exception $e) {
                    $this->error("Failed to send email reminder to {$user->email}: " . $e->getMessage());
                    Log::error('Failed to send event reminder email', [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                // Send SMS notification (if Twilio is configured and user has phone)
                if ($twilioService && $user->phone) {
                    try {
                        // Build reminder message
                        $message = "Reminder: {$event->name}";
                        if ($event->company_name) {
                            $message .= " ({$event->company_name})";
                        }
                        $message .= " starts in 5 minutes.";
                        if ($event->start_date) {
                            $message .= " Date: " . $event->start_date->format('M d, Y');
                        }
                        if ($reminder->notes) {
                            $message .= "\n\nNote: {$reminder->notes}";
                        }

                        // Send SMS
                        $twilioService->sendSMS($user->phone, $message);
                        $this->info("Sent SMS reminder to {$user->email} ({$user->phone}) for event: {$event->name}");
                        $sentSmsCount++;

                        Log::info('Event reminder SMS sent', [
                            'event_id' => $event->id,
                            'user_id' => $user->id,
                            'phone' => $user->phone,
                        ]);

                    } catch (\Exception $e) {
                        $this->error("Failed to send SMS reminder to {$user->email}: " . $e->getMessage());
                        Log::error('Failed to send event reminder SMS', [
                            'event_id' => $event->id,
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } elseif (!$user->phone) {
                    $this->warn("Skipping SMS for user {$user->email} - no phone number set.");
                }

                // Mark reminder as sent (after both email and SMS attempts)
                $reminder->update(['is_sent' => true]);
            }
        }

        $this->info("Sent {$sentEmailCount} email reminder(s) and {$sentSmsCount} SMS reminder(s).");
    }
}
