# Changelog

All notable changes to the Chege Photos Platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.3.0] - 2026-10-22

### Security & Hardening
- **PHP 8.4.27 Upgrade**: Upgraded `web/Dockerfile` base image to `php:8.4.27-apache-bookworm` to completely remediate critical unauthenticated RCE vulnerabilities (`php2shell`):
  - **CVE-2026-109618** (CVSS 9.2): Memory corruption via uninitialized request state during out-of-memory (`memory_limit`) aborts via `longjmp()`.
  - **CVE-2026-109619** (CVSS 9.2): Stale global state pointers referencing freed request memory leading to use-after-free RCE.
- **Dozzle Access Protection**: Added optional native password authentication (`DOZZLE_AUTH_PROVIDER=simple`, `DOZZLE_USERS`) and documented zero-exposure private SSH tunneling (`ssh -N -L 8888:localhost:8888`).
- **Chainguard Distroless Hardening**: Switched MinIO client to `cgr.dev/chainguard/minio-client:latest-dev` with root execution privileges for deterministic bucket initialization.

### Added
- **Multi-Cloud Object Storage (MinIO S3)**: Integrated MinIO on standard ports `9000` (S3 API) and `9001` (Web Console) with AWS SigV4 signing, decoupling photo and thumbnail storage from local server disks.
- **Two-Tier Observability**:
  - *Tier 1 (Core Diagnostics)*: Direct container stderr streaming in PHP (`ErrorlogHandler`), Starlette request latency middleware in ML microservice, background cron log forwarding, and Docker log rotation (max 20MB / 5 files).
  - *Tier 2 (Visual Logs)*: Deployed Dozzle Go binary container (`chege-dozzle`) on port `8888` for split-screen real-time container log inspection.
- **Host Diagnostic Utility (`scripts/logs.sh`)**: CLI utility supporting real-time multi-tailing, error scanning (`--errors`), and diagnostic bundle archiving (`--dump`).
- **Single Source of Truth Versioning**: Root `VERSION` file with synchronized release metadata across WebApp (`PLATFORM_VERSION`), API (`/api/v1/health`), and Android client.

### Changed
- Refactored `scripts/deploy.sh` to automatically manage `DOZZLE_PORT`, `MINIO_PORT`, and auth parameters.
- Overhauled public landing pages (`home.php`, `about.php`, `setup.php`, `layouts/public.php`) to document the 5-stage media pipeline and all 7 supporting containers.

---

## [1.2.0] - 2026-09-15

### Added
- **Multimodal AI Vision Suite (FastAPI / PyTorch)**:
  - InsightFace RetinaFace face detection and ArcFace 512-dimensional vector embeddings.
  - Unsupervised HDBSCAN clustering for automatic grouping of faces into named person identities.
  - OpenAI CLIP cross-modal text-to-image search indexed in Qdrant HNSW vector database.
  - Ultralytics YOLOv8 object and scene classification across 80+ visual categories.
  - Tesseract OCR for text recognition within screenshots, receipts, and signs.
- **Frictionless Mobile Pairing**: Fast Android companion client pairing via QR code scanning or 6-digit numeric PIN.
- **Redis Asynchronous Queue**: Decoupled deep learning inference pipeline from web requests using Redis job queues.

---

## [1.1.0] - 2026-08-01

### Added
- **Core Web Application**: Built on CodeIgniter 4 with Shield authentication, chunked file uploads up to 512MB, and EXIF extraction.
- **Relational Storage**: MySQL 8.4 schema for photos, albums, EXIF metadata, and user quotas.
- **Interactive Spatial Heatmaps**: Leaflet GPS heatmaps based on embedded photo coordinates.
- **Memories Engine**: Automated "On This Day" and seasonal flash-back timeline generation.
- **Cloud Hydration**: Google Cloud Storage backup worker for offsite disaster recovery.
