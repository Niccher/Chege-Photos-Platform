# Chege Photos Platform — Engineering Handbook

Welcome to the engineering documentation for Chege Photos Platform. This monorepo houses the complete backend infrastructure: CodeIgniter 4 Web application, FastAPI computer vision microservice, Qdrant vector database, and MySQL relational store.

If you only need to deploy, configure, or run the platform with Docker Compose, see the [Root README](../README.md).

---

## Documentation Navigation

| I want to… | Go here |
|---|---|
| **Run the full stack with one command** | [../README.md](../README.md) |
| **Understand system architecture & C4 containers** | [architecture/overview.md](architecture/overview.md) |
| **Inspect protocols, cURL proxies & sequences** | [architecture/communication.md](architecture/communication.md) |
| **Review database schemas, vectors & storage hierarchy** | [architecture/data-and-storage.md](architecture/data-and-storage.md) |
| **Review deployment & container topology** | [architecture/deployment.md](architecture/deployment.md) |
| **Inspect threat model & STRIDE security analysis** | [architecture/threat-model.md](architecture/threat-model.md) |
| **Review Mobile Client REST API contract** | [api/contract.md](api/contract.md) |
| **Inspect ML microservice OpenAPI specification** | [api/ml-openapi.yaml](api/ml-openapi.yaml) |
| **Work on CodeIgniter 4 controllers & Spark CLI** | [services/codeigniter.md](services/codeigniter.md) |
| **Work on FastAPI microservice & routing** | [services/fastapi.md](services/fastapi.md) |
| **Inspect ML models (InsightFace, YOLOv8, CLIP, NSFW)** | [services/ml.md](services/ml.md) |
| **Set up local development (PHP & Python)** | [engineering/local-development.md](engineering/local-development.md) |
| **Add a feature safely & Definition of Done** | [engineering/making-changes.md](engineering/making-changes.md) |
| **Manage database migrations & query scoping** | [engineering/database.md](engineering/database.md) |
| **Execute test suites (PHPUnit & Pytest)** | [engineering/testing.md](engineering/testing.md) |
| **Review CI/CD pipelines & automated tests** | [engineering/ci.md](engineering/ci.md) |
| **Review contributing guidelines & PR workflow** | [engineering/contributing.md](engineering/contributing.md) |
| **Troubleshoot compiler & dev environment issues** | [engineering/troubleshooting.md](engineering/troubleshooting.md) |
| **Review security & token encryption architecture** | [engineering/security.md](engineering/security.md) |
| **Inspect ecosystem version matrix & compatibility** | [engineering/release.md](engineering/release.md) |
| **Execute container restart & recovery runbook** | [runbooks/restart.md](runbooks/restart.md) |
| **Execute database & media backup/restore runbook** | [runbooks/backup-and-restore.md](runbooks/backup-and-restore.md) |
| **Review ADR 0001: Hybrid QR & Numeric Pairing** | [adr/0001-hybrid-qr-numeric-pairing.md](adr/0001-hybrid-qr-numeric-pairing.md) |
| **Review ADR 0002: Unified Feed Aggregation** | [adr/0002-unified-feed-aggregation.md](adr/0002-unified-feed-aggregation.md) |
| **Review ADR 0004: Decoupled ML Microservice** | [adr/0004-decoupled-microservice-ml.md](adr/0004-decoupled-microservice-ml.md) |
| **Review operator configuration & environment** | [user/configuration.md](user/configuration.md) |
| **Troubleshoot runtime & startup issues** | [user/troubleshooting.md](user/troubleshooting.md) |

---

### Project Repositories (Two-Repo Arrangement)

| Component | Responsibility | Repository URL | Documentation |
|---|---|---|---|
| **Platform (Backend)** | Web UI, Mobile REST Gateway, ML Microservice & DBs | [Chege-Photos-Platform](https://github.com/Niccher/Chege-Photos-Platform) | [docs/](https://github.com/Niccher/Chege-Photos-Platform/tree/main/docs) |
| **Client (Mobile)** | Native Android Companion Client (Compose/Room) | [Chege-Photos-App](https://github.com/Niccher/Chege-Photos-App) | [docs/](https://github.com/Niccher/Chege-Photos-App/tree/main/docs) |
