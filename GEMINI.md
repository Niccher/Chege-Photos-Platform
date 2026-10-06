# Chege Photos Platform — Agent Guidelines & Ground Truth

## 1. Ground Truth Architecture
* **Repository Role**: Unified backend platform monorepo containing WebApp, ML microservice, and Docker orchestration.
* **Component Directory Layout**:
  - `web/`: CodeIgniter 4 PHP 8.3 Web UI, Admin portal, and Mobile REST API gateway. Exposes standard HTTP port `80` (Apache).
  - `ml/`: FastAPI computer vision and semantic embedding microservice. Exposes standard port `8000`.
  - `docs/`: Unified platform architecture, handbooks, and runbooks.
* **Standard Ports**:
  - WebApp: Port `80` (internal `80`)
  - MySQL: Port `3306` (internal `3306`, service name `mysql`)
  - ML Microservice: Port `8000` (internal `8000`, service name `ml`)
  - Redis: Port `6379` (internal `6379`, service name `redis`)
  - Qdrant Vector Engine: Port `6333` HTTP / `6334` gRPC (service name `qdrant`)
* **Shared Network**: Internal compose network `chege-network`.
* **Auxiliary Tools Policy**: `phpMyAdmin` and other third-party DB inspection tools are strictly NOT part of this stack.

## 2. Multi-Repo Integration Boundaries (Two-Repo Arrangement)
* **Two-Repo Architecture**:
  1. `Chege-Photos-App`: Native Android mobile client (Kotlin / Jetpack Compose).
  2. `Chege-Photos-Platform`: Unified backend platform (this repository).
* **Client Isolation**: The Android mobile client communicates **exclusively** with the WebApp REST API (`/api/v1/` on standard port `80` via `http://10.0.2.2` or host LAN IP). Android has ZERO direct network connections to ML, Redis, or Qdrant.
* **Media Sharing**: Photos are stored under `web/public/uploads` and mounted read-only into the ML container at `/app/uploads:ro`.

## 3. Privacy & Sanitization
* Never commit or document real personal emails, personal usernames, or absolute user home paths.
* Always use `admin@example.com` and generic placeholders (`path/to/project`, `shared_secret_token`).
