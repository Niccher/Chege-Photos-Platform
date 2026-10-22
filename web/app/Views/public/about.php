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
                <p>Engineered on PHP 8.4 / CodeIgniter 4 with Shield authentication, the web portal provides:</p>
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
│  Browser / Client ──HTTP:80──▶ Web Application (PHP 8.4 Apache / CodeIgniter 4)        │
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
        <h2>Production Container Cluster (7 Services)</h2>
        <p>Every component runs containerized on an isolated Docker network with health checks, strict resource limits, and automated volume management.</p>
    </div>
    <div class="table-responsive">
        <table class="table table-pub align-middle">
            <thead>
                <tr>
                    <th>Container Service</th>
                    <th>Runtime / Base</th>
                    <th>Standard Port</th>
                    <th>Internal Network Alias</th>
                    <th>Role & Responsibility</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>chege-photos-web</strong></td>
                    <td><code>php:8.4.27-apache-bookworm</code></td>
                    <td><code>80</code></td>
                    <td><code>web</code></td>
                    <td>CodeIgniter 4 WebApp, Shield authentication, REST API gateway, EXIF extraction, and cron task scheduler.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-ml</strong></td>
                    <td><code>python:3.12-slim</code></td>
                    <td><code>8000</code></td>
                    <td><code>ml</code>, <code>ml-chege-photos</code></td>
                    <td>FastAPI neural engine running RetinaFace, ArcFace, HDBSCAN, OpenAI CLIP, YOLOv8, and Tesseract OCR.</td>
                </tr>
                <tr>
                    <td><strong>chege-mysql</strong></td>
                    <td><code>mysql:8.4</code></td>
                    <td><code>3306</code></td>
                    <td><code>mysql</code></td>
                    <td>ACID relational database for users, albums, photos, tags, EXIF parameters, and operational audit logs.</td>
                </tr>
                <tr>
                    <td><strong>chege-minio</strong></td>
                    <td><code>cgr.dev/chainguard/minio</code></td>
                    <td><code>9000</code> / <code>9001</code></td>
                    <td><code>minio</code></td>
                    <td>S3-compatible distributed object storage decoupling binary photo assets from application host containers.</td>
                </tr>
                <tr>
                    <td><strong>chege-minio-init</strong></td>
                    <td><code>minio-client:latest-dev</code></td>
                    <td>—</td>
                    <td><code>minio-init</code></td>
                    <td>Bootstrap init container that provisions the <code>chege-photos</code> bucket and public thumbnail policy on boot.</td>
                </tr>
                <tr>
                    <td><strong>chege-redis</strong></td>
                    <td><code>redis:7-alpine</code></td>
                    <td><code>6379</code></td>
                    <td><code>redis</code></td>
                    <td>In-memory session cache (with automated MySQL fallback) and durable asynchronous background scan queue.</td>
                </tr>
                <tr>
                    <td><strong>chege-qdrant</strong></td>
                    <td><code>qdrant/qdrant:v1.13.2</code></td>
                    <td><code>6333</code> / <code>6334</code></td>
                    <td><code>qdrant</code></td>
                    <td>HNSW high-dimensional vector search engine indexing 512-d ArcFace and CLIP embeddings with sub-15ms latency.</td>
                </tr>
                <tr>
                    <td><strong>chege-dozzle</strong></td>
                    <td><code>amir20/dozzle:latest</code></td>
                    <td><code>8888</code></td>
                    <td><code>dozzle</code></td>
                    <td>Real-time visual container log streamer with read-only Docker socket mount and optional password authentication.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Multimodal Ingestion Pipeline (The 5 Stages)</h2>
        <p>How an uploaded photo is securely cataloged, stored in S3, and enriched with artificial intelligence.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">1</div>
                <h6 style="font-size:0.85rem;">Upload & EXIF</h6>
                <p style="font-size:0.82rem;">Image received via WebApp or Android background sync. GPS coordinates, camera model, and capture date are extracted into memory.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">2</div>
                <h6 style="font-size:0.85rem;">MinIO S3 Persistence</h6>
                <p style="font-size:0.82rem;">Media is committed to MinIO S3 Object Storage via AWS SigV4, keeping containers stateless and enabling offsite disaster recovery.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">3</div>
                <h6 style="font-size:0.85rem;">Relational Indexing</h6>
                <p style="font-size:0.82rem;">Metadata, SHA-256 deduplication hash, and album assignments are committed to MySQL 8.4; scan job is enqueued in Redis.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">4</div>
                <h6 style="font-size:0.85rem;">Asynchronous Vision Extraction</h6>
                <p style="font-size:0.82rem;">FastAPI ML microservice pops the job from Redis: InsightFace detects faces, YOLOv8 tags 80+ scenes, and Tesseract OCR extracts text.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">5</div>
                <h6 style="font-size:0.85rem;">Qdrant Vector Indexing</h6>
                <p style="font-size:0.82rem;">ArcFace 512-d unit vectors and CLIP cross-modal embeddings are indexed into Qdrant for sub-15ms natural language search.</p>
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
