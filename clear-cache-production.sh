#!/bin/bash
# Production cache clearing script
# Run this on the production server after pulling latest code

echo "Clearing all Laravel caches..."

php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Force delete all compiled views
rm -rf storage/framework/views/*

echo "Cache cleared successfully!"
echo "Please refresh your browser."

