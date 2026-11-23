# Contributing to Chege Photos Platform

Thank you for contributing to **Chege Photos Platform**!

## Architecture & Boundaries
- **Two-Repo Isolation:** Backend services (`nginx`, `web` PHP 8.4.28 FPM, `ml` FastAPI, `mysql`, `redis`, `qdrant`, `minio`, `dozzle`) live in `Chege-Photos-Platform`. The native Kotlin Android client lives in `Chege-Photos-App` and communicates **exclusively** with the WebApp REST API (`/api/v1/` on port `80`).
- **Stateless Compute:** Never write persistent user media or model weights to host container volumes. Media belongs in `minio://chege-photos/`, AI weights belong in `minio://chege-models/`, and cold backups belong in `minio://chege-backups/`.
- **Privacy & Sanitization:** Never commit `.env`, real credentials, personal emails, or machine-specific filesystem paths. Use `admin@example.com` and `path/to/project` in examples.

## Development Workflow
1. Fork the repository and create a feature branch (`git checkout -b feat/your-feature`).
2. Copy `.env.example` to `.env` and run `bash scripts/deploy.sh`.
3. Make your changes and run syntax/lint checks:
   - PHP: `php -l web/app/...`
   - Python: `python3 -m py_compile ml/app/...`
   - Docs: `python3 scripts/lint-docs.py`
4. Update relevant pages under `docs/` and keep `README.md` focused on setup and running.
5. Open a Pull Request describing what changed and how you verified it.
