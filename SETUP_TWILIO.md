# Twilio Account Setup - Step by Step Guide

## Step 1: Create Twilio Account (if you don't have one)

1. **Visit**: https://www.twilio.com/try-twilio
2. **Sign up** with:
   - First name, last name
   - Email address
   - Password
3. **Verify your email** (check inbox)
4. **Verify your phone number** (enter code sent via SMS)

## Step 2: Get Your Twilio Credentials

Once logged in to https://console.twilio.com/:

### Find Account SID and Auth Token:
1. Go to the **Dashboard** (main page)
2. Look for:
   - **Account SID** - Starts with `AC` (e.g., `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
   - **Auth Token** - Click "Show" to reveal it (e.g., `your_auth_token_here`)

### Get Your Phone Number:
1. Click **Phone Numbers** in the left sidebar
2. Click **Manage** > **Active Numbers**
3. You should see your Twilio phone number (format: `+1XXXXXXXXXX`)
4. If you don't have one yet:
   - Click **Buy a Number**
   - Search for a number with SMS capability
   - Purchase it (usually free with trial account)

## Step 3: Add Credentials to Your Project

Once you have your credentials, run this command in your terminal:

```bash
php artisan twilio:setup
```

Or manually add to your `.env` file:

```env
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_FROM_NUMBER=+15551234567
```

## Step 4: Test the Setup

After adding credentials, test it:

```bash
php artisan config:clear
php artisan twilio:test
```

## Quick Reference

- **Twilio Console**: https://console.twilio.com/
- **Dashboard**: Where you find Account SID and Auth Token
- **Phone Numbers**: Where you find/manage your Twilio number
- **Trial Account**: Free credits to test (usually $15-20 worth)

## Troubleshooting

**"No phone number found"**
- Make sure you've purchased a phone number in Twilio Console
- The number must have SMS capability enabled

**"Invalid credentials"**
- Double-check Account SID starts with `AC`
- Make sure Auth Token has no extra spaces
- Run `php artisan config:clear` after updating `.env`

**"Insufficient balance"**
- Check your Twilio account balance
- Trial accounts come with free credits
- You may need to add funds if trial credits are exhausted

