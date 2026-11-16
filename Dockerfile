# syntax=docker/dockerfile:1

# Employee Satisfaction Survey: PHP 8.4 + Apache, with the Microsoft ODBC
# driver for SQL Server. Runs as www-data on port 8080; the configuration
# comes from the .env file mounted by docker-compose.yml.
FROM php:8.4-apache-bookworm

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# gd (with WebP) re-encodes uploaded images; pdo_sqlsrv also installs the
# Microsoft ODBC Driver 18 for SQL Server; intl backs Laravel's Number helper.
RUN install-php-extensions gd intl pdo_sqlsrv opcache \
    && apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && a2enmod rewrite headers \
    && sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf

COPY <<'EOF' /usr/local/etc/php/conf.d/zz-app.ini
expose_php = Off
; Uploads are limited to 2 MB by the validation rules, which give a clear message.
upload_max_filesize = 8M
post_max_size = 20M
; GD decodes images up to 6000 x 6000 pixels (see App\Helpers\FileApps).
memory_limit = 256M
; The code is part of the image and never changes in a running container.
opcache.validate_timestamps = 0
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
EOF

COPY <<'EOF' /etc/apache2/sites-available/000-default.conf
ServerName localhost
ServerTokens Prod
ServerSignature Off

<VirtualHost *:8080>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Caches the configuration, routes, views and events with the runtime
# environment (.env), then starts the given command (Apache by default).
COPY --chmod=755 <<'EOF' /usr/local/bin/app-entrypoint
#!/bin/sh
set -e

php artisan optimize --quiet

exec docker-php-entrypoint "$@"
EOF

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan storage:link \
    && mkdir -p /var/www/.config \
    && chown -R www-data:www-data storage bootstrap/cache /var/www/.config

USER www-data
EXPOSE 8080

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
