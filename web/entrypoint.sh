#!/bin/bash
set -e

# ── Self-healing vendor dependency restoration ────────────────────────────────
# If host volume shadowing or an empty volume mount hides vendor/, restore from /opt/vendor
if [ ! -f "/var/www/html/vendor/autoload.php" ] || [ ! -d "/var/www/html/vendor/codeigniter4/shield" ]; then
    if [ -d "/opt/vendor" ]; then
        echo "Restoring baked-in Composer vendor dependencies from /opt/vendor into /var/www/html/vendor..."
        mkdir -p /var/www/html/vendor
        cp -a /opt/vendor/. /var/www/html/vendor/
        echo "Composer vendor dependencies restored successfully."
    else
        echo "WARNING: /opt/vendor not found. Attempting emergency composer install..."
        cd /var/www/html
        composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist 2>&1 \
            || echo "WARNING: composer install encountered errors."
    fi
else
    echo "Vendor integrity OK."
fi

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
MIGRATION_FAILED=0
if ! CI_ENVIRONMENT=development php spark migrate --all; then
    echo "=========================================="
    echo "WARNING: Migrations failed. Apache will"
    echo "still start. Check logs and fix manually:"
    echo "  docker compose logs web"
    echo "  docker exec chege-photos-web php spark migrate:status"
    echo "=========================================="
    MIGRATION_FAILED=1
fi
if [ "$MIGRATION_FAILED" -eq 0 ]; then
    CI_ENVIRONMENT=development php spark db:seed SystemDefaultSeeder || echo "WARNING: System default seeding skipped."
fi

# Ensure session table exists so database session handler never fails
echo "Verifying session table exists..."
php -r '
$h = getenv("DB_HOST") ?: "mysql";
$pt = getenv("DB_PORT") ?: "3306";
$u = getenv("DB_USER") ?: "root";
$p = getenv("DB_PASS") ?: "root_password";
$d = getenv("DB_NAME") ?: "db_chege_photos";
try {
    $pdo = new PDO("mysql:host={$h};port={$pt};dbname={$d}", $u, $p);
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ci_sessions` (
        `id` varchar(128) NOT NULL,
        `ip_address` varchar(45) NOT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `data` blob NOT NULL,
        KEY `ci_sessions_timestamp` (`timestamp`),
        PRIMARY KEY (`id`,`ip_address`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
} catch (\Throwable $e) {}
' 2>/dev/null || true

# Re-enforce writable permissions after CLI root execution (targeted to writable only)
chown -R www-data:www-data /var/www/html/writable 2>/dev/null || true

# Setup container cron jobs
echo "Configuring container cron schedules..."
printenv | sed 's/^\(.*\)=\(.*\)$/export \1="\2"/' > /var/www/html/writable/cron_env.sh 2>/dev/null || true
chmod 0644 /var/www/html/writable/cron_env.sh 2>/dev/null || true
printenv | grep -E '^(MYSQL|DB_|CI_|PORT|APACHE|ML_|QDRANT_|RAILWAY|REDIS_|APP_|encryption\.|app\.)' > /etc/environment || true

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

# Stream background cron execution to container stdout so scheduled failures appear live in Docker logs
(tail -n 0 -F /var/log/cron-run.log > /proc/1/fd/1 2>/dev/null) &

# Force remove conflicting MPMs and ensure mpm_prefork is active
echo "Configuring Apache MPM..."
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf 2>/dev/null || true

# Pre-warm lightweight thumbnails from GCP in background (non-blocking for instant Apache boot)
echo "Triggering background thumbnail pre-warming from GCP..."
(sleep 4 && php /var/www/html/spark cloud:sync --hydrate --thumbnails-only >> /var/log/hydrate.log 2>&1) &

echo "Migrations complete. Starting Apache..."
exec apache2-foreground
