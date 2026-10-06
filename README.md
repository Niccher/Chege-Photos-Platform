# Chege Photos Platform

Unified backend platform for self-hosted personal photo and video management featuring automated face clustering (InsightFace ArcFace), object tagging (YOLOv8), semantic text-to-image search (CLIP), Redis caching and durable queueing, Qdrant vector indexing, Google Cloud Storage hydration, and companion Android synchronization.

**Stack**: PHP 8.3 / CodeIgniter 4, Python 3.12 / FastAPI, Redis 7, Qdrant Vector DB, MySQL 8.4, Docker Compose.  
**If you only need to run the system, this page is enough.** Software engineers: [docs/README.md](docs/README.md).

---

## What “Running” Looks Like (Standard Production Ports)

| Service | URL / Endpoint | Standard Port | Purpose / Default Credential |
|---|---|---|---|
| **Web Application** | [http://localhost](http://localhost) | `80` | Web UI & Admin console (`admin@example.com` / `admin_password`) |
| **ML Microservice** | [http://localhost:8000/docs](http://localhost:8000/docs) | `8000` | FastAPI vision API, Swagger `/docs` |
| **Redis Cache & Queue** | `localhost:6379` | `6379` | In-memory cache & durable background job broker |
| **Qdrant Vector DB**| [http://localhost:6333/dashboard](http://localhost:6333/dashboard) | `6333` / `6334` | Vector search engine (HTTP: 6333, gRPC: 6334) |
| **MySQL 8.4** | `localhost:3306` | `3306` | Relational database (`db_chege_photos` / `root_password`) |
| **Web Health Check**| `GET http://localhost/api/v1/health` | `80` | WebApp JSON status check |
| **ML Health Check** | `GET http://localhost:8000/health` | `8000` | ML service JSON status check |
| **Android Client**  | Companion APK | — | Connects to WebApp at `http://10.0.2.2` (emulator) or host LAN IP |

---

## Prerequisites

### Docker Compose (Recommended)
* Docker Engine 24.0+ & Docker Compose v2 (or Docker Desktop)
* Git
* 4 GB available RAM (for combined PHP runtime, Redis, and ML PyTorch/ONNX models)

---

## Setup and Run

### Option A — One-Click Automated Deployment (Recommended)

From the repository root:

```bash
bash scripts/deploy.sh
```

> [!NOTE]
> `deploy.sh` automatically performs pre-flight system diagnostics, detects your LAN IP, initializes `.env`, handles container lifecycle, probes service health sequentially, runs database migrations, and enforces file permissions.

### Option B — Manual Docker Compose

```bash
# 1. Copy environment configuration
cp .env.example .env

# 2. Launch full platform stack in background
docker compose up --build -d

# 3. Verify running services
docker compose ps

# 4. Open browser at http://localhost and log in with default credentials.

# 5. To stop containers when finished:
docker compose stop
```

---

## Configuration

| Variable | Default | Purpose |
|---|---|---|
| `WEB_PORT` | `80` | Host published port for Web application. |
| `ML_PORT` | `8000` | Host published port for FastAPI ML microservice. |
| `REDIS_PORT` | `6379` | Host published port for Redis in-memory broker. |
| `DB_PORT` | `3306` | Host published port for MySQL 8.4 database. |
| `QDRANT_HTTP_PORT` | `6333` | Host published port for Qdrant HTTP REST API. |
| `DB_ROOT_PASSWORD` | `root_password` | MySQL root database password. |
| `ML_API_KEY` | `my_super_secret_shared_token_key_123!` | Shared secret header (`X-API-KEY`) between Web and ML. |
| `GCP_BUCKET` | *None* | Google Cloud Storage bucket for persistent offsite media backup. |

Full list of operational environment variables: [docs/user/configuration.md](docs/user/configuration.md).

---

## Software Engineers

For architecture, database schemas, API specs, and development workflows, see the **[Engineering Handbook](docs/README.md)**:

* [Architecture Overview & C4 Containers](docs/architecture/overview.md)
* [Protocols & Inter-Service Communication](docs/architecture/communication.md)
* [Database Schemas & Storage Hierarchy](docs/architecture/data-and-storage.md)
* [Threat Model & Security Hardening](docs/architecture/threat-model.md)
* [Mobile REST API Contract](docs/api/contract.md)
* [ML Microservice OpenAPI Contract](docs/api/ml-openapi.yaml)
* [CodeIgniter 4 Service Details](docs/services/codeigniter.md)
* [FastAPI Service Details](docs/services/fastapi.md)
* [ML Models & Vision Pipelines](docs/services/ml.md)
* [Local Development Guide](docs/engineering/local-development.md)
* [Making Changes & Definition of Done](docs/engineering/making-changes.md)
* [Testing Guide](docs/engineering/testing.md)

---

## Two-Repository Project Architecture

Chege Photos is organized into a clean **two-repository arrangement**:

```
[ Android Companion App ]  (Repository: Chege-Photos-App)
         │
         │ (HTTP / Bearer Token - standard port 80)
         ▼
┌────────────────────────────────────────────────────────┐
│ Chege Photos Platform    (Repository: Chege-Photos-Platform)
│                                                        │
│  [ Web / REST Gateway ] (Port 80) ────► [ MySQL 8.4 ]  │
│         │         │                     ▲ (Port 3306)  │
│         │         ▼                     │              │
│         │    [ Redis Agent ] (Port 6379)│              │
│         │         ▲                     │              │
│         ▼         │                     │              │
│  [ ML Microservice ] (Port 8000) ───────┤              │
│         │                                              │
│         ▼                                              │
│  [ Qdrant Vector DB ] (Port 6333)                      │
└────────────────────────────────────────────────────────┘
```

### Sibling Ecosystem Repositories

| Component | Responsibility | Repository URL | Documentation |
|---|---|---|---|
| **Platform (Backend)** | Web UI, Mobile REST Gateway, ML Microservice & DBs | [Chege-Photos-Platform](https://github.com/Niccher/Chege-Photos-Platform) | [docs/](https://github.com/Niccher/Chege-Photos-Platform/tree/main/docs) |
| **Client (Mobile)** | Native Android Companion Client (Compose/Room) | [Chege-Photos-App](https://github.com/Niccher/Chege-Photos-App) | [docs/](https://github.com/Niccher/Chege-Photos-App/tree/main/docs) |

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
