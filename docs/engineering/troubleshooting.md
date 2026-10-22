# Engineering Troubleshooting Guide

Diagnostic procedures for common compiler, runtime extension, and framework issues encountered during local development of Chege Photos WebApp.

---

## 1. Missing Required PHP Extensions

When running bare-metal without Docker, CodeIgniter 4 may fail on missing modules:

```bash
# Ubuntu / Debian install command for required PHP 8.4 extensions:
sudo apt install php8.4-intl php8.4-mysqli php8.4-gd php8.4-zip \
  php8.4-exif php8.4-curl php8.4-mbstring php8.4-xml
```

* **Symptoms**: `Call to undefined function intl_is_failure()` or blank pages during boot.
* **Fix**: Ensure all extensions above are enabled in `/etc/php/8.4/apache2/php.ini` and `/etc/php/8.4/cli/php.ini`.

---

## 2. Apache `mod_rewrite` & `.htaccess` Issues

If navigating to routes returns `404 Not Found` or `403 Forbidden` on bare-metal Apache:

```bash
# 1. Enable rewrite module
sudo a2enmod rewrite

# 2. Allow overrides in Apache virtual host configuration:
# <Directory /path/to/public>
#     AllowOverride All
#     Require all granted
# </Directory>

# 3. Reload Apache
sudo systemctl reload apache2
```

---

## 3. Database Migration State Desynchronization

If `php spark migrate` reports that a migration failed or table already exists:

```bash
# Check current migration batch and history
php spark migrate:status

# To roll back the last batch:
php spark migrate:rollback

# To reset all migrations and re-run (DESTRUCTIVE - wipes dev tables):
php spark migrate:refresh
```

---

## 4. `writable/` Directory Permissions

CodeIgniter requires write access to log files, file sessions, and cache:

```bash
# Set appropriate permissions for the web server user
chmod -R 775 writable/ public/uploads/ public/thumbnails/
sudo chown -R www-data:www-data writable/ public/uploads/ public/thumbnails/
```
