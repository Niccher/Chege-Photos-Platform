# Testing & Quality Verification

How to execute syntax linters, PHPUnit test suites, Python Pytest suites, and documentation quality checks across Chege Photos Platform.

---

## 1. Web Application Testing (`web/`)

### PHP Syntax Verification
Verify the entire web codebase for syntax errors:
```bash
find web/app/ -name "*.php" -exec php -l {} \;
```

### Running PHPUnit Tests
Execute automated PHPUnit test suites:
```bash
# Inside web container:
docker compose exec web vendor/bin/phpunit

# Or locally:
cd web && vendor/bin/phpunit
```

---

## 2. Machine Learning Service Testing (`ml/`)

### Running Pytest
Execute Python test suites:
```bash
# Inside ML container:
docker compose exec ml pytest

# Or locally with virtualenv active:
cd ml && pytest
```

---

## 3. Documentation Quality Linting

Run the automated documentation quality linter:
```bash
python3 scripts/lint-docs.py .
```
Validates:
- `README.md` concise operator line count ($\le 150$ lines).
- Zero broken relative Markdown links.
- Valid Mermaid syntax blocks.
- No unredacted credentials or private keys.
