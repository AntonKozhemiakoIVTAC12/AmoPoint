#!/bin/sh
set -e

cd /var/www/html

# SQLite persists in /data — на Railway/Fly туда монтируется volume.
: "${DB_CONNECTION:=sqlite}"
: "${DB_DATABASE:=/data/database.sqlite}"
export DB_CONNECTION DB_DATABASE

if [ "$DB_CONNECTION" = "sqlite" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    [ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
fi

# APP_KEY обязателен в проде. Если не задан — генерируем эфемерный
# (при рестарте контейнера ключ изменится → сессии слетят).
if [ -z "$APP_KEY" ]; then
    [ -f .env ] || cp .env.example .env 2>/dev/null || true
    php artisan key:generate --force >/dev/null 2>&1 || true
    echo "WARN: APP_KEY env var is not set. Generated ephemeral key — set APP_KEY in Railway → Variables to keep sessions stable across restarts." >&2
fi

php artisan migrate --force --no-interaction
php artisan db:seed --force --class=AdminUserSeeder --no-interaction || true

PORT="${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
