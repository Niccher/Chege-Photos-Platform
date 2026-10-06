# Chege Photos Platform — Agent Guidelines & Ground Truth

## 1. Ground Truth Architecture
* **Repository Role**: Unified backend platform monorepo containing WebApp, ML microservice, and Docker orchestration.
* **Component Directory Layout**:
  - `web/`: CodeIgniter 4 PHP 8.3 Web UI, Admin portal, and Mobile REST API gateway. Exposes port `9005` (Apache).
  - `ml/`: FastAPI computer vision and semantic embedding microservice. Exposes port `9051` (internal `8000`).
  - `docs/`: Unified platform architecture, handbooks, and runbooks.
* **Database**: MySQL 8.4 on host port `9306` (service name `mysql`, internal port `3306`).
* **Vector Engine**: Qdrant on host port `9052` (internal `6333`).
* **Shared Network**: Internal compose network `chege-network`.
* **Auxiliary Tools Policy**: `phpMyAdmin` and other third-party DB inspection tools are strictly NOT part of this stack.

## 2. Multi-Repo Integration Boundaries (Two-Repo Arrangement)
* **Two-Repo Architecture**:
  1. `Chege-Photos-App`: Native Android mobile client (Kotlin / Jetpack Compose).
  2. `Chege-Photos-Platform`: Unified backend platform (this repository).
* **Client Isolation**: The Android mobile client communicates **exclusively** with the WebApp REST API (`/api/v1/` on port `9005`). Android has ZERO direct network connections to the ML service or Qdrant.
* **Media Sharing**: Photos are stored under `web/public/uploads` and mounted read-only into the ML container at `/app/uploads:ro`.

## 3. Privacy & Sanitization
* Never commit or document real personal emails, personal usernames, or absolute user home paths.
* Always use `admin@example.com` and generic placeholders (`path/to/project`, `shared_secret_token`).
