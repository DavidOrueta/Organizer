#!/bin/sh
set -eu

cd /var/www/html
[ -f .env ] || cp .env.example .env

set_env() {
    key="$1"
    value="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '\n%s=%s\n' "$key" "$value" >> .env
    fi
}

set_env APP_URL "${APP_URL:-http://localhost:8000}"
set_env DB_CONNECTION "${DB_CONNECTION:-mysql}"
set_env DB_HOST "${DB_HOST:-database}"
set_env DB_PORT "${DB_PORT:-3306}"
set_env DB_DATABASE "${DB_DATABASE:-nexo}"
set_env DB_USERNAME "${DB_USERNAME:-nexo}"
set_env DB_PASSWORD "${DB_PASSWORD:-nexo-local}"
set_env DB_URL "${DB_URL:-mysql://nexo:nexo-local@database:3306/nexo}"

composer install --no-interaction --prefer-dist

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force
exec php artisan serve --host=0.0.0.0 --port=8000