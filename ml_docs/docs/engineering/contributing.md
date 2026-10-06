# Contributing Guide & Workflow

Engineering practices, model check-in policies, coding standards, and Definition of Done for ML Chege Photos.

---

## 1. Branching & PR Workflow

1. Create a feature branch from `main`:
   ```bash
   git checkout -b feat/support-vit-l-clip-model
   ```
2. Follow standard commit conventions:
   - `feat(ml): add ViT-L/14 model loader option`
   - `fix(cluster): resolve zero-division edge case in centroid normalization`
   - `docs(api): document new face similarity thresholds in openapi.yaml`
3. Submit a Pull Request targeting `main`.

---

## 2. Model Check-In Policies

> [!CAUTION]
> **Never commit binary model weights to git!** Large `.onnx`, `.pt`, `.bin`, and `.safetensors` files must never be tracked in the git repository.

* All model weights must be downloaded on demand via `app.ml.loader` during container build or lifespan startup.
* Keep weights cached in the container cache volume (`~/.cache/insightface/`, `~/.cache/clip/`).

---

## 3. Definition of Done (Contract Changes)

Before merging PRs that modify endpoints or vector embeddings:

- [ ] **Implementation**: Code adheres to PEP 8 / Ruff standards with type hints.
- [ ] **Tests**: Unit tests covering new routers or models added to `tests/`.
- [ ] **OpenAPI Spec**: Updated `docs/api/contract.md` and `docs/api/openapi.yaml`.
- [ ] **Environment**: New parameters added to `.env.example` and documented in `docs/user/configuration.md`.
- [ ] **Documentation Linter**: `python3 scripts/lint-docs.py .` passes with 0 errors.
