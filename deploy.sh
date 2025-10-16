#!/bin/bash

set -e

echo "=========================================="
echo "🚀 Starting Laravel deployment..."
echo "=========================================="

echo "📥 Pulling latest changes from GitHub..."
git pull origin main-2

echo "🧹 Fixing permissions..."
sudo chown -R www-data:www-data /var/www/project2/storage /var/www/project2/bootstrap/cache
sudo chmod -R 775 /var/www/project2/storage /var/www/project2/bootstrap/cache

echo "🧹 Cleaning old caches..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan clear-compiled
sudo -u www-data php artisan package:discover --ansi

echo "🔧 Rebuilding caches..."
sudo -u www-data php artisan config:cache

echo "🛠️ Running database migrations..."
sudo -u www-data php artisan migrate --force

echo "♻️ Restarting PHP-FPM..."
sudo --non-interactive /usr/bin/systemctl restart php8.3-fpm.service

echo "=========================================="
echo "✅ Deployment finished successfully!"
echo "=========================================="




