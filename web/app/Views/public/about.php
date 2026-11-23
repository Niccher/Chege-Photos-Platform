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
│                    Chege Photos Platform (chege-photos-net)                            │
│                                                                                        │
│  Browser / Client ──HTTPS:443▶ chege-photos-caddy (Caddy 2 Alpine + tls internal)      │
│                                  │  php_fastcgi :9000 (flush_interval -1)              │
│                                  ▼                                                     │
│                                chege-photos-web (PHP 8.4.28 FPM + OPcache JIT)         │
│                                  ├── chege-photos-minio (Port 9000 S3 / 9001 Console)  │
│                                  │   Primary Store: chege-photos | chege-models | bak  │
│                                  ├── chege-photos-mysql (MySQL 8.4 / Port 3306)        │
│                                  │   Users, albums, EXIF, captions, video transcripts  │
│                                  ├── chege-photos-redis (Redis 7 / Port 6379)          │
│                                  │   Sessions, cache & durable ML scan queue           │
│                                  ├── chege-photos-ml (FastAPI + ffmpeg / Port 8000)    │
│                                  │   ├── SigLIP 768-d, Florence-2, Whisper Turbo       │
│                                  │   ├── InsightFace buffalo_l, YOLOv8n, OpenNSFW      │
│                                  │   └── chege-photos-qdrant (Port 6333 / 6334)        │
│                                  └── chege-photos-dozzle (Port 8888 Live Log Viewer)   │
│                                                                                        │
│  Android Client ────HTTP:80──▶ Nginx + WebApp Gateway (Strict /api/v1 isolation)       │
└────────────────────────────────────────────────────────────────────────────────────────┘
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Production Container Cluster (9 Services)</h2>
        <p>Stateless Web &amp; ML containers with zero host media volumes; all user media, AI model weights, and cold backups live in MinIO.</p>
    </div>
    <div class="table-responsive">
        <table class="table table-pub align-middle">
            <thead>
                <tr>
                    <th>Container Service</th>
                    <th>Runtime / Base</th>
                    <th>Standard Port</th>
                    <th>Internal Network Alias</th>
                    <th>Role &amp; Responsibility</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>chege-photos-caddy</strong></td>
                    <td><code>caddy:2-alpine</code></td>
                    <td><code>80</code> / <code>443</code> (HTTP/3)</td>
                    <td><code>caddy</code>, <code>chegephotos.local</code></td>
                    <td>Reverse proxy with automatic local TLS (<code>https://chegephotos.local</code>), zero-buffer FastCGI streaming, <code>/logs*</code>, and <code>/ml/*</code>.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-web</strong></td>
                    <td><code>php:8.4.28-fpm-bookworm</code></td>
                    <td>Internal <code>9000</code></td>
                    <td><code>web</code>, <code>php-fpm</code></td>
                    <td>Stateless CodeIgniter 4 NTS PHP-FPM workers with OPcache JIT (CVE-2026-126714 patched), Shield auth, and cron scheduler.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-ml</strong></td>
                    <td><code>python:3.12-slim</code> + <code>ffmpeg</code></td>
                    <td><code>8000</code></td>
                    <td><code>ml</code>, <code>ml-chege-photos</code></td>
                    <td>Stateless FastAPI multimodal AI engine running SigLIP 768-d, Florence-2, Whisper Large-v3-Turbo, InsightFace, YOLOv8n, and OpenNSFW.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-mysql</strong></td>
                    <td><code>mysql:8.4</code></td>
                    <td><code>3306</code></td>
                    <td><code>mysql</code></td>
                    <td>ACID relational database for users, albums, photos, AI captions, video transcripts, EXIF, and audit logs.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-minio</strong></td>
                    <td><code>cgr.dev/chainguard/minio</code></td>
                    <td><code>9000</code> / <code>9001</code></td>
                    <td><code>minio</code></td>
                    <td>Primary S3 object storage hosting <code>chege-photos</code> (media), <code>chege-models</code> (AI weights), and <code>chege-backups</code>.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-minio-init</strong></td>
                    <td><code>minio-client:latest-dev</code></td>
                    <td>—</td>
                    <td><code>minio-init</code></td>
                    <td>Bootstrap init container provisioning <code>chege-photos</code>, <code>chege-models</code>, and <code>chege-backups</code> buckets.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-redis</strong></td>
                    <td><code>redis:7-alpine</code></td>
                    <td><code>6379</code></td>
                    <td><code>redis</code></td>
                    <td>In-memory session cache (with MySQL fallback) and durable asynchronous ML background scan queue.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-qdrant</strong></td>
                    <td><code>qdrant/qdrant:latest</code></td>
                    <td><code>6333</code> / <code>6334</code></td>
                    <td><code>qdrant</code></td>
                    <td>HNSW vector search engine indexing 768-d SigLIP image/transcript vectors and 512-d ArcFace embeddings.</td>
                </tr>
                <tr>
                    <td><strong>chege-photos-dozzle</strong></td>
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
