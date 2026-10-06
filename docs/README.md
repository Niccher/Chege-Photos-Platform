# ML Chege Photos — Engineering Handbook

Welcome to the engineering documentation for ML Chege Photos. This documentation is intended for computer vision and backend software engineers maintaining, debugging, or extending the microservice.

If you only need to run or test the ML microservice, see the [Root README](../README.md).

---

## Documentation Navigation

| I want to… | Go here |
|---|---|
| **Run the service without coding** | [../README.md](../README.md) |
| **Understand system architecture & C4 containers** | [architecture/overview.md](architecture/overview.md) |
| **Inspect protocols, cURL proxies & sequences** | [architecture/communication.md](architecture/communication.md) |
| **Review vector schemas & Qdrant collections** | [architecture/data-and-storage.md](architecture/data-and-storage.md) |
| **Deploy to Docker or Railway** | [architecture/deployment.md](architecture/deployment.md) |
| **Inspect threat model & STRIDE security analysis** | [architecture/threat-model.md](architecture/threat-model.md) |
| **Review REST API contract** | [api/contract.md](api/contract.md) |
| **Work on FastAPI routes & lifespan loaders** | [services/fastapi.md](services/fastapi.md) |
| **Inspect ML models, ArcFace & CLIP pipelines** | [services/ml.md](services/ml.md) |
| **Set up a bare-metal dev machine** | [engineering/local-development.md](engineering/local-development.md) |
| **Add a feature safely & Definition of Done** | [engineering/making-changes.md](engineering/making-changes.md) |
| **Execute pytest test suites** | [engineering/testing.md](engineering/testing.md) |
| **Review CI/CD pipelines & automated tests** | [engineering/ci.md](engineering/ci.md) |
| **Review contributing guidelines & model policies** | [engineering/contributing.md](engineering/contributing.md) |
| **Troubleshoot compiler & PyTorch wheel issues** | [engineering/troubleshooting.md](engineering/troubleshooting.md) |
| **Review security & API key validation** | [engineering/security.md](engineering/security.md) |
| **Inspect ecosystem version matrix & compatibility** | [engineering/release.md](engineering/release.md) |
| **Execute container restart & recovery runbook** | [runbooks/restart.md](runbooks/restart.md) |
| **Execute Qdrant & metadata backup/restore runbook** | [runbooks/backup-and-restore.md](runbooks/backup-and-restore.md) |
| **Review ADR 0004: Decoupled ML Microservice** | [adr/0004-decoupled-microservice-ml.md](adr/0004-decoupled-microservice-ml.md) |
| **Review operator configuration & environment** | [user/configuration.md](user/configuration.md) |
| **Troubleshoot runtime & startup issues** | [user/troubleshooting.md](user/troubleshooting.md) |

---

### Sibling Ecosystem Repositories

| Component | Responsibility | Repository URL | Documentation |
|---|---|---|---|
| **Web App** | CodeIgniter 4 Web UI, Auth & Sync Gateway | [GitHub Repo](https://github.com/niccher/Chege-Photos-WebApp) | [docs/](https://github.com/niccher/Chege-Photos-WebApp/tree/main/docs) |
| **ML Microservice** | FastAPI, InsightFace, YOLO, CLIP & Qdrant | [GitHub Repo](https://github.com/niccher/Chege-Photos-ML) | [docs/](https://github.com/niccher/Chege-Photos-ML/tree/main/docs) |
| **Android App** | Jetpack Compose Native Mobile Client | [GitHub Repo](https://github.com/niccher/Chege-Photos-Android) | [docs/](https://github.com/niccher/Chege-Photos-Android/tree/main/docs) |
