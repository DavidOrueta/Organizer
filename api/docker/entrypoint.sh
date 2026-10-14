#!/bin/sh
set -eu

cd /var/www/html
if [ -z "${APP_KEY:-}" ]; then
	if [ "${APP_ENV:-production}" = local ]; then
		APP_KEY="$(php artisan key:generate --show)"
		export APP_KEY
	else
		printf '%s\n' 'APP_KEY must be set in production.' >&2
		exit 1
	fi
fi

port="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:cache

php artisan migrate --force
exec apache2-foreground