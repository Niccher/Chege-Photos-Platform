# Bare-Metal Local Development

How to run and debug the CodeIgniter 4 WebApp natively on your workstation without Docker.

---

## 1. System Dependencies

Ensure the following tools and extensions are installed on your workstation:
- **PHP**: 8.2 or 8.3 with extensions:
  - `ext-intl`
  - `ext-mysqli`
  - `ext-pdo_mysql`
  - `ext-gd` (with FreeType and libjpeg)
  - `ext-zip`
  - `ext-exif`
  - `ext-curl`
  - `ext-mbstring`
- **Composer**: Version 2.6+
- **MySQL**: 8.0+ or MariaDB 10.6+

---

## 2. Setup Steps

```bash
# 1. Install PHP dependencies
composer install

# 2. Configure environment
cp .env.example .env

# 3. Edit .env with local database settings
#    database.default.hostname = localhost
#    database.default.database = db_chege_photos
#    database.default.username = your_user
#    database.default.password = your_password

# 4. Generate encryption key
php spark key:generate

# 5. Run migrations
php spark migrate --all

# 6. Seed default administrator
php spark db:seed SystemDefaultSeeder

# 7. Start local server
php spark serve --port 8080
```

Access the application at [http://localhost:8080](http://localhost:8080).

---

## 3. ML Microservice (FastAPI & Vision Models)

How to run and debug the Python FastAPI microservice natively on your workstation:

### System Dependencies
* **Python**: 3.12+
* **Tesseract OCR**: `sudo apt install tesseract-ocr tesseract-ocr-eng` (optional, for OCR pipelines)
* **Graphics Libraries**: `sudo apt install libgl1 libglib2.0-0` (for OpenCV runtime)

### Setup & Execution
```bash
# 1. Navigate to ML service directory
cd ml

# 2. Create and activate a Python virtual environment
python3 -m venv .venv
source .venv/bin/activate

# 3. Install dependencies from PyPI
pip install -r requirements.txt

# 4. Pre-download neural network weights (InsightFace, YOLOv8, OpenNSFW, CLIP)
python scripts/download_all_models.py

# 5. Configure environment variables
cp .env.example .env

# 6. Start FastAPI development server with auto-reload
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

Access the interactive OpenAPI Swagger UI at [http://localhost:8000/docs](http://localhost:8000/docs).

