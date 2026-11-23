# Backup & Restore Runbook

All user media (`chege-photos`) and AI models (`chege-models`) already reside durably in MinIO. To back up the stateful engine volumes (**MySQL 8.4** and **Qdrant**) without accumulating files on the web container, use the stateless MinIO cold backup command.

---

## Automated Cold Backup to `minio://chege-backups/`

```bash
docker compose exec -T web php spark backup:minio
```

This command:
1. Streams a consistent compressed `mysqldump --single-transaction` archive to `minio://chege-backups/mysql/db_chege_photos_<TIMESTAMP>.sql.gz`.
2. Triggers Qdrant snapshots for `face_embeddings` and `photo_embeddings` and uploads them to `minio://chege-backups/qdrant/<collection>/`.
3. Deletes all ephemeral `/tmp` scratch files immediately.

---

## Restoring from Backup

### 1. Restore MySQL
Download the `.sql.gz` archive from `minio://chege-backups/mysql/` and pipe into `chege-photos-mysql`:
```bash
gunzip -c db_chege_photos_<TIMESTAMP>.sql.gz | docker compose exec -T mysql mysql -u root -p"${DB_ROOT_PASSWORD}" db_chege_photos
```

### 2. Rebuild Qdrant Vectors (Alternative to Snapshot Restore)
Because all originals live in `minio://chege-photos/`, Qdrant 768-d SigLIP embeddings, Florence-2 captions, and Whisper transcripts can always be regenerated cleanly:
```bash
docker compose exec -T web php spark ml:reindex-siglip
```
