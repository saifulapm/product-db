# JotForm Model Sync

This command syncs model submissions from your JotForm form to the database.

## Setup

1. Get your JotForm API key:
   - Go to https://www.jotform.com/myaccount/api
   - Create a new API key or use an existing one

2. Add the API key to your `.env` file:
   ```
   JOTFORM_API_KEY=your_api_key_here
   ```

## Usage

### Manual Sync
Run the sync command manually:
```bash
php artisan models:sync-from-jotform
```

### With Custom Form ID
If you need to sync from a different form:
```bash
php artisan models:sync-from-jotform --form-id=YOUR_FORM_ID
```

### With API Key Override
If you want to use a different API key for this run:
```bash
php artisan models:sync-from-jotform --api-key=your_api_key
```

## Field Mapping

The command maps JotForm fields to database fields:
- **Name** → `first_name`, `last_name`, `name`
- **Email** → `email`
- **Phone Number** → `phone_number`
- **Social Media** → `social_media`
- **Upload A Selfie** → `selfie_url`
- **Coffee Order** → `coffee_order`
- **Food Allergies** → `food_allergies`
- **Height** → `height`
- **Tops Size** → `tops_size` (array)
- **Bottoms Size** → `bottoms_size` (array)
- **Availability** → `availability` (array)

## Scheduling

To schedule automatic syncing, add this to `routes/console.php`:

```php
Schedule::command('models:sync-from-jotform')->hourly();
```

## Notes

- The command checks for existing models by email address
- If a model with the same email exists, it updates the record
- New submissions create new model records
- The form ID defaults to `253424538550053` if not specified

