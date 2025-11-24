# Twilio SMS Integration Setup Guide

This guide will help you set up Twilio SMS integration for sending mockup submission updates to clients.

## Prerequisites

1. A Twilio account (sign up at https://www.twilio.com/)
2. A Twilio phone number (you'll get one when you sign up)
3. Your Twilio Account SID and Auth Token

## Step 1: Get Your Twilio Credentials

1. Log in to your Twilio Console: https://console.twilio.com/
2. Navigate to the Dashboard
3. You'll find your **Account SID** and **Auth Token** on the dashboard
4. Copy these values - you'll need them for your `.env` file

## Step 2: Get Your Twilio Phone Number

1. In the Twilio Console, go to **Phone Numbers** > **Manage** > **Active Numbers**
2. You should see your Twilio phone number (it will look like +1XXXXXXXXXX)
3. Copy this number - this is your `TWILIO_FROM_NUMBER`

## Step 3: Configure Environment Variables

Add the following variables to your `.env` file:

```env
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_FROM_NUMBER=+1XXXXXXXXXX
```

Replace:
- `your_account_sid_here` with your actual Account SID
- `your_auth_token_here` with your actual Auth Token
- `+1XXXXXXXXXX` with your Twilio phone number (include the +1)

## Step 4: Test the Integration

1. Make sure you have a mockup submission with a valid customer phone number
2. Click "Send Submission to Client" button
3. The system will send an SMS to the customer's phone number

## Phone Number Format

The system automatically formats phone numbers to E.164 format (required by Twilio):
- US numbers: `+1XXXXXXXXXX` (10 digits after +1)
- The system will automatically add `+1` if it's missing

## Troubleshooting

### Error: "SMS service is not configured"
- Check that all three Twilio environment variables are set in your `.env` file
- Make sure there are no extra spaces or quotes around the values
- Run `php artisan config:clear` after updating `.env`

### Error: "Invalid phone number format"
- Ensure the customer phone number has at least 10 digits
- Phone numbers should be stored without special formatting (e.g., `2522693956` or `+12522693956`)

### Error: "Twilio error sending SMS"
- Verify your Twilio credentials are correct
- Check your Twilio account balance (you need credits to send SMS)
- Ensure your Twilio phone number is active
- Check Twilio Console logs for more details

## SMS Message Format

The SMS message sent to clients includes:
- Customer name
- Submission tracking number
- Company name (if available)
- Custom notes (if provided)
- A friendly message asking for review

Example message:
```
Hi John Doe,

Your mockup submission #123 for Acme Corp is ready for review.

Notes: Please review the color options and let us know your preference.

Please review and provide feedback. Thank you!
```

## Cost Considerations

- Twilio charges per SMS sent (typically $0.0075 per SMS in the US)
- Make sure you have sufficient balance in your Twilio account
- Monitor your usage in the Twilio Console

## Security Notes

- Never commit your `.env` file to version control
- Keep your Auth Token secret and secure
- Rotate your Auth Token periodically for security

