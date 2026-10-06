# Chege Photos WebApp — Engineering Handbook

Welcome to the engineering documentation for Chege Photos WebApp. This documentation is intended for software engineers maintaining, debugging, or extending the application.

If you only need to deploy, configure, or run the web application, see the [Root README](../README.md).

---

## Documentation Navigation

| I want to… | Go here |
|---|---|
| **Run the system without coding** | [../README.md](../README.md) |
| **Understand system architecture & C4 containers** | [architecture/overview.md](architecture/overview.md) |
| **Inspect protocols, cURL proxies & sequences** | [architecture/communication.md](architecture/communication.md) |
| **Review database schemas, vectors & GCP rehydration** | [architecture/data-and-storage.md](architecture/data-and-storage.md) |
| **Deploy to Railway or Docker Compose** | [architecture/deployment.md](architecture/deployment.md) |
| **Inspect threat model & STRIDE security analysis** | [architecture/threat-model.md](architecture/threat-model.md) |
| **Review REST API contract** | [api/contract.md](api/contract.md) |
| **Work on CodeIgniter 4 controllers & Spark CLI** | [services/codeigniter.md](services/codeigniter.md) |
| **Set up a bare-metal dev machine** | [engineering/local-development.md](engineering/local-development.md) |
| **Add a feature safely & Definition of Done** | [engineering/making-changes.md](engineering/making-changes.md) |
| **Manage database migrations & query scoping** | [engineering/database.md](engineering/database.md) |
| **Execute PHPUnit tests & linting** | [engineering/testing.md](engineering/testing.md) |
| **Review CI/CD pipelines & automated tests** | [engineering/ci.md](engineering/ci.md) |
| **Review contributing guidelines & PR workflow** | [engineering/contributing.md](engineering/contributing.md) |
| **Troubleshoot compiler & dev environment issues** | [engineering/troubleshooting.md](engineering/troubleshooting.md) |
| **Review security & token encryption architecture** | [engineering/security.md](engineering/security.md) |
| **Inspect ecosystem version matrix & compatibility** | [engineering/release.md](engineering/release.md) |
| **Execute container restart & recovery runbook** | [runbooks/restart.md](runbooks/restart.md) |
| **Execute database & media backup/restore runbook** | [runbooks/backup-and-restore.md](runbooks/backup-and-restore.md) |
| **Review ADR 0001: Hybrid QR & Numeric Pairing** | [adr/0001-hybrid-qr-numeric-pairing.md](adr/0001-hybrid-qr-numeric-pairing.md) |
| **Review ADR 0002: Unified Feed Aggregation** | [adr/0002-unified-feed-aggregation.md](adr/0002-unified-feed-aggregation.md) |
| **Review operator configuration & environment** | [user/configuration.md](user/configuration.md) |
| **Troubleshoot runtime & startup issues** | [user/troubleshooting.md](user/troubleshooting.md) |

---

### Sibling Ecosystem Repositories

| Component | Responsibility | Repository URL | Documentation |
|---|---|---|---|
| **Web App** | CodeIgniter 4 Web UI, Auth & Sync Gateway | [GitHub Repo](https://github.com/niccher/Chege-Photos-WebApp) | [docs/](https://github.com/niccher/Chege-Photos-WebApp/tree/main/docs) |
| **ML Microservice** | FastAPI, InsightFace, YOLO, CLIP & Qdrant | [GitHub Repo](https://github.com/niccher/Chege-Photos-ML) | [docs/](https://github.com/niccher/Chege-Photos-ML/tree/main/docs) |
| **Android App** | Jetpack Compose Native Mobile Client | [GitHub Repo](https://github.com/niccher/Chege-Photos-Android) | [docs/](https://github.com/niccher/Chege-Photos-Android/tree/main/docs) |
