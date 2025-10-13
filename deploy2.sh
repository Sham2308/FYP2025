#!/bin/bash
echo "=========================================="
echo "🚀 Starting Laravel deployment..."
echo "=========================================="

echo "📥 Pulling latest changes from GitHub..."
git pull origin main-2

echo "🧹 Cleaning old caches..."
php artisan config:clear
php artisan clear-compiled
php artisan package:discover --ansi

echo "🔧 Clearing and rebuilding caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache

echo "🛠️ Running database migrations..."
php artisan migrate --force

echo "♻️ Restarting PHP-FPM..."
systemctl restart php8.3-fpm

echo "=========================================="
echo "✅ Deployment finished successfully!"
echo "=========================================="


