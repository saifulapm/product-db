<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TwilioService;

class TestTwilio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twilio:test {phone? : Phone number to send test SMS to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Twilio SMS integration by sending a test message';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  Testing Twilio SMS Integration');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Check if Twilio is configured
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $fromNumber = config('services.twilio.from');

        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            $this->error('❌ Twilio is not configured!');
            $this->info('Run: php artisan twilio:setup');
            return 1;
        }

        $this->info('✅ Twilio credentials found:');
        $this->line("  Account SID: {$accountSid}");
        $this->line("  From Number: {$fromNumber}");
        $this->newLine();

        // Get phone number
        $phoneNumber = $this->argument('phone');
        if (!$phoneNumber) {
            $phoneNumber = $this->ask('Enter a phone number to send test SMS to (format: +15551234567)');
        }

        if (empty($phoneNumber)) {
            $this->error('Phone number is required');
            return 1;
        }

        try {
            $twilioService = new TwilioService();
            
            // Validate phone number
            if (!$twilioService->isValidPhoneNumber($phoneNumber)) {
                $this->error('❌ Invalid phone number format');
                $this->info('Phone number should have at least 10 digits');
                return 1;
            }

            $this->info("Sending test SMS to: {$phoneNumber}");
            $this->newLine();

            $message = "Hello! This is a test message from your Twilio integration. Your SMS setup is working correctly! 🎉";
            
            $twilioMessage = $twilioService->sendSMS($phoneNumber, $message);

            $this->info('✅ SMS sent successfully!');
            $this->line("  Message SID: {$twilioMessage->sid}");
            $this->line("  Status: {$twilioMessage->status}");
            $this->line("  To: {$twilioMessage->to}");
            $this->line("  From: {$twilioMessage->from}");
            $this->newLine();
            $this->info('Check your phone for the test message!');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error sending SMS:');
            $this->line($e->getMessage());
            $this->newLine();
            $this->info('Common issues:');
            $this->line('  - Check your Twilio account balance');
            $this->line('  - Verify your phone number format');
            $this->line('  - Ensure your Twilio number has SMS capability');
            $this->line('  - Check Twilio Console for error details');
            
            return 1;
        }
    }
}
