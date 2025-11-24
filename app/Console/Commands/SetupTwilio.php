<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupTwilio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twilio:setup 
                            {--account-sid= : Twilio Account SID}
                            {--auth-token= : Twilio Auth Token}
                            {--from-number= : Twilio Phone Number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Twilio SMS integration credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  Twilio SMS Integration Setup');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Check if .env file exists
        if (!File::exists(base_path('.env'))) {
            $this->error('.env file not found!');
            $this->info('Please create a .env file first by copying .env.example');
            return 1;
        }

        // Read current .env
        $envContent = File::get(base_path('.env'));
        
        // Get credentials from options or prompt
        $accountSid = $this->option('account-sid') ?: $this->ask('Enter your Twilio Account SID (starts with AC)');
        $authToken = $this->option('auth-token') ?: $this->secret('Enter your Twilio Auth Token');
        $fromNumber = $this->option('from-number') ?: $this->ask('Enter your Twilio Phone Number (format: +15551234567)');

        // Validate inputs
        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            $this->error('All credentials are required!');
            return 1;
        }

        // Format phone number if needed
        if (!str_starts_with($fromNumber, '+')) {
            $cleaned = preg_replace('/[^0-9]/', '', $fromNumber);
            if (strlen($cleaned) === 10) {
                $fromNumber = '+1' . $cleaned;
            } elseif (strlen($cleaned) === 11 && str_starts_with($cleaned, '1')) {
                $fromNumber = '+' . $cleaned;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Account SID: {$accountSid}");
        $this->line("  Auth Token: " . str_repeat('*', strlen($authToken)));
        $this->line("  From Number: {$fromNumber}");
        $this->newLine();

        // Update .env file
        $lines = explode("\n", $envContent);
        $hasAccountSid = false;
        $hasAuthToken = false;
        $hasFromNumber = false;
        $newLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'TWILIO_ACCOUNT_SID=')) {
                $newLines[] = "TWILIO_ACCOUNT_SID={$accountSid}";
                $hasAccountSid = true;
            } elseif (str_starts_with($line, 'TWILIO_AUTH_TOKEN=')) {
                $newLines[] = "TWILIO_AUTH_TOKEN={$authToken}";
                $hasAuthToken = true;
            } elseif (str_starts_with($line, 'TWILIO_FROM_NUMBER=')) {
                $newLines[] = "TWILIO_FROM_NUMBER={$fromNumber}";
                $hasFromNumber = true;
            } else {
                $newLines[] = $line;
            }
        }

        // Add if they don't exist
        if (!$hasAccountSid || !$hasAuthToken || !$hasFromNumber) {
            $newLines[] = '';
            $newLines[] = '# Twilio SMS Configuration';
            if (!$hasAccountSid) {
                $newLines[] = "TWILIO_ACCOUNT_SID={$accountSid}";
            }
            if (!$hasAuthToken) {
                $newLines[] = "TWILIO_AUTH_TOKEN={$authToken}";
            }
            if (!$hasFromNumber) {
                $newLines[] = "TWILIO_FROM_NUMBER={$fromNumber}";
            }
        }

        File::put(base_path('.env'), implode("\n", $newLines));

        $this->info('✅ Twilio credentials saved to .env file!');
        $this->newLine();

        // Clear config cache
        $this->call('config:clear');
        $this->info('✅ Configuration cache cleared!');
        $this->newLine();

        $this->info('You can test the setup by running:');
        $this->line('  php artisan twilio:test');

        return 0;
    }
}
