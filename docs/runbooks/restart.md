# Runbook: Graceful Restart & Container Recovery

Procedures for stopping, updating, restarting, and recovering Chege Photos WebApp containers without losing user media or database records.

---

## 1. Graceful Container Restart

When applying environment variable updates in `.env` or refreshing Apache configurations:

```bash
# Gracefully stop containers (allows in-flight HTTP requests and MySQL flushes to complete)
docker compose stop

# Restart containers
docker compose start

# Verify health status
docker compose ps
curl -I http://localhost:9005/api/v1/health
```

---

## 2. Rebuild Without Volume Loss

When updating PHP dependencies or pulling new git commits, rebuild container images while preserving named Docker volumes (`mysql_data` and persistent uploads):

```bash
# Rebuild images without cache
docker compose build --no-cache

# Recreate containers with new image
docker compose up -d

# Check startup logs for migration execution
docker compose logs -f webapp
```

> [!CAUTION]
> Never run `docker compose down -v` on production environments! The `-v` flag deletes all attached persistent volumes, destroying database records and local cache files.

---

## 3. MySQL Deadlock Recovery

If MySQL hangs on startup or reports table locking errors after an abrupt power outage:

```bash
# Check MySQL container status
docker compose logs --tail=100 mysql

# If InnoDB recovery is needed, force safe recovery mode in docker-compose.yml:
# command: --innodb-force-recovery=1

# Once database starts, dump tables immediately, revert the setting, and restart:
docker compose exec mysql mysqldump -u root -p db_chege_photos > emergency_dump.sql
```

---

## 4. Emergency Container Reset (Safe Reset)

To purge temporary container state, sessions, and CodeIgniter file caches while keeping media intact:

```bash
# Clear CodeIgniter writable caches inside the container
docker compose exec webapp php spark cache:clear

# Restart web server process
docker compose exec webapp apachectl -k graceful
```
