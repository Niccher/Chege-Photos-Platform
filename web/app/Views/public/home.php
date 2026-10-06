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
        <span class="tech-badge"><i class="bi bi-filetype-php me-1"></i>PHP 8.3 / CI4</span>
        <span class="tech-badge"><i class="bi bi-database me-1"></i>MySQL 8.4</span>
        <span class="tech-badge"><i class="bi bi-lightning-charge me-1"></i>Python 3.12 / FastAPI</span>
        <span class="tech-badge"><i class="bi bi-person-bounding-box me-1"></i>InsightFace (ArcFace)</span>
        <span class="tech-badge"><i class="bi bi-search me-1"></i>OpenAI CLIP</span>
        <span class="tech-badge"><i class="bi bi-tag me-1"></i>YOLOv8</span>
        <span class="tech-badge"><i class="bi bi-fonts me-1"></i>Tesseract OCR</span>
        <span class="tech-badge"><i class="bi bi-diagram-3 me-1"></i>Qdrant Vector DB</span>
        <span class="tech-badge"><i class="bi bi-hdd-network me-1"></i>Redis 7</span>
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
            <p>Single <code>docker compose up --build -d</code> deploys MySQL 8.4, Redis 7, Qdrant Vector DB, FastAPI ML, and WebApp. Features automated model pre-downloads and Docker layer caching.</p>
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
