#!/bin/bash
set -e

# Initialize CodeIgniter writable directories (handles cases where a volume is mounted and empty)
echo "Ensuring writable directories exist and have correct permissions..."
mkdir -p /var/www/html/writable/cache \
         /var/www/html/writable/logs \
         /var/www/html/writable/session \
         /var/www/html/writable/debugbar \
         /var/www/html/writable/uploads \
         /var/www/html/public/uploads \
         /var/www/html/public/thumbnails

# Handle unified persistent volume if mounted at /data on Railway
if [ -d "/data" ]; then
    echo "Persistent volume detected at /data. Linking uploads and thumbnails..."
    mkdir -p /data/uploads /data/thumbnails
    if [ ! -L "/var/www/html/public/uploads" ]; then
        cp -rn /var/www/html/public/uploads/* /data/uploads/ 2>/dev/null || true
        rm -rf /var/www/html/public/uploads
        ln -sfn /data/uploads /var/www/html/public/uploads
    fi
    if [ ! -L "/var/www/html/public/thumbnails" ]; then
        cp -rn /var/www/html/public/thumbnails/* /data/thumbnails/ 2>/dev/null || true
        rm -rf /var/www/html/public/thumbnails
        ln -sfn /data/thumbnails /var/www/html/public/thumbnails
    fi
    chmod 775 /data 2>/dev/null || true
    chown www-data:www-data /data 2>/dev/null || true
fi

# Directory-level permissions (non-recursive to avoid freezing container startup on large media libraries)
chown -R www-data:www-data /var/www/html/writable 2>/dev/null || true
chown www-data:www-data /var/www/html/public/uploads /var/www/html/public/thumbnails 2>/dev/null || true
chmod 775 /var/www/html/public/uploads /var/www/html/public/thumbnails 2>/dev/null || true

# Wait for MySQL to accept connections (fast poll: Compose already verified container health)
echo "Verifying MySQL connection..."
max_retries=15
count=0
while ! php -r '$h=getenv("database.default.hostname")?:(getenv("database_default_hostname")?:(getenv("DB_HOST")?:(getenv("MYSQLHOST")?:"mysql"))); $pt=getenv("database.default.port")?:(getenv("database_default_port")?:(getenv("DB_PORT")?:(getenv("MYSQLPORT")?:"3306"))); $u=getenv("database.default.username")?:(getenv("database_default_username")?:(getenv("DB_USER")?:(getenv("MYSQLUSER")?:"root"))); $p=getenv("database.default.password")?:(getenv("database_default_password")?:(getenv("DB_PASS")?:(getenv("MYSQLPASSWORD")?:"root_password"))); new PDO("mysql:host={$h};port={$pt}", $u, $p);' 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_retries ]; then
        echo "ERROR: MySQL did not become ready in time. Starting Apache without migrations."
        exec apache2-foreground
    fi
    echo "MySQL connection warming up... ($count/$max_retries)"
    sleep 1
done

echo "MySQL connection confirmed!"

# Run migrations (safe to run multiple times — only applies pending migrations)
echo "Running database migrations..."
cd /var/www/html
CI_ENVIRONMENT=development php spark migrate --all || { echo "FATAL ERROR: Migrations failed. See above for details."; exit 1; }
CI_ENVIRONMENT=development php spark db:seed SystemDefaultSeeder || echo "WARNING: System default seeding skipped."

# Re-enforce writable permissions after CLI root execution (targeted to writable only)
chown -R www-data:www-data /var/www/html/writable 2>/dev/null || true

# Setup container cron jobs
echo "Configuring container cron schedules..."
printenv | grep -E '^(MYSQL|DB_|CI_|PORT|APACHE|ML_|QDRANT_|RAILWAY)' > /etc/environment || true

cat << 'EOF' > /etc/cron.d/photos-cron
# Run master task scheduler every minute with environment loaded
* * * * * . /etc/environment; /usr/local/bin/php /var/www/html/spark cron:run >> /var/log/cron-run.log 2>&1
EOF

chmod 0644 /etc/cron.d/photos-cron
crontab /etc/cron.d/photos-cron
touch /var/log/cron-run.log
chmod 0666 /var/log/cron-run.log

# Start the cron service
echo "Starting cron service..."
service cron start || cron || echo "WARNING: Could not start cron daemon."

# Force remove conflicting MPMs just in case they were pulled in by a package update
echo "Cleaning up conflicting Apache MPMs..."
rm -f /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf

# Pre-warm lightweight thumbnails from GCP in background (non-blocking for instant Apache boot)
echo "Triggering background thumbnail pre-warming from GCP..."
(sleep 4 && php /var/www/html/spark cloud:sync --hydrate --thumbnails-only >> /var/log/hydrate.log 2>&1) &

echo "Migrations complete. Starting Apache..."
exec apache2-foreground
