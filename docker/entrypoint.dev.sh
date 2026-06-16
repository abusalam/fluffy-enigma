#!/bin/sh
set -e
cd /app

echo "[dev] Preparing development container…"

# Composer deps live in the dev-vendor volume. The volume may have been
# pre-seeded with the image's production (--no-dev) vendor, so also (re)install
# when dev tools like PHPUnit are missing.
if [ ! -f vendor/autoload.php ] || [ ! -f vendor/bin/phpunit ]; then
    echo "[dev] Installing Composer dependencies (incl. dev)…"
    composer install --no-interaction
fi

# Local env file.
if [ ! -f .env ]; then
    cp .env.example .env
fi
grep -q '^APP_KEY=base64' .env || php artisan key:generate --no-interaction

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public
DB_FILE="${DB_DATABASE:-/app/storage/app/database/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"

php artisan migrate --force --no-interaction
php artisan db:seed --class='Database\Seeders\PermissionSeeder' --force --no-interaction || true

php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "[dev] Ready → http://localhost:8000"
exec "$@"
