#!/bin/bash
set -e  # Exit immediately if a command fails

echo "=========================================="
echo "🚀 Starting Laravel deployment..."
echo "=========================================="

# Go to project directory (adjust if needed)
cd /var/www/tapnborrow

# Pull latest changes from GitHub
echo "📥 Pulling latest changes from GitHub..."
git pull origin main

# Clean old cache files
echo "🧹 Cleaning old caches..."
rm -f bootstrap/cache/*.php

# Install/update dependencies
echo "📦 Installing dependencies..."
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev -o --prefer-dist --no-interaction
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload -o

# Clear and rebuild caches
echo "🔧 Clearing and rebuilding caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache

# Run database migrations
echo "��️ Running database migrations..."
php artisan migrate --force

# Restart PHP-FPM (so new code is used immediately)
echo "♻️ Restarting PHP-FPM..."
systemctl restart php8.3-fpm  # ⚠️ Change php8.3-fpm if your version is different

echo "=========================================="
echo "✅ Deployment finished successfully!"
echo "=========================================="

