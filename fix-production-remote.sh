#!/bin/bash
# Run this command to fix production server
# Usage: bash fix-production-remote.sh user@design.ethos-merch.com /path/to/product-db

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Usage: bash fix-production-remote.sh user@host /path/to/product-db"
    echo "Example: bash fix-production-remote.sh user@design.ethos-merch.com /var/www/product-db"
    exit 1
fi

SERVER=$1
PROJECT_PATH=$2

echo "🔧 Connecting to production server and fixing..."
echo ""

ssh $SERVER << EOF
cd $PROJECT_PATH
echo "📥 Pulling latest code..."
git pull origin main

echo "🧹 Clearing all Laravel caches..."
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "🗑️  Deleting all compiled views..."
rm -rf storage/framework/views/*

echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo "🔄 Restarting PHP-FPM..."
sudo systemctl reload php8.4-fpm 2>/dev/null || sudo systemctl reload php-fpm 2>/dev/null || echo "PHP-FPM reload skipped"

echo ""
echo "✅ Done! All caches cleared on production."
echo "🌐 Please refresh your browser and test the pages."
EOF

echo ""
echo "✅ Production server fixed!"



