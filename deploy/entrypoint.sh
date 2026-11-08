#!/bin/sh
# SokoTrack container entrypoint: configure, cache, migrate, then hand over to supervisord.
set -eu

APP_DIR="${APP_DIR:-/var/www/html}"
DEPLOY_DIR="${DEPLOY_DIR:-/opt/deploy}"
NGINX_CONF="${NGINX_CONF:-/etc/nginx/nginx.conf}"
FPM_POOL_CONF="${FPM_POOL_CONF:-/usr/local/etc/php-fpm.d/zz-sokotrack.conf}"

# Run artisan as the web user so cached files are not owned by root.
as_app() {
    if [ "${SKIP_SU_EXEC:-0}" = "1" ]; then
        "$@"
    else
        su-exec www-data "$@"
    fi
}

cd "$APP_DIR"

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

# Render exposes the public URL. Use it unless APP_URL is set, for example to a custom domain.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    APP_URL="$RENDER_EXTERNAL_URL"
    export APP_URL
fi

if [ -z "${MPESA_CALLBACK_URL:-}" ] && [ -n "${APP_URL:-}" ]; then
    MPESA_CALLBACK_URL="${APP_URL%/}/api/mpesa/stk-callback"
    export MPESA_CALLBACK_URL
fi

PORT="${PORT:-10000}"
MAX="${FPM_MAX_CHILDREN:-5}"

case "$PORT$MAX" in
    '' | *[!0-9]*)
        echo "PORT and FPM_MAX_CHILDREN must be whole numbers (got PORT=$PORT, FPM_MAX_CHILDREN=$MAX)." >&2
        exit 1
        ;;
esac

if [ "$MAX" -lt 1 ]; then
    echo "FPM_MAX_CHILDREN must be at least 1." >&2
    exit 1
fi

START=$(( (MAX + 1) / 2 ))
MIN_SPARE=$(( MAX / 3 ))
[ "$MIN_SPARE" -lt 1 ] && MIN_SPARE=1
MAX_SPARE=$(( MAX * 2 / 3 ))
[ "$MAX_SPARE" -lt "$MIN_SPARE" ] && MAX_SPARE="$MIN_SPARE"
[ "$START" -lt "$MIN_SPARE" ] && START="$MIN_SPARE"
[ "$START" -gt "$MAX_SPARE" ] && START="$MAX_SPARE"

sed -e "s/__PORT__/${PORT}/g" "$DEPLOY_DIR/nginx.conf.template" > "$NGINX_CONF"

sed -e "s/__FPM_MAX_CHILDREN__/${MAX}/g" \
    -e "s/__FPM_START_SERVERS__/${START}/g" \
    -e "s/__FPM_MIN_SPARE__/${MIN_SPARE}/g" \
    -e "s/__FPM_MAX_SPARE__/${MAX_SPARE}/g" \
    "$DEPLOY_DIR/php-fpm-pool.conf.template" > "$FPM_POOL_CONF"

# The persistent disk is mounted as root, so hand it to the web user.
if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
fi

# Cache configuration, events and views. Closure routes cannot be cached: that is not an error.
as_app php artisan config:cache
as_app php artisan event:cache
as_app php artisan view:cache
as_app php artisan route:cache || echo "route:cache skipped (the app has closure routes); routes are resolved on each request" >&2

# Wait for the database (a new MySQL service takes a few minutes on its very first start), then migrate.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    tries=0
    max_tries="${MIGRATE_MAX_TRIES:-60}"
    until as_app php artisan migrate --force --no-interaction; do
        tries=$((tries + 1))
        if [ "$tries" -ge "$max_tries" ]; then
            echo "Migrations still failing after $tries attempts. Giving up." >&2
            exit 1
        fi
        echo "Database not ready or migration failed (attempt $tries of $max_tries). Retrying in ${MIGRATE_RETRY_SECONDS:-5}s..." >&2
        sleep "${MIGRATE_RETRY_SECONDS:-5}"
    done
fi

exec "$@"
