FROM php:8.4-apache

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libsqlite3-dev libzip-dev unzip \
	&& docker-php-ext-install pdo_mysql pdo_sqlite zip \
	&& a2enmod rewrite headers \
	&& rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
	&& sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
	&& printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/organizer.conf \
	&& a2enconf organizer \
	&& mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
	&& chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/organizer-entrypoint
RUN chmod +x /usr/local/bin/organizer-entrypoint

ENV PORT=8080
EXPOSE 8080
ENTRYPOINT ["organizer-entrypoint"]