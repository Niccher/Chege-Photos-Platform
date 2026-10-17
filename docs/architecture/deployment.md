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
- **MinIO Object Storage**: S3-compatible primary photo/thumbnail bucket (Port `9000` S3 API / `9001` Web Console).
- **MinIO Initialization**: Automatic bucket creation (`chege-photos`) and thumbnail download policy via `minio/mc`.
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

Inspect cron output:
```bash
docker compose exec chege-photos cat /var/log/cron-run.log
```
