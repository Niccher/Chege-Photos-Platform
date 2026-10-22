<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>Self-Hosted Photo Cloud with Local Multimodal AI<?= $this->endSection() ?>
<?= $this->section('description') ?>Chege Photos is a self-hosted private photo cloud with local multimodal AI: natural-language semantic search (CLIP), facial clustering (InsightFace), object detection (YOLOv8), text OCR, and companion Android sync.<?= $this->endSection() ?>
<?= $this->section('nav-home') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="Chege Photos — Self-Hosted Photo Cloud with Local Multimodal AI">
<meta property="og:description" content="Take full control of your photos on your own infrastructure. Natural language search via CLIP, facial recognition via InsightFace, object detection via YOLOv8, and vector search with Qdrant.">
<meta property="og:type" content="website">
<meta name="keywords" content="self-hosted, photo cloud, face recognition, semantic search, CLIP, YOLOv8, OCR, Qdrant, Docker, open source">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<section class="hero">
    <h1>Your private photo cloud. <br><span class="accent">Powered by local multimodal AI.</span></h1>
    <p>Complete data sovereignty meets modern computer vision. Self-host your entire photo library with natural-language semantic search, automated face clustering, object tagging, and text OCR — running 100% on your own hardware with zero cloud subscriptions.</p>
    <div class="hero-actions">
        <a href="<?= url_to('register') ?>" class="btn-primary-pub px-4 py-2" style="font-size:1rem;">
            <i class="bi bi-person-plus me-2"></i>Get Started Free
        </a>
        <a href="<?= url_to('login') ?>" class="btn-outline-pub px-4 py-2" style="font-size:1rem;">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </a>
    </div>
    <div class="mt-4 d-flex gap-2 flex-wrap justify-content-center" style="font-size: 0.8rem; color: var(--text-muted);">
        <span class="tech-badge"><i class="bi bi-filetype-php me-1"></i>PHP 8.4 / CI4</span>
        <span class="tech-badge"><i class="bi bi-database me-1"></i>MySQL 8.4</span>
        <span class="tech-badge"><i class="bi bi-lightning-charge me-1"></i>Python 3.12 / FastAPI</span>
        <span class="tech-badge"><i class="bi bi-person-bounding-box me-1"></i>InsightFace (ArcFace)</span>
        <span class="tech-badge"><i class="bi bi-search me-1"></i>OpenAI CLIP</span>
        <span class="tech-badge"><i class="bi bi-tag me-1"></i>YOLOv8</span>
        <span class="tech-badge"><i class="bi bi-fonts me-1"></i>Tesseract OCR</span>
        <span class="tech-badge"><i class="bi bi-diagram-3 me-1"></i>Qdrant Vector DB</span>
        <span class="tech-badge"><i class="bi bi-hdd-network me-1"></i>Redis 7</span>
        <span class="tech-badge"><i class="bi bi-cloud-arrow-up me-1"></i>MinIO S3 / Multi-Cloud</span>
        <span class="tech-badge"><i class="bi bi-journal-text me-1"></i>Two-Tier Observability</span>
        <span class="tech-badge"><i class="bi bi-phone me-1"></i>Kotlin / Compose</span>
        <span class="tech-badge"><i class="bi bi-box me-1"></i>Docker Compose</span>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Three components, one unified platform</h2>
        <p>Chege Photos is a cohesive ecosystem — a rich desktop web management portal, a native Android mobile companion, and an on-premise AI microservice that transforms photos into intelligent, searchable memories.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div class="card-icon"><i class="bi bi-window-stack"></i></div>
                <h5>Web Management Platform</h5>
                <p>CodeIgniter 4 backend with responsive Bootstrap 5 UI. Multi-file upload, smart rule albums, EXIF analytics, public token sharing, GPS photo heatmaps, memories, and superadmin telemetry console.</p>
                <a href="<?= base_url('about') ?>" class="auth-link" style="font-size:0.85rem;">Platform overview <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div class="card-icon"><i class="bi bi-phone"></i></div>
                <h5>Native Android Companion</h5>
                <p>Modern Kotlin app built with Jetpack Compose. Seamless background MediaStore synchronization, biometric fingerprint unlock, 6-digit & QR pairing, and interactive face bounding box overlays.</p>
                <a href="<?= base_url('android') ?>" class="auth-link" style="font-size:0.85rem;">Mobile features <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div class="card-icon"><i class="bi bi-cpu"></i></div>
                <h5>Local Multimodal AI Engine</h5>
                <p>Python 3.12 / FastAPI microservice combining InsightFace face clustering, OpenAI CLIP semantic vector search, YOLOv8 object tagging, OCR text extraction, and Qdrant vector indexing.</p>
                <a href="<?= base_url('ml') ?>" class="auth-link" style="font-size:0.85rem;">AI architecture <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Built for privacy. Engineered for power.</h2>
        <p>Enjoy every convenience of commercial cloud photo giants without surrendering personal privacy or paying recurring monthly subscription fees.</p>
    </div>
    <div class="feature-grid">
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-shield-check"></i></div>
            <h5>100% Data Sovereignty</h5>
            <p>Every photo, database record, and vector embedding resides strictly on your own hardware. Zero telemetry, zero external data mining, and zero third-party vendor lock-in.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-search-heart"></i></div>
            <h5>Natural Language Semantic Search</h5>
            <p>Search your library by typing visual concepts in plain English — like <em>"birthday cake with candles"</em>, <em>"hiking in the forest"</em>, or <em>"golden retriever puppy"</em>. Powered by OpenAI CLIP vector embeddings.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-people"></i></div>
            <h5>Facial Recognition & Clustering</h5>
            <p>InsightFace RetinaFace detects every face while ArcFace extracts 512-dimensional embeddings. HDBSCAN clustering automatically groups family and friends into named identities with demographic attributes.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-tag"></i></div>
            <h5>Object & Scene Detection</h5>
            <p>Ultralytics YOLOv8 classifies photos across 80+ visual categories (animals, vehicles, food, landscapes, architecture), enabling instant multi-tag filtering without manual tagging.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-fonts"></i></div>
            <h5>In-Photo Text Search (OCR)</h5>
            <p>Integrated Tesseract OCR parses text inside screenshots, street signs, receipts, and documents, making words appearing inside your images searchable instantly.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-qr-code-scan"></i></div>
            <h5>Frictionless Mobile Pairing</h5>
            <p>Connect your Android phone in seconds using instant QR code scanning or a 6-digit numeric PIN. No cumbersome passwords or server tokens to type on mobile screens.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-geo-alt"></i></div>
            <h5>Interactive GPS Map & Memories</h5>
            <p>Interactive Leaflet heatmaps plot your travels based on embedded EXIF GPS tags. Rediscover forgotten moments through automated "On This Day" and "6 Months Ago" memory carousels.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-box-seam"></i></div>
            <h5>Turnkey Docker Deployment</h5>
            <p>Single <code>docker compose up --build -d</code> deploys MySQL 8.4, Redis 7, Qdrant Vector DB, MinIO, FastAPI ML, and WebApp with automated model downloads.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-cloud-check"></i></div>
            <h5>MinIO S3 & Multi-Cloud Storage</h5>
            <p>Decoupled object storage powered by MinIO with AWS SigV4 authorization. Supports seamless failover and offsite disaster recovery mirroring to AWS S3 or Google Cloud Storage.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-speedometer2"></i></div>
            <h5>Two-Tier Logging & Observability</h5>
            <p>Production monitoring with Dozzle real-time visual streaming, microsecond ML request latency tracking, framework stderr streaming, and 100MB disk-capped log rotation.</p>
        </div>
    </div>
</section>

<!-- 5-Stage Media Pipeline Section -->
<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>How Chege Photos Works: The 5-Stage Media Pipeline</h2>
        <p>From camera shutter to sub-second vector search: how user media is ingested, processed, indexed, and synchronized across private microservices.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="step-num">1</span>
                    <h5 class="mb-0">Chunked Ingestion & EXIF</h5>
                </div>
                <p>The <strong>PHP 8.4 WebApp</strong> receives files up to 512MB via resumable chunked uploads or companion Android background sync. GPS coordinates, camera model, lens aperture, and capture timestamps are extracted immediately via <code>exif</code> into memory.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="step-num">2</span>
                    <h5 class="mb-0">Decoupled MinIO S3 Storage</h5>
                </div>
                <p>Media objects are streamed into <strong>MinIO S3 Object Storage</strong> using secure AWS SigV4 authorization. Thumbnails and original assets are stored with deterministic object paths, keeping application containers stateless and enabling seamless multi-cloud mirroring.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="step-num">3</span>
                    <h5 class="mb-0">Relational Indexing (MySQL 8.4)</h5>
                </div>
                <p><strong>MySQL 8.4</strong> commits ACID-compliant photo records, file hashes (SHA-256 deduplication), user ownership, and album structures. Slow queries (>2s) and system errors are tracked to guarantee database integrity.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="step-num">4</span>
                    <h5 class="mb-0">Asynchronous AI Vision Pipeline (Redis + FastAPI)</h5>
                </div>
                <p>The WebApp enqueues media scan jobs into <strong>Redis 7</strong>. The <strong>FastAPI ML microservice</strong> pulls jobs asynchronously to avoid blocking web traffic, passing frames through 4 local neural pipelines: RetinaFace (5-point landmark alignment), ArcFace (512-d embeddings), YOLOv8 (80+ objects), and Tesseract OCR.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="step-num">5</span>
                    <h5 class="mb-0">Vector Indexing & Semantic Search (Qdrant)</h5>
                </div>
                <p>High-dimensional vectors from ArcFace and OpenAI CLIP are indexed in <strong>Qdrant Vector Database</strong> with HNSW cosine similarity. Queries execute in sub-15ms, returning instant search results to desktop browsers and mobile clients.</p>
            </div>
        </div>
    </div>
</section>

<!-- Supporting Infrastructure Containers Section -->
<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Supporting Infrastructure Containers</h2>
        <p>A production-ready cluster orchestrated seamlessly via Docker Compose on an isolated bridge network.</p>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5><i class="bi bi-database me-2 text-primary"></i>MySQL 8.4</h5>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.7;">
                    <strong>Port:</strong> <code>3306</code><br>
                    <strong>Role:</strong> Primary relational data store for photo metadata, EXIF tags, albums, user accounts, and authentication credentials. Features transactional integrity and slow-query observability.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5><i class="bi bi-lightning-charge me-2 text-primary"></i>Redis 7 Alpine</h5>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.7;">
                    <strong>Port:</strong> <code>6379</code><br>
                    <strong>Role:</strong> Low-latency in-memory session cache (with automated MySQL fallback) and durable queue broker decoupling web uploads from heavy deep learning inference.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5><i class="bi bi-cloud-arrow-up me-2 text-primary"></i>MinIO Object Storage</h5>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.7;">
                    <strong>Ports:</strong> <code>9000</code> (S3) / <code>9001</code> (Console)<br>
                    <strong>Role:</strong> High-performance distributed object storage using Chainguard distroless containers. Decouples binary media storage from application compute with AWS S3 & GCP backup compatibility.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5><i class="bi bi-diagram-3 me-2 text-primary"></i>Qdrant Vector Engine</h5>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.7;">
                    <strong>Ports:</strong> <code>6333</code> (HTTP) / <code>6334</code> (gRPC)<br>
                    <strong>Role:</strong> High-dimensional vector database indexing 512-d ArcFace facial embeddings and CLIP cross-modal text-to-image vectors with approximate nearest neighbor (HNSW) search.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5><i class="bi bi-terminal me-2 text-primary"></i>Dozzle Visual Logs</h5>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.7;">
                    <strong>Port:</strong> <code>8888</code><br>
                    <strong>Role:</strong> Ultra-lightweight (~15 MB RAM) real-time log inspector. Connects via read-only Docker socket mount to stream container logs live over WebSockets with password authentication support.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5><i class="bi bi-cpu me-2 text-primary"></i>FastAPI ML Service</h5>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.7;">
                    <strong>Port:</strong> <code>8000</code><br>
                    <strong>Role:</strong> Dedicated Python 3.12 microservice running RetinaFace, ArcFace, HDBSCAN, YOLOv8, and Tesseract OCR models with internal health telemetry and microsecond timing.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Ready to take back control of your memories?</h2>
        <p>Deploy the stack on your home server, NAS, or private VPS and start experiencing private, AI-powered photo management today.</p>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-md-5">
            <div class="card-pub text-center h-100">
                <div style="font-size: 2.2rem; color: var(--accent-color); margin-bottom: 0.5rem;"><i class="bi bi-person-plus"></i></div>
                <h5>New to Chege Photos?</h5>
                <p style="font-size: 0.9rem;">Create your initial account to start uploading. The multimodal vision pipeline automatically detects faces, tags objects, and indexes your photos in the background.</p>
                <a href="<?= url_to('register') ?>" class="btn-primary-pub"><i class="bi bi-person-plus me-2"></i>Create Account</a>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card-pub text-center h-100">
                <div style="font-size: 2.2rem; color: var(--accent-color); margin-bottom: 0.5rem;"><i class="bi bi-box-arrow-in-right"></i></div>
                <h5>Already configured?</h5>
                <p style="font-size: 0.9rem;">Sign in to browse your library, manage named persons, inspect EXIF analytics, generate shared links, and pair the companion Android mobile client.</p>
                <a href="<?= url_to('login') ?>" class="btn-outline-pub"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</a>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
