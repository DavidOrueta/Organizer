FROM php:8.4-cli

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libsqlite3-dev libzip-dev unzip \
	&& docker-php-ext-install pdo_mysql pdo_sqlite zip \
	&& rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/nexo-entrypoint
RUN chmod +x /usr/local/bin/nexo-entrypoint

EXPOSE 8000
ENTRYPOINT ["nexo-entrypoint"]