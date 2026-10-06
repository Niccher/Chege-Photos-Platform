# Continuous Integration & Automation

Continuous integration pipelines, automated tests, and documentation verification for ML Chege Photos.

---

## 1. CI Workflow Overview

ML Chege Photos utilizes GitHub Actions for automated validation:

| Workflow | File Path | Trigger | Responsibilities |
|---|---|---|---|
| **Docs Quality Verification** | `.github/workflows/docs-verify.yml` | Push & PR to `main`/`master` touching `README.md` or `docs/**` | Runs `scripts/lint-docs.py` (checks line limits, leaked instructions, broken markdown links) and verifies external links via `markdown-link-check`. |
| **Pytest Suite** | `.github/workflows/python.yml` | Push & PR to `main`/`master` | Installs dependencies, sets up mock vector database, and executes pytest unit and integration tests across Python 3.12. |

---

## 2. Running CI Checks Locally

### Documentation Linter
```bash
python3 scripts/lint-docs.py .
```

### Pytest Suite
```bash
# Run pytest with coverage report
pytest -v --cov=app tests/
```

### Flake8 & Ruff Linting
```bash
ruff check app/
```
