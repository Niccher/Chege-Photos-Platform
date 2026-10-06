# Runbook: Database & Media Backup and Restore

Operational guidelines for automated snapshotting and full disaster recovery of the MySQL database and uploaded media assets.

---

## 1. Backup Strategy Overview

Chege Photos WebApp relies on two primary data stores:
1. **Relational Database (`db_chege_photos`)**: MySQL 8.4 on port 9306 containing users, photo metadata, album links, and facial attributes.
2. **Media Assets (`public/uploads/` & `public/thumbnails/`)**: Original files and generated thumbnails organized in `{user_id}/{YYYY}/{MM}/` hierarchy.

---

## 2. Backup Procedures

### A. Database Snapshot (`mysqldump`)

Create a compressed transactional snapshot of the database:

```bash
# Execute mysqldump inside container
docker compose exec mysql mysqldump \
  -u root -proot_password \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  db_chege_photos | gzip > backup_db_chege_photos_$(date +%Y%m%d_%H%M%S).sql.gz
```

### B. Uploaded Media Archive

Archive local photos and thumbnails with path preservation:

```bash
# Tar and compress uploads and thumbnails
tar -czvf backup_media_$(date +%Y%m%d_%H%M%S).tar.gz \
  public/uploads \
  public/thumbnails
```

---

## 3. Full Restoration Procedure

### Step 1: Restore Database
```bash
# Decompress and import database dump
gunzip < backup_db_chege_photos_YYYYMMDD_HHMMSS.sql.gz | \
  docker compose exec -T mysql mysql -u root -proot_password db_chege_photos
```

### Step 2: Restore Media Files
```bash
# Extract media archives into webapp root
tar -xzvf backup_media_YYYYMMDD_HHMMSS.tar.gz -C .

# Set proper write permissions for Apache user
chmod -R 775 public/uploads public/thumbnails
```

### Step 3: Verify Integrity
```bash
# 1. Run database migrations to ensure schema synchronization
docker compose exec webapp php spark migrate

# 2. Check HTTP API health
curl -I http://localhost:9005/api/v1/health

# 3. Verify media fallback and thumbnail loading in browser
```
