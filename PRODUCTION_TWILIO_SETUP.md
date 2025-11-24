# Production Server Twilio Setup

The error "SMS service is not configured" means the production server doesn't have Twilio credentials configured.

## Steps to Fix on Production Server

1. **SSH into your production server**
   ```bash
   ssh your-server
   ```

2. **Navigate to your application directory**
   ```bash
   cd /path/to/your/app
   ```

3. **Add Twilio credentials to .env file**
   Edit the `.env` file and add:
   ```env
   TWILIO_ACCOUNT_SID=ACad02a5663d0d7d2fc7313f55f962f988
   TWILIO_AUTH_TOKEN=25905b1eb097cd985b3bff2fcef39e7f
   TWILIO_FROM_NUMBER=+17603347966
   ```

4. **Clear and rebuild config cache**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

5. **Test the setup**
   ```bash
   php artisan twilio:test +1YOUR_PHONE_NUMBER
   ```

## Quick Fix Command

If you have SSH access, run:
```bash
cd /path/to/your/app && \
echo "" >> .env && \
echo "# Twilio SMS Configuration" >> .env && \
echo "TWILIO_ACCOUNT_SID=ACad02a5663d0d7d2fc7313f55f962f988" >> .env && \
echo "TWILIO_AUTH_TOKEN=25905b1eb097cd985b3bff2fcef39e7f" >> .env && \
echo "TWILIO_FROM_NUMBER=+17603347966" >> .env && \
php artisan config:clear && \
php artisan config:cache
```

## Verify It's Working

After adding credentials, check:
```bash
php artisan config:show services.twilio
```

You should see:
- account_sid: ACad02a5663d0d7d2fc7313f55f962f988
- auth_token: 25905b1eb097cd985b3bff2fcef39e7f
- from: +17603347966

