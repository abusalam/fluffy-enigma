#!/bin/sh
set -e
cd /app

# --- Ensure writable runtime dirs exist (volumes may start empty) ----------
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public

# SQLite DB file (path from DB_DATABASE; defaults under the storage volume).
DB_FILE="${DB_DATABASE:-/app/storage/app/database/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"

# --- Application key --------------------------------------------------------
# Prefer APP_KEY from the environment. If it is empty and .env has no key,
# generate one (persisted into .env so it survives container restarts only
# when .env is volume-mounted; otherwise set APP_KEY in your env file).
if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64' .env 2>/dev/null; then
    echo "[entrypoint] Generating application key…"
    php artisan key:generate --force --no-interaction || true
fi

# --- Database --------------------------------------------------------------
echo "[entrypoint] Running migrations…"
php artisan migrate --force --no-interaction

echo "[entrypoint] Seeding baseline roles & permissions…"
php artisan db:seed --class='Database\Seeders\PermissionSeeder' --force --no-interaction || true

# --- Public storage symlink (logo uploads) ---------------------------------
php artisan storage:link --force >/dev/null 2>&1 || true

# --- Caches (env is available now) -----------------------------------------
php artisan config:cache >/dev/null 2>&1 || true
php artisan route:cache  >/dev/null 2>&1 || true
php artisan view:cache   >/dev/null 2>&1 || true

# --- Permissions on writable paths (NOT the source database/ dir) ----------
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting FrankenPHP…"
exec "$@"
