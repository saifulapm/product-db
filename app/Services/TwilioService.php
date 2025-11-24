<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;
    protected $fromNumber;

    public function __construct()
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $this->fromNumber = config('services.twilio.from');

        // Log what we're getting for debugging
        Log::debug('Twilio Service Initialization', [
            'account_sid_set' => !empty($accountSid),
            'auth_token_set' => !empty($authToken),
            'from_number_set' => !empty($this->fromNumber),
            'from_number_value' => $this->fromNumber,
        ]);

        if (!$accountSid || !$authToken || !$this->fromNumber) {
            $missing = [];
            if (!$accountSid) $missing[] = 'TWILIO_ACCOUNT_SID';
            if (!$authToken) $missing[] = 'TWILIO_AUTH_TOKEN';
            if (!$this->fromNumber) $missing[] = 'TWILIO_FROM_NUMBER';
            
            Log::error('Twilio credentials missing', [
                'missing' => $missing,
                'account_sid' => $accountSid ? 'SET' : 'MISSING',
                'auth_token' => $authToken ? 'SET' : 'MISSING',
                'from_number' => $this->fromNumber ?: 'MISSING',
            ]);
            
            throw new \Exception('Twilio credentials are not configured. Missing: ' . implode(', ', $missing) . '. Please set these in your .env file and run: php artisan config:clear');
        }

        $this->client = new Client($accountSid, $authToken);
    }

    /**
     * Send an SMS message
     *
     * @param string $to Phone number in E.164 format (e.g., +15551234567)
     * @param string $message Message content
     * @return \Twilio\Rest\Api\V2010\Account\MessageInstance
     * @throws \Exception
     */
    public function sendSMS($to, $message)
    {
        try {
            // Ensure phone number is in E.164 format
            $to = $this->formatPhoneNumber($to);

            $message = $this->client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            Log::info('SMS sent successfully', [
                'to' => $to,
                'message_sid' => $message->sid,
                'status' => $message->status
            ]);

            return $message;
        } catch (\Exception $e) {
            Log::error('Error sending SMS via Twilio', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Format phone number to E.164 format
     * E.164 format: +[country code][number] (e.g., +15551234567)
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If it doesn't start with +, assume US number and add +1
        if (!str_starts_with($phoneNumber, '+')) {
            // If it's 10 digits, assume US number
            if (strlen($cleaned) === 10) {
                return '+1' . $cleaned;
            }
            // If it's 11 digits and starts with 1, add +
            if (strlen($cleaned) === 11 && str_starts_with($cleaned, '1')) {
                return '+' . $cleaned;
            }
            // Otherwise, try to add +1 if it's a valid length
            return '+1' . $cleaned;
        }

        return $phoneNumber;
    }

    /**
     * Validate phone number format
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isValidPhoneNumber($phoneNumber)
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        // Basic validation: should have at least 10 digits
        $digits = preg_replace('/[^0-9]/', '', $cleaned);
        return strlen($digits) >= 10;
    }
}

