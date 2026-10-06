# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] - 2026-10-06

### Removed
- **Vendored Binary Python Wheels**: Completely purged 62 pre-compiled `.whl` package archives (~223 MB) from `ml/wheels/` and removed them from Git tracking index. This permanently eliminates GitHub's `>50 MB` push warnings (specifically for `opencv_python-5.0.0.93` at 70.36 MB) and dramatically reduces clone/push bandwidth.

### Changed
- **ML Dockerfile BuildKit Optimization**: Refactored `ml/Dockerfile` pip dependency installation to utilize BuildKit cache mounts (`--mount=type=cache,target=/root/.cache/pip`), significantly accelerating local and CI container rebuilds without inflating final image size.
- **Docker & Git Ignore Boundary Hardening**:
  - Added ignore patterns for `ml/wheels/`, `wheels/`, and `*.whl` to root `.gitignore` and `ml/.gitignore`.
  - Excluded `wheels/`, `*.whl`, `models/`, and `*.onnx` from `ml/.dockerignore` to prevent developer host weights and wheels from inflating Docker daemon context transfers (< 5 MB).
- **Public Landing Page Copy**: Updated `web/app/Views/public/home.php` to accurately describe automated model provisioning and Docker layer caching rather than deprecated offline vendored wheels.

### Fixed
- **FastAPI Documentation Relative Link**: Resolved broken relative path link in `docs/services/fastapi.md` targeting `app/main.py` by pointing to the correct monorepo path `../../ml/app/main.py`.

### Documentation
- **ML Microservice Local Development Handbook**: Expanded `docs/engineering/local-development.md` with step-by-step instructions for native Python 3.12 virtual environment (`venv`) setup, pip package installation, model downloading, and Uvicorn server execution.
- **ML Service Model Provisioning Docs**: Documented automated build-time model weight acquisition and runtime fallback mechanisms (`scripts/download_all_models.py` and `app/ml/loader.py`) in `docs/services/ml.md`.
- **Release Compatibility Matrix**: Updated `docs/engineering/release.md` to define version `v1.2.x` requirements, compatibility constraints, and architectural changes across the ecosystem.

## [1.1.0] - 2026-08-27

### Changed
- **Dockerization Optimizations**: Refactored `Dockerfile` to implement efficient multi-stage builds.
  - Leveraged BuildKit cache mounts (`--mount=type=cache`) for `apt` package manager and `composer` downloads.
  - Added dependency layering to separate Composer installation (`composer.json`, `composer.lock`) from application code changes, improving build speed.
  - Configured system package installation with `--no-install-recommends` to keep the image slim.
  - Cleaned up Composer binary after dependency installation to reduce attack surface and final image size.
  - Moved Apache configuration updates into organized blocks and automated PHP runtime limits configuration.
- **Docker Ignore Updates**: Updated `.dockerignore` to exclude `.gitignore` and `vendor/` directory while ensuring `composer.lock` is included to lock dependencies during Docker build stages.
