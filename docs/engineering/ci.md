# Continuous Integration & Automation

Continuous integration pipelines, automated tests, and documentation verification for Chege Photos WebApp.

---

## 1. CI Workflow Overview

Chege Photos WebApp utilizes GitHub Actions for continuous integration:

| Workflow | File Path | Trigger | Responsibilities |
|---|---|---|---|
| **Docs Quality Verification** | `.github/workflows/docs-verify.yml` | Push & PR to `main`/`master` touching `README.md` or `docs/**` | Runs `scripts/lint-docs.py` (checks line counts, leaked instructions, broken markdown links) and verifies external links via `markdown-link-check`. |
| **PHPUnit & Lint Suite** | `.github/workflows/php.yml` | Push & PR to `main`/`master` | Executes PHP linting, static analysis, and PHPUnit unit tests across PHP 8.4 environments. |

---

## 2. Running CI Checks Locally

Engineers can execute the exact same checks locally prior to opening a pull request:

### Documentation Quality Linter
```bash
# Execute local docs linter
python3 scripts/lint-docs.py .
```

### PHP Unit Tests
```bash
# Run PHPUnit test suite inside container
docker compose exec webapp ./vendor/bin/phpunit
```

### Static Analysis & Syntax Check
```bash
# Validate PHP syntax across app directory
docker compose exec webapp find app/ -name "*.php" -exec php -l {} \;
```
