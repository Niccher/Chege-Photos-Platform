# syntax=docker/dockerfile:1
# Patched PHP 8.4.28 NTS FPM (fixes CVE-2026-126714 phpAfterShell, CVE-2026-129289, CVE-2026-115560)
FROM php:8.4.28-fpm-bookworm

# ── Fast PHP extension installer (downloads optimized pre-compiled binaries) ──
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# ── System dependencies & PHP extensions ──────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        cron \
        default-mysql-client \
        ffmpeg \
        libheif-examples \
        zip \
        unzip \
    && install-php-extensions intl mysqli pdo_mysql zip gd exif redis opcache \
    && rm -rf /var/lib/apt/lists/*

# ── Upload limits, CVE defense-in-depth & OPcache Tracing JIT ─────────────────
RUN { \
        echo "upload_max_filesize = 512M"; \
        echo "post_max_size = 512M"; \
        echo "memory_limit = 512M"; \
        echo "max_execution_time = 300"; \
        echo "max_input_time = 300"; \
    } > /usr/local/etc/php/conf.d/upload-limits.ini \
    && { \
        echo "expose_php = Off"; \
        echo "session.upload_progress.enabled = 0"; \
        echo "opcache.enable = 1"; \
        echo "opcache.enable_cli = 0"; \
        echo "opcache.memory_consumption = 128"; \
        echo "opcache.interned_strings_buffer = 16"; \
        echo "opcache.max_accelerated_files = 10000"; \
        echo "opcache.jit = tracing"; \
        echo "opcache.jit_buffer_size = 64M"; \
        echo "opcache.validate_timestamps = 1"; \
        echo "opcache.revalidate_freq = 2"; \
    } > /usr/local/etc/php/conf.d/security-opcache.ini \
    && { \
        echo "[www]"; \
        echo "clear_env = no"; \
        echo "pm = dynamic"; \
        echo "pm.max_children = 24"; \
        echo "pm.start_servers = 4"; \
        echo "pm.min_spare_servers = 2"; \
        echo "pm.max_spare_servers = 8"; \
        echo "pm.max_requests = 500"; \
        echo "catch_workers_output = yes"; \
        echo "decorate_workers_output = no"; \
    } > /usr/local/etc/php-fpm.d/zz-chege-pool.conf

ENV CI_ENVIRONMENT=development
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
EXPOSE 9000
