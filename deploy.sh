#!/usr/bin/env bash
# ==============================================================================
# FORMÉ — High-Performance Production Deployment & Cache Warmer
# ==============================================================================
set -e

echo "🚀 [1/6] Running Database Migrations..."
php artisan migrate --force

if command -v npm >/dev/null 2>&1; then
    echo "📦 [2/6] Building Production Frontend Assets (Vite)..."
    npm run build
else
    echo "ℹ️  [2/6] 'npm' not found on this server — using pre-built assets in public/build/."
fi

echo "🧹 [3/6] Clearing Old Caches..."
php artisan optimize:clear

echo "⚡ [4/6] Priming Production Optimized Caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "🔄 [5/6] Gracefully Restarting Background Workers..."
php artisan queue:restart || true

echo "🔒 [6/6] Checking Storage Symlink..."
php artisan storage:link || true

echo ""
echo "=========================================================="
echo "✅ FORMÉ Deployment & Optimization Completed Successfully!"
echo "=========================================================="

