# System Architecture Overview

Chege Photos uses a **Two-Repo Architecture** paired with **100% stateless compute containers** and **MinIO as primary object storage**.

---

## C4 Container Diagram

```mermaid
flowchart TB
    subgraph Users["End Users"]
        Browser["Web Browser"]
        Phone["Android Device (API 29–36)"]
    end

    subgraph Stateless["Stateless Compute Layer (ZERO Host Media or Model Volumes)"]
        Caddy["chege-photos-caddy\ncaddy:2-alpine (:80/:443)\n• https://chegephotos.local (tls internal)\n• flush_interval -1"]
        Web["chege-photos-web\nphp:8.4.28-fpm-bookworm (:9000)\n• CodeIgniter 4 + Shield\n• OPcache Tracing JIT\n• Zero-disk S3 streaming"]
        ML["chege-photos-ml\npython:3.12-slim + ffmpeg (:8000)\n• InsightFace buffalo_l\n• SigLIP 768-d + Florence-2\n• Whisper Large-v3-Turbo"]
    end

    subgraph PrimaryStore["Primary Object Storage"]
        MinIO["chege-photos-minio (:9000/:9001)\n• chege-photos (media, thumbs, avatars)\n• chege-models (all AI weights)\n• chege-backups (MySQL + Qdrant dumps)"]
    end

    subgraph StatefulEngines["Stateful Engine Volumes (POSIX I/O Required)"]
        MySQL["chege-photos-mysql (:3306)\nMySQL 8.4 InnoDB"]
        Qdrant["chege-photos-qdrant (:6333)\nVector DB (HNSW + mmap)"]
        Redis["chege-photos-redis (:6379)\nRedis 7 AOF (Sessions/Cache/Queue)"]
    end

    Browser -- HTTPS :443 --> Caddy
    Phone -- REST /api/v1/ :80 --> Caddy
    Caddy -- php_fastcgi :9000 --> Web
    Web -- SigV4 PUT/GET/Stream --> MinIO
    Web -- SQL --> MySQL
    Web -- Sessions & Cache --> Redis
    Web -- HTTP POST /api/v1/faces/encode --> ML
    ML -- SigV4 Pull Media & Weights --> MinIO
    ML -- BLPOP chege:ml:scan:queue --> Redis
    ML -- Upsert/Search Vectors --> Qdrant
    ML -- Write Faces/Tags/Captions/Transcripts --> MySQL
```

---

## Core Architectural Principles

1. **Strict Client Isolation:** The Android app (`Chege-Photos-App`) communicates **exclusively** with `chege-photos-caddy` on standard Port `80` / `443` (`/api/v1/...`). It has zero direct network access to ML, Redis, Qdrant, or MinIO.
2. **NTS PHP 8.4.28 FPM + Caddy 2:** Decouples HTTP/3 + local TLS (`https://chegephotos.local`) and static asset delivery (Caddy) from PHP application execution (Non-Thread-Safe PHP-FPM workers with OPcache Tracing JIT). Activates `fastcgi_finish_request()` for instant upload responses.
3. **Stateless Web & ML Containers:** Neither `chege-photos-web` nor `chege-photos-ml` mounts host media or model directories. Ephemeral `/tmp` scratch files are purged immediately after upload or inference.
4. **POSIX vs. Object Storage Separation:** Transactional engines (`mysql`, `qdrant`, `redis`) and `minio` itself retain minimal local Docker volumes for POSIX I/O, while all user media, AI model weights, and cold backups live in MinIO.
