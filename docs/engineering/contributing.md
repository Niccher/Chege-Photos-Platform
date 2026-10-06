# Contributing Guide & Workflow

Engineering practices, branching conventions, code standards, and the Definition of Done for Chege Photos WebApp.

---

## 1. Branching & PR Workflow

1. Create a descriptive feature branch from `main`:
   ```bash
   git checkout -b feat/support-raw-heic-uploads
   ```
2. Make atomic commits with conventional commit messages:
   - `feat(photos): add HEIC image conversion filter`
   - `fix(auth): correct token expiration check in group filter`
   - `docs(api): document new photo batch endpoints in openapi.yaml`
3. Push to your fork and submit a Pull Request targeting `main`.

---

## 2. Definition of Done (Contract Changes)

Before any PR modifying database schemas or API endpoints can be merged:

- [ ] **Implementation**: Code adheres to CodeIgniter 4 standards and passes PHP linting.
- [ ] **Database Migration**: Schema modifications are tracked in a versioned migration (`app/Database/Migrations/`).
- [ ] **Tests**: New routes include corresponding PHPUnit test coverage in `tests/`.
- [ ] **Documentation**: Updated `docs/api/contract.md` and `docs/api/openapi.yaml`.
- [ ] **Environment**: Any new environment variable is added to `.env.example` with clear defaults and documented in `docs/user/configuration.md`.
- [ ] **Linter Verification**: `python3 scripts/lint-docs.py .` passes with 0 errors.
