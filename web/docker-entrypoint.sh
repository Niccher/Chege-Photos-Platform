#!/bin/bash
set -e

# Recreate writable directory structure if missing
mkdir -p /var/www/html/writable/cache
mkdir -p /var/www/html/writable/logs
mkdir -p /var/www/html/writable/session
mkdir -p /var/www/html/writable/uploads

# Ensure correct permissions for CI4's writable directory
chown -R www-data:www-data /var/www/html/writable
chmod -R 777 /var/www/html/writable

# Execute the original command
exec "$@"
