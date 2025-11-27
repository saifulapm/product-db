#!/bin/bash

# Deployment script for product-db
# Run this on your production server after pulling latest changes

echo "🚀 Starting deployment..."

# Pull latest changes (if not already done)
echo "📥 Pulling latest changes..."
git pull origin main

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --optimize-autoloader --no-dev

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild caches for production
echo "⚡ Rebuilding production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Rebuild assets (if needed)
echo "🎨 Building assets..."
npm run build

echo "✅ Deployment complete!"
echo ""
echo "If you see any errors above, please check:"
echo "1. Database connection is working"
echo "2. All migrations ran successfully"
echo "3. File permissions are correct (storage/, bootstrap/cache/)"


