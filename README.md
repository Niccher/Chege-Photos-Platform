# Chege Photos Platform

Unified backend platform for self-hosted personal photo and video management featuring automated face clustering (InsightFace ArcFace), object tagging (YOLOv8), semantic text-to-image search (CLIP), Qdrant vector indexing, Google Cloud Storage hydration, and companion Android synchronization.

**Stack**: PHP 8.3 / CodeIgniter 4, Python 3.12 / FastAPI, Qdrant Vector DB, MySQL 8.4, Docker Compose.  
**If you only need to run the system, this page is enough.** Software engineers: [docs/README.md](docs/README.md).

---

## What “Running” Looks Like

| Service | URL / Endpoint | Port (Host) | Purpose / Default Credential |
|---|---|---|---|
| **Web Application** | [http://localhost:9005](http://localhost:9005) | `9005` | Web UI & Admin console (`admin@example.com` / `admin_password`) |
| **ML Microservice** | [http://localhost:9051/docs](http://localhost:9051/docs) | `9051` | FastAPI vision API, Swagger `/docs` |
| **Qdrant Vector DB**| [http://localhost:9052/dashboard](http://localhost:9052/dashboard) | `9052` / `9053` | Vector search engine (HTTP: 9052, gRPC: 9053) |
| **MySQL 8.4** | `localhost:9306` | `9306` | Relational database (`db_chege_photos` / `root_password`) |
| **Web Health Check**| `GET http://localhost:9005/api/v1/health` | `9005` | WebApp JSON status check |
| **ML Health Check** | `GET http://localhost:9051/health` | `9051` | ML service JSON status check |
| **Android Client**  | Companion APK | — | Connects to WebApp at `http://10.0.2.2:9005` (emulator) or LAN IP |

---

## Prerequisites

### Docker Compose (Recommended)
* Docker Engine 24.0+ & Docker Compose v2 (or Docker Desktop)
* Git
* 4 GB available RAM (for combined PHP runtime and ML PyTorch/ONNX models)

---

## Setup and Run

From a fresh terminal:

```bash
# 1. Clone repository
git clone https://github.com/Niccher/Chege-Photos-Platform.git
cd "Chege Photos Platform"

# 2. Copy environment configuration
cp .env.example .env

# 3. Launch full platform stack in background
docker compose up --build -d

# 4. Verify running services
docker compose ps

# 5. Open browser at http://localhost:9005 and log in with default credentials.

# 6. To stop containers when finished:
docker compose stop
```

> [!NOTE]
> On first boot, the platform automatically initializes MySQL, runs database migrations, seeds the default superuser, preloads InsightFace/YOLO ML model weights, and binds the shared internal network `chege-network`.

---

## Configuration

| Variable | Default | Purpose |
|---|---|---|
| `WEB_PORT` | `9005` | Host published port for Web application. |
| `ML_PORT` | `9051` | Host published port for FastAPI ML microservice. |
| `DB_PORT` | `9306` | Host published port for MySQL 8.4 database. |
| `QDRANT_HTTP_PORT` | `9052` | Host published port for Qdrant HTTP REST API. |
| `DB_ROOT_PASSWORD` | `root_password` | MySQL root database password. |
| `ML_API_KEY` | `my_super_secret_shared_token_key_123!` | Shared secret header (`X-API-KEY`) between Web and ML. |
| `GCP_BUCKET` | *None* | Google Cloud Storage bucket for persistent offsite media backup. |

Full list of operational environment variables: [docs/user/configuration.md](docs/user/configuration.md).

---

## Troubleshooting

* **Port 9005 already in use**: Change `WEB_PORT` in `.env` or `docker-compose.yml`.
* **MySQL not ready yet**: Initial database initialization takes ~15–30 seconds. Run `docker compose logs -f mysql`.
* **ML model download timeout**: First-time build downloads model weights. Models are cached in persistent Docker volume `insightface_models`.
* **Android emulator cannot connect**: Use `http://10.0.2.2:9005` as the server URL instead of `localhost`.

Detailed symptoms and step-by-step fixes: [docs/user/troubleshooting.md](docs/user/troubleshooting.md).

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
         │ (HTTPS / Bearer Token - port 9005)
         ▼
┌────────────────────────────────────────────────────────┐
│ Chege Photos Platform    (Repository: Chege-Photos-Platform)
│                                                        │
│  [ Web / REST Gateway ] (Port 9005) ───► [ MySQL 8.4 ] │
│              │                              ▲ (Port 9306)
│              ▼                              │
│  [ ML Microservice ] (Port 9051) ───────────┤
│              │                                         │
│              ▼                                         │
│  [ Qdrant Vector DB ] (Port 9052)                      │
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
