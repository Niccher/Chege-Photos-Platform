# Deployment Architecture

Production hosting blueprints for Railway, Docker Compose, and self-hosted VPS servers.

---

## 1. Railway Cloud Deployment (Recommended)

Chege Photos WebApp is engineered specifically to deploy effortlessly on [Railway](https://railway.app).

### Setup Steps
1. **Provision MySQL Database**: Add a MySQL database instance in your Railway project.
2. **Deploy WebApp Service**: Connect your GitHub repository `Chege-Photos-WebApp`.
3. **Environment Variables**:
   - Railway auto-provisions `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`.
   - Set `CI_ENVIRONMENT` to `production`.
   - Set `app.baseURL` to your public Railway domain: `https://your-domain.up.railway.app/`.
   - Add Google Cloud Storage credentials (`GCP_BUCKET`, `GCP_SERVICE_ACCOUNT_JSON`).
4. **Service Startup**:
   - `entrypoint.sh` runs automatically on container boot: waits for MySQL, executes migrations (`spark migrate --all`), seeds system defaults, configures cron, and starts Apache.

---

## 2. Docker Compose Multi-Container Deployment

For private VPS deployment (Ubuntu 22.04 / 24.04, Debian 12) or local orchestration:

- **MySQL 8.4 Database**: Relational metadata, users, photos, albums, and cron logs (Port `3306`).
- **Redis In-Memory Broker**: Background queue and caching (Port `6379`).
- **Qdrant Vector Database**: Face and CLIP semantic vector embeddings (Port `6333` HTTP / `6334` gRPC).
- **MinIO Object Storage**: S3-compatible primary photo/thumbnail bucket (Port `9000` S3 API / `9001` Web Console via `cgr.dev/chainguard/minio`).
- **MinIO Initialization**: Automatic bucket creation (`chege-photos`) and thumbnail download policy via `cgr.dev/chainguard/minio-client`.
- **Dozzle Visual Log Viewer**: Lightweight real-time container log console (Port `8888`).
- **WebApp Gateway**: CodeIgniter 4 PHP 8.3 & Apache API/UI (Port `80`).
- **ML Microservice**: FastAPI Vision & embedding microservice (Port `8000`).

One-click automated deployment:
```bash
bash scripts/deploy.sh
```

Or start services manually with Docker Compose:
```bash
docker compose up --build -d
```

---

## 3. Container Cron Service

Background CLI commands (`spark cron:run`) are managed by the container's built-in cron daemon configured in `/etc/cron.d/photos-cron`. It executes every minute, checking database settings for scheduled maintenance tasks (`trash:purge`, `cloud:sync`, `ml:cluster`).

Cron execution failures and output stream in real time to the container's stdout/stderr, visible in Docker logs.

---

## 4. Two-Tier Logging & Observability Architecture

Chege Photos Platform implements a low-overhead, two-tier logging design:

### Tier 1: Application Streaming & Host CLI Diagnostics
* **WebApp (PHP / CodeIgniter 4)**: Configured with `ErrorlogHandler` in `Logger.php`, streaming all framework exceptions, database query errors, and warnings directly to Docker container `stderr`.
* **ML Microservice (FastAPI / PyTorch)**: HTTP middleware tracks microsecond request duration (`duration_ms`), client IP, status codes, and logs full tracebacks for unhandled exceptions.
* **Log Rotation**: Hard-capped in `docker-compose.yml` (`json-file`, max 20MB x 5 files per container) to permanently prevent GCP VM disk exhaustion.
* **Host CLI Diagnostics (`scripts/logs.sh`)**:
  ```bash
  # Live tail across all containers with colored tags:
  bash scripts/logs.sh

  # Tail a specific service:
  bash scripts/logs.sh web
  bash scripts/logs.sh ml
  bash scripts/logs.sh minio

  # System-wide scan for errors and tracebacks:
  bash scripts/logs.sh --errors

  # Export compressed diagnostic bundle for troubleshooting:
  bash scripts/logs.sh --dump
  ```

### Tier 2: Real-Time Visual Log Inspection (Dozzle Web UI)
* Accessible via browser at `http://<GCP-VM-IP>:8888` (configurable via `DOZZLE_PORT` in `.env`).
* Consumes under 20MB of host RAM without external database dependencies.
* Connects read-only to `/var/run/docker.sock:ro` with split-screen multi-container views, live search, and regex filtering.
