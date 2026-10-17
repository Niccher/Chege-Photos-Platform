<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>About — Self-Hosted Private Photo Platform<?= $this->endSection() ?>
<?= $this->section('description') ?>Learn about Chege Photos — a comprehensive self-hosted photo management platform featuring natural language semantic search (CLIP), facial clustering, YOLOv8 object tagging, OCR, and companion Android synchronization.<?= $this->endSection() ?>
<?= $this->section('nav-about') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="About Chege Photos — Self-Hosted Photo Cloud">
<meta property="og:description" content="A complete self-hosted photo platform with web management, Android mobile companion, local multimodal AI vision engine, and encrypted GCP hybrid cloud backups.">
<meta name="keywords" content="self-hosted photos, photo management, open source, docker, CLIP search, face recognition, YOLOv8, OCR, CodeIgniter, FastAPI">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>About Chege Photos</h1>
    <p>A complete self-hosted photo management platform — upload, organise, search with AI, analyse, and share your photos, all on your own private infrastructure.</p>
</div>

<section class="section">
    <div class="section-header">
        <h2>What is Chege Photos?</h2>
        <p>A modern, unified platform replacing commercial cloud photo subscriptions with a sovereign on-premise alternative equipped with cutting-edge multimodal AI.</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Core Web Application & Portal</h5>
                <p>Engineered on PHP 8.3 / CodeIgniter 4 with Shield authentication, the web portal provides:</p>
                <ul style="color: var(--text-muted); font-size: 0.9rem; line-height: 2;">
                    <li><strong>Upload & Processing</strong> — Chunked AJAX multi-file upload, 512 MB file limit, and EXIF extraction.</li>
                    <li><strong>Natural Language Search</strong> — Instant plain-text queries via local CLIP vector embeddings.</li>
                    <li><strong>Smart Rule Albums</strong> — Dynamic albums matching tags, camera models, dates, and people automatically.</li>
                    <li><strong>EXIF Analytics & Heatmap</strong> — Timeline upload charts, camera stats, and Leaflet GPS heatmaps.</li>
                    <li><strong>Memories Engine</strong> — Automated "On This Day" and seasonal flash-back feeds.</li>
                    <li><strong>Secure Sharing</strong> — Multi-user permissions and tokenized public album links with expiration.</li>
                    <li><strong>GCP Storage Hydration</strong> — Optional background sync to Google Cloud Storage for offsite disaster recovery.</li>
                    <li><strong>Soft-Delete Trash</strong> — Multi-tenant safety with 60-day auto-purge retention policy.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Local Multimodal AI Vision Suite</h5>
                <p>An on-premise FastAPI vision microservice executing zero-cloud inference:</p>
                <ul style="color: var(--text-muted); font-size: 0.9rem; line-height: 2;">
                    <li><strong>RetinaFace & ArcFace</strong> — High-accuracy face detection, 5-point alignment, and 512-d unit vectors.</li>
                    <li><strong>HDBSCAN Face Clustering</strong> — Unsupervised grouping of faces into named person identities.</li>
                    <li><strong>OpenAI CLIP Vector Search</strong> — Natural language cross-modal search indexed in Qdrant.</li>
                    <li><strong>YOLOv8 Object Detection</strong> — Automatic visual tagging across 80+ common scene & object categories.</li>
                    <li><strong>Tesseract OCR</strong> — In-photo text recognition for searchable signs, receipts, and documents.</li>
                    <li><strong>2-Stage Safety Gate</strong> — OpenNSFW & NudeNet verification protecting shared family feeds.</li>
                    <li><strong>Qdrant HNSW Index</strong> — Approximate Nearest Neighbor vector search in under 15 milliseconds.</li>
                    <li><strong>Person Management</strong> — Merge duplicate identities, re-scan images, and update names with ease.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>System Architecture</h2>
        <p>All backend services run containerized on an isolated Docker bridge network, communicating via strictly defined REST contracts.</p>
    </div>
    <div class="diagram-box">
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        Chege Photos Platform (Docker Network)                          │
│                                                                                        │
│  Browser / Client ──HTTP:80──▶ Web Application (PHP 8.3 Apache / CodeIgniter 4)        │
│                                  │                                                     │
│                                  ├── MySQL 8.4 (Port 3306)                             │
│                                  │   Users, albums, EXIF, tags, person metadata        │
│                                  │                                                     │
│                                  ├── MinIO Object Storage (Port 9000 S3 / 9001 Console)│
│                                  │   Primary S3 bucket (Multi-Cloud / AWS / GCP backup)│
│                                  │                                                     │
│                                  ├── Redis 7 (Port 6379)                               │
│                                  │   Session cache (MySQL fallback) & scan job queues  │
│                                  │                                                     │
│                                  ├── ML Microservice (FastAPI / Port 8000)             │
│                                  │   ├── InsightFace, CLIP, YOLOv8, OCR pipelines      │
│                                  │   └── Qdrant Vector Engine (Port 6333 / 6334)       │
│                                  │                                                     │
│                                  └── Dozzle Visual Log Viewer (Port 8888)              │
│                                      Two-tier observability & real-time streaming      │
│                                                                                        │
│  Android Client ────HTTP:80──▶ Web App Gateway (All ML features served via /api/v1)   │
└────────────────────────────────────────────────────────────────────────────────────────┘
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Production Tech Stack</h2>
    </div>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card-pub h-100">
                <h5>Web Management</h5>
                <div style="color: var(--text-muted); font-size: 0.88rem; line-height: 2;">
                    PHP 8.3 & Apache 2.4<br>
                    CodeIgniter 4 + Shield Auth<br>
                    MySQL 8.4 (Relational DB)<br>
                    Bootstrap 5.3 + Themes<br>
                    Leaflet GPS Heatmaps<br>
                    Standard Port: <strong>80</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub h-100">
                <h5>AI Vision Backend</h5>
                <div style="color: var(--text-muted); font-size: 0.88rem; line-height: 2;">
                    Python 3.12 / FastAPI<br>
                    InsightFace (ArcFace 512-d)<br>
                    OpenAI CLIP (Multimodal)<br>
                    Ultralytics YOLOv8<br>
                    Tesseract OCR Engine<br>
                    Standard Port: <strong>8000</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub h-100">
                <h5>Data & Object Storage</h5>
                <div style="color: var(--text-muted); font-size: 0.88rem; line-height: 2;">
                    MinIO S3 (Port <strong>9000</strong>/<strong>9001</strong>)<br>
                    Qdrant 1.13 Vector (Port <strong>6333</strong>)<br>
                    Redis 7 Cache (Port <strong>6379</strong>)<br>
                    Multi-Cloud AWS & GCP Sync<br>
                    AWS SigV4 Authentication
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub h-100">
                <h5>Android & Observability</h5>
                <div style="color: var(--text-muted); font-size: 0.88rem; line-height: 2;">
                    Kotlin 2.0+ / Jetpack Compose<br>
                    WorkManager Background Sync<br>
                    Room DB + Biometric Auth<br>
                    Dozzle Logs (Port <strong>8888</strong>)<br>
                    Two-Tier Container Diagnostics
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Multimodal Ingestion Pipeline</h2>
        <p>How an uploaded photo is securely cataloged and enriched with artificial intelligence.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">1</div>
                <h6 style="font-size:0.85rem;">Upload & EXIF</h6>
                <p style="font-size:0.82rem;">Image received via Web or Android. EXIF tags (camera, GPS, timestamp) are extracted and saved to MySQL.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">2</div>
                <h6 style="font-size:0.85rem;">Vision Extraction</h6>
                <p style="font-size:0.82rem;">FastAPI processes the image: InsightFace detects faces, YOLOv8 tags objects, and Tesseract extracts text.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">3</div>
                <h6 style="font-size:0.85rem;">Vector Embeddings</h6>
                <p style="font-size:0.82rem;">ArcFace generates 512-d face vectors and CLIP encodes the image. Embeddings are indexed into Qdrant collections.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">4</div>
                <h6 style="font-size:0.85rem;">Clustering & Search</h6>
                <p style="font-size:0.82rem;">HDBSCAN groups faces into named persons. Library becomes instantly searchable by concept, person, or keyword.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-sm" style="border-top: 1px solid var(--border-color);">
    <div class="text-center">
        <h3 style="font-weight: 700; color: var(--text-primary);">Ready to deploy your instance?</h3>
        <p style="color: var(--text-muted);">Consult the step-by-step <a href="<?= base_url('setup') ?>" class="auth-link">setup guide</a> or <a href="<?= url_to('register') ?>" class="auth-link">create an account</a> on an active server.</p>
    </div>
</section>

<?= $this->endSection() ?>
