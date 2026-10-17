# Architecture Overview

System context, container topology, component responsibilities, and trust boundaries for Chege Photos WebApp.

---

## 1. System Context Diagram

```mermaid
C4Context
  title System Context - Chege Photos Ecosystem
  Person(user, "User / Photographer", "Uploads, organizes, browses memories, and shares albums.")
  Person(admin, "Superadmin", "Monitors telemetry, manages storage quotas, configures cron tasks.")

  System(webapp, "Chege Photos WebApp", "PHP 8.3 / CodeIgniter 4. Core web UI, admin console, authentication, and mobile sync gateway.")
  System(ml, "ML Chege Photos", "FastAPI / Python 3.12. Neural network face detection, ArcFace embeddings, YOLO tags, CLIP vision.")
  SystemDb(qdrant, "Qdrant Vector DB", "HNSW high-dimensional vector similarity index.")
  SystemDb(mysql, "MySQL 8.4 Database", "Relational metadata catalog, EXIF storage, and user credentials.")
  System_Ext(gcs, "Google Cloud Storage", "Persistent durable offsite media archive.")
  System(android, "Chege Photos Android", "Kotlin Jetpack Compose companion mobile client.")

  Rel(user, webapp, "Uses via Desktop Browser", "HTTPS / Session Cookie")
  Rel(user, android, "Uses via Mobile Phone", "Native Touch UI")
  Rel(android, webapp, "Syncs photos and queries API", "HTTPS / 8-char Bearer Token")
  Rel(admin, webapp, "Accesses admin console", "HTTPS / Session Cookie")
  Rel(webapp, mysql, "Reads and writes records", "TCP 3306 / MySQL Wire")
  Rel(webapp, redis, "Caches sessions & dispatches jobs", "TCP 6379 / Redis Protocol")
  Rel(webapp, minio, "Stores and retrieves media objects", "HTTP 9000 / AWS SigV4")
  Rel(webapp, ml, "Triggers AI scan / search proxy", "HTTP 8000 / REST + X-API-KEY")
  Rel(ml, qdrant, "Upserts and queries vectors", "HTTP 6333 / gRPC 6334")
  Rel(ml, mysql, "Persists face metadata & tags", "TCP 3306 / SQLAlchemy")
  Rel(ml, minio, "Resolves media for inference", "HTTP 9000 / AWS SigV4")
  Rel(webapp, gcs, "Mirrors media for offsite disaster backup", "HTTPS 443 / JSON REST")
```

---

## 2. Container Topology

| Container / Service | Technology | Port (Internal) | Standard Production Port | Responsibility |
|---|---|---|---|---|
| `chege-photos-web` | PHP 8.3 / Apache | `80` | `80` | Web controllers, views, REST API, fallback hydration, cron runner |
| `chege-photos-ml` | FastAPI / PyTorch | `8000` | `8000` | Face detection (ArcFace), CLIP semantic search, YOLOv8 tagging |
| `chege-minio` | MinIO Object Storage | `9000` / `9001` | `9000` / `9001` | Primary S3-compatible photo/thumbnail storage + Web Console |
| `chege-redis` | Redis 7 Alpine | `6379` | `6379` | In-memory session store with MySQL fallback, queue broker |
| `chege-qdrant` | Qdrant Vector DB | `6333` / `6334` | `6333` / `6334` | Sub-second HNSW cosine vector similarity indexing |
| `chege-mysql` | MySQL 8.4 | `3306` | `3306` | Relational storage for users, photos, albums, and system logs |
| `chege-dozzle` | Dozzle Go binary | `8080` | `8888` | Real-time visual log inspection and multi-container live tail |

---

## 3. Trust Boundaries & Security Architecture

1. **Web Browser Boundary**:
   - Authenticated via CodeIgniter Shield session cookies with `HttpOnly`, `SameSite=Lax`, and secure session handling.
   - CSRF protection enabled across all POST/PUT/DELETE web form submissions.
2. **Mobile REST Boundary**:
   - Uses cryptographic 8-character device tokens passed in the `Authorization: Bearer <token>` header.
   - Bypasses CSRF filter; validated against `tbl_auth_tokens` on every request.
3. **ML Service Proxy Boundary**:
   - Internal microservice communication requires the `X-API-KEY` secret header.
   - Non-blocking cURL calls use short timeouts ($\le 100\text{ms}$) so external ML downtime never blocks the client.
4. **Multi-Tenant Row-Level Security**:
   - Every database query strictly binds `user_id = auth()->id()`.
   - SQL queries with `OR` clauses use strict `groupStart()` and `groupEnd()` blocks to eliminate cross-tenant data leaks.
