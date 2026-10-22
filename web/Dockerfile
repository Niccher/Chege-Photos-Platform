# syntax=docker/dockerfile:1
FROM php:8.4.27-apache-bookworm

# ── Fast PHP extension installer (downloads optimized pre-compiled binaries) ──
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# ── System dependencies & PHP extensions ──────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        cron \
        ffmpeg \
        libheif-examples \
        zip \
        unzip \
    && install-php-extensions intl mysqli pdo_mysql zip gd exif redis \
    && rm -rf /var/lib/apt/lists/* \
    && a2dismod mpm_event mpm_worker mpm_prefork || true \
    && a2enmod mpm_prefork rewrite headers

# PHP upload & memory limits (PHP_INI_PERDIR — must be set in an ini file, not at runtime)
RUN { \
        echo "upload_max_filesize = 512M"; \
        echo "post_max_size = 512M"; \
        echo "memory_limit = 512M"; \
        echo "max_execution_time = 300"; \
        echo "max_input_time = 300"; \
    } > /usr/local/etc/php/conf.d/upload-limits.ini

# Move DocumentRoot to public/ and suppress ServerName FQDN warning
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV CI_ENVIRONMENT=development
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

# ── Composer dependency layer ─────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-reqs

# ── Backup vendor to /opt/vendor so bind mounts don't wipe installed packages ──
RUN cp -a /var/www/html/vendor /opt/vendor

# ── Application code ──────────────────────────────────────────────────────────
COPY . /var/www/html

RUN mkdir -p /var/www/html/writable/cache \
             /var/www/html/writable/logs \
             /var/www/html/writable/session \
             /var/www/html/public/uploads \
             /var/www/html/public/thumbnails \
    && chown -R www-data:www-data /var/www/html/writable /var/www/html/public/uploads /var/www/html/public/thumbnails \
    && chmod -R 775 /var/www/html/writable /var/www/html/public/uploads /var/www/html/public/thumbnails

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
EXPOSE 80
