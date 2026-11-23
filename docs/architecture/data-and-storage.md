# Data & Storage Architecture

## Storage Boundary: Object Storage vs. POSIX Engine Volumes

| Data Class | Storage Location | Why |
| :--- | :--- | :--- |
| **User Photos, Videos, Thumbnails, Avatars, Vault** | `minio://chege-photos/` | Durable S3 object storage; zero host disk footprint on Web/ML containers. |
| **AI Model Weights (ONNX, Safetensors, CTranslate2)** | `minio://chege-models/` | Centralized artifact registry; ML container stays lean and stateless. |
| **Cold Backups (MySQL dumps, Qdrant snapshots)** | `minio://chege-backups/` | Automated via `php spark backup:minio`. |
| **MySQL Engine Files (`/var/lib/mysql`)** | Docker volume `chege-photos-mysql` | InnoDB requires 16KB atomic POSIX random I/O and `fsync()`. |
| **Qdrant Vector Files (`/qdrant/storage`)** | Docker volume `chege-photos-qdrant` | HNSW vector index relies on `mmap` and WAL. |
| **Redis Persistence (`/data`)** | Docker volume `chege-photos-redis` | AOF sequential appends for sessions and `chege:ml:scan:queue`. |
| **MinIO Data (`/data`)** | Docker volume `chege-photos-minio` | Physical backing store for MinIO object chunks. |

---

## MinIO Bucket Layout

```text
minio://chege-photos/
├── uploads/users/{user_id}/{YYYY}/{MM}/{random}.{ext}
├── uploads/avatars/user_{user_id}_{ts}.{ext}
└── thumbnails/users/{user_id}/{YYYY}/{MM}/{random}.jpg

minio://chege-models/
├── insightface/buffalo_l/         (det_10g.onnx, w600k_r50.onnx, genderage.onnx, ...)
├── yolo/yolov8n.onnx
├── nsfw/open_nsfw.onnx
├── siglip/google--siglip-base-patch16-224/
├── florence2/microsoft--Florence-2-base/
└── whisper/large-v3-turbo/

minio://chege-backups/
├── mysql/{db_name}_{YYYYMMDD_HHMMSS}.sql.gz
└── qdrant/{collection}/{YYYYMMDD_HHMMSS}_{snapshot}
```

---

## Stateless Upload & Zero-Disk Streaming Sequence

```mermaid
sequenceDiagram
    participant C as Client (Web / Android)
    participant N as Caddy 2 (:80/:443)
    participant W as PHP 8.4.28 FPM (:9000)
    participant M as MinIO S3 (:9000)
    participant ML as FastAPI ML (:8000)

    C->>N: POST /api/v1/upload (multipart)
    N->>W: php_fastcgi forward
    W->>W: Stage in /tmp -> EXIF + Thumbnail
    W->>M: SigV4 PUT uploads/... & thumbnails/...
    W->>W: unlink(/tmp/*) immediately
    W-->>C: HTTP 200 JSON (fastcgi_finish_request)
    W->>ML: POST /api/v1/faces/encode (async_task=1)
    ML->>M: SigV4 GET -> /tmp/chege_ml_media -> scan -> unlink

    C->>N: GET /uploads/... (Range: bytes=0-)
    N->>W: php_fastcgi (flush_interval -1)
    W->>M: SigV4 GET with Range header
    M-->>W: Stream chunks (zero disk write)
    W-->>C: HTTP 206 Partial Content
```

---

## Qdrant Vector Collections

| Collection | Dimensions | Distance | Model | Payload Indexes |
| :--- | :---: | :---: | :--- | :--- |
| `photo_embeddings` | **768** | Cosine | `google/siglip-base-patch16-224` (images + video transcripts) | `user_id`, `photo_id` |
| `face_embeddings` | **512** | Cosine | InsightFace `buffalo_l` (`w600k_r50.onnx`) | `user_id`, `photo_id` |
| `face_embeddings_centroids` | **512** | Cosine | Incremental person centroids | `user_id` |
