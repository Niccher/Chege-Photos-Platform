# ML Chege Photos — Agent Guidelines & Ground Truth

## 1. Ground Truth Architecture
* **Service Role**: FastAPI computer vision and semantic embedding microservice.
* **Service Port**: Port 8000 inside container (mapped to host port `9051`).
* **Vector Engine**: Qdrant vector database on port 6333 (mapped to host port `9052`).
* **Relational Store**: MySQL 8.4 on port 3306 (mapped to host port `9306`).
* **Shared Network**: Attaches to `hosts-shared-network` created by the WebApp compose stack.

## 2. Multi-Repo Boundaries
* **Client Isolation**: The ML service is an internal microservice and is NEVER accessed directly by the Android mobile companion.
* **WebApp Media Hydration**: Photos are read from the mounted `uploads/` volume or fetched on demand from the WebApp via the `X-Webapp-Url` header.

## 3. Privacy & Sanitization
* Never document real usernames, `/home/` absolute paths, or real API keys.
* Use generic placeholders (`path/to/ML_Chege_Photos`, `shared_secret_token`).
