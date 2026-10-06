# Runbook: Vector Database & Model Snapshot Procedures

Disaster recovery procedures for Qdrant vector collections (`face_embeddings`, `face_centroids`, `clip_embeddings`) and MySQL metadata stores.

---

## 1. Storage Architecture

1. **Qdrant Vector Database**: Stores 512-dimensional vector indexes and payload attributes on port 6333.
2. **Metadata Store**: Relational store tracking scan job states, person identities, and cluster centroids.

---

## 2. Backup Procedures

### A. Qdrant Collection Snapshots

Qdrant provides a point-in-time snapshot API for zero-downtime backups:

```bash
# Snapshot face_embeddings collection
curl -X POST http://localhost:6333/collections/face_embeddings/snapshots

# Snapshot face_centroids collection
curl -X POST http://localhost:6333/collections/face_centroids/snapshots

# Snapshot clip_embeddings collection
curl -X POST http://localhost:6333/collections/clip_embeddings/snapshots
```

Snapshots are created inside the Qdrant storage volume under `snapshots/` as `.snapshot` files.

### B. Relational Metadata Snapshot

```bash
# Dump ML metadata tables from MySQL
docker compose exec mysql mysqldump \
  -u root -proot_password \
  --single-transaction \
  ml_chege_photos > backup_ml_metadata_$(date +%Y%m%d_%H%M%S).sql
```

---

## 3. Restoration Procedure

### Step 1: Restore Qdrant Snapshot
```bash
# Recover collection from uploaded snapshot file
curl -X POST http://localhost:6333/collections/face_embeddings/snapshots/recover \
  -H 'Content-Type: application/json' \
  -d '{"location": "http://backup-server/face_embeddings-snapshot.snapshot"}'
```

### Step 2: Verify Collection Health
```bash
# Check point count and index status
curl http://localhost:6333/collections/face_embeddings
```
