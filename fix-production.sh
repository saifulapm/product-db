#!/bin/bash
# Production Server Fix Script
# Run this on your production server to fix all 500 errors

echo "🔧 Fixing production server..."
echo ""

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Clear all caches
echo "🧹 Clearing all Laravel caches..."
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Delete ALL compiled views (CRITICAL)
echo "🗑️  Deleting all compiled views..."
rm -rf storage/framework/views/*

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Restart PHP-FPM if available
if command -v systemctl &> /dev/null; then
    echo "🔄 Restarting PHP-FPM..."
    sudo systemctl reload php8.4-fpm 2>/dev/null || sudo systemctl reload php-fpm 2>/dev/null || true
fi

echo ""
echo "✅ Done! All caches cleared."
echo "🌐 Please refresh your browser and test the pages."
echo ""
echo "Test these pages:"
echo "  - /admin/sock-styles"
echo "  - /admin/incoming-shipments"
echo "  - /admin/packing-lists"

