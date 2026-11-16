#!/bin/sh
# Build-time only. Creates a ready-to-use SQLite demo database inside the image: schema, demo data
# and, optionally, product photos. For the free Render plan, which has no persistent storage and no
# database service: every restart begins again from this baked state.
set -eu

APP_DIR="${APP_DIR:-/var/www/html}"
cd "$APP_DIR"

if [ "${BAKE_DEMO_DB:-false}" != "true" ]; then
    echo "BAKE_DEMO_DB is not true: not baking demo data."
    exit 0
fi

DB_FILE="${BAKE_DB_FILE:-$APP_DIR/database/database.sqlite}"
touch "$DB_FILE"

# Build-time settings only. The key is a throwaway, never a real one: nothing encrypted is stored.
export APP_ENV=production
export APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA="
export DB_CONNECTION=sqlite DB_DATABASE="$DB_FILE"
export CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync MAIL_MAILER=log LOG_CHANNEL=stderr

php artisan migrate --seed --force --no-interaction

if [ "${BAKE_DEMO_PHOTOS:-false}" = "true" ]; then
    php artisan products:fetch-images || echo "Product photos could not be fetched; the demo will use placeholders." >&2
fi

if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data "$APP_DIR/database" "$APP_DIR/storage"
fi
