<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>FAQs — Chege Photos<?= $this->endSection() ?>
<?= $this->section('description') ?>Frequently asked questions about Chege Photos — self-hosted photo management platform with ML-powered face recognition. Installation, privacy, face recognition, and more.<?= $this->endSection() ?>
<?= $this->section('nav-faq') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="FAQs — Chege Photos">
<meta property="og:description" content="Frequently asked questions about Chege Photos — self-hosted photo management platform with ML-powered face recognition.">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= base_url('faq') ?>">
<meta name="keywords" content="Chege Photos FAQ, self-hosted photo management, photo privacy questions, face recognition FAQ, open source photo platform">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1><i class="bi bi-question-circle me-2" style="color: var(--accent-color);"></i>Frequently Asked Questions</h1>
    <p>Everything you need to know about Chege Photos — from installation to daily use.</p>
</div>

<div class="section-sm">
    <div class="accordion accordion-pub" id="faqAccordion">
        <!-- 1. What is Chege Photos -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    What is Chege Photos?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Chege Photos is a <strong>self-hosted, open-source</strong> photo management platform engineered for complete data privacy and local multimodal AI intelligence. It consists of the <strong>Chege-Photos-Platform monorepo</strong> (PHP 8.3 / CodeIgniter 4 web application + MySQL 8.4 + Python 3.12 / FastAPI vision service + Qdrant vector database + Redis 7) and the native <strong>Chege Photos Android companion app</strong> (Kotlin + Jetpack Compose). All neural models (CLIP semantic search, InsightFace clustering, YOLOv8 object tagging, Tesseract OCR) execute entirely on your own hardware — zero cloud subscriptions, zero telemetry, and zero third-party API dependencies.
                </div>
            </div>
        </div>

        <!-- 2. Is it free -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    Is Chege Photos free?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes. Chege Photos is 100% free and open-source under the <strong>MIT License</strong>. You can run it on your own homelab, local server, or VPS with no user limits, no feature paywalls, and no subscription tiers. Your only costs are your own server hardware and storage.
                </div>
            </div>
        </div>

        <!-- 3. Where are photos stored -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    Where are my photos and data stored?
                </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Your photos, thumbnails, and AI vector embeddings are stored <strong>exclusively on your own hardware</strong>. Media originals are preserved on your local filesystem, relational metadata resides in MySQL, and high-dimensional neural embeddings live in an on-premise Qdrant vector database. <strong>Your photos and biometric vectors never leave your premises.</strong>
                </div>
            </div>
        </div>

        <!-- 4. Natural Language Search -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_clip">
                    How does natural-language search work without third-party AI APIs?
                </button>
            </h2>
            <div id="faq_clip" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Unlike commercial clouds that send your private photos to third-party APIs (like OpenAI or Google Cloud), Chege Photos runs an embedded <strong>OpenAI CLIP (ViT-B/32)</strong> neural network directly inside your local FastAPI microservice. When photos are uploaded, CLIP computes a 512-dimensional vector capturing semantic concepts, objects, and scenes. When you search for queries like <em>"kids playing soccer on grass"</em> or <em>"sunset over snowy mountains"</em>, the text is vectorized locally and matched against your private Qdrant vector index in milliseconds via HNSW cosine similarity.
                </div>
            </div>
        </div>

        <!-- 5. Multimodal Vision Pipeline -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                    What AI capabilities are included in the Vision Engine?
                </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <p>The vision backend executes a unified multimodal inference pipeline whenever media is uploaded:</p>
                    <ol class="mb-0">
                        <li><strong>Face Detection &amp; Alignment</strong>: RetinaFace locates faces and key facial landmarks.</li>
                        <li><strong>Facial Embeddings</strong>: ArcFace extracts normalized 512-d biometric vectors per face.</li>
                        <li><strong>Identity Clustering</strong>: HDBSCAN automatically groups matching faces into recognizable people across your timeline.</li>
                        <li><strong>Semantic Search</strong>: CLIP ViT-B/32 maps photo content into a multi-modal text/image vector space.</li>
                        <li><strong>Object &amp; Tag Detection</strong>: Ultralytics YOLOv8 tags photos across 80+ visual categories.</li>
                        <li><strong>OCR Text Extraction</strong>: Tesseract OCR extracts text from receipts, documents, and signs for instant keyword retrieval.</li>
                        <li><strong>Vector Indexing</strong>: Qdrant stores high-dimensional embeddings with sub-second HNSW indexing.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- 6. GPU Requirements -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_gpu">
                    Do I need an expensive dedicated GPU?
                </button>
            </h2>
            <div id="faq_gpu" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <strong>No.</strong> The entire ML stack (ONNX Runtime, InsightFace, CLIP, YOLOv8) is optimized for multithreaded x86_64 CPU execution out of the box. A standard 4-core modern CPU processes photos smoothly in background queues. If you have an NVIDIA GPU, you can optionally pass through CUDA acceleration via Docker Compose for ultra-fast batch imports.
                </div>
            </div>
        </div>

        <!-- 7. Hardware Requirements -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                    What hardware specifications are recommended?
                </button>
            </h2>
            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <ul class="mb-0">
                        <li><strong>Minimum</strong>: 2 CPU cores, 4 GB RAM, 20 GB+ disk space (handles basic photo gallery and lightweight tagging).</li>
                        <li><strong>Recommended (Full Multimodal AI)</strong>: 4+ CPU cores, 8 GB RAM, SSD/NVMe storage for database and vector indexes.</li>
                        <li><strong>Optional Acceleration</strong>: NVIDIA GPU with CUDA 12+ for instant processing of massive library imports.</li>
                        <li><strong>Operating System</strong>: Linux (Ubuntu, Debian, Fedora, Arch) or any Docker-compatible host.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 8. Mobile Pairing & Security -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_mobile_pair">
                    How does the Android app connect securely without typing passwords?
                </button>
            </h2>
            <div id="faq_mobile_pair" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Chege Photos features a <strong>hybrid QR &amp; 6-digit numeric PIN pairing</strong> system. In your WebApp settings, generate a pairing session which displays both a dynamic QR code and an expiring 6-digit numeric PIN. On your Android phone, scan the QR code or enter the 6 digits — the server securely validates the device fingerprint and issues an encrypted API token. No passwords or lengthy server URLs ever need to be typed on your phone.
                </div>
            </div>
        </div>

        <!-- 9. Android Companion Features -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                    What makes the Android app different from a generic mobile web wrapper?
                </button>
            </h2>
            <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    The Android app is a 100% native client built with <strong>Kotlin and Jetpack Compose</strong>. It integrates directly with Android's MediaStore for background camera backup, includes biometric fingerprint and face lock, features zero-copy streaming uploads, and offers a specialized <code>PersonPhotoPager</code> that renders real-time face bounding box overlays over photos.
                </div>
            </div>
        </div>

        <!-- 10. Deduplication -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_dedup">
                    What happens if I upload duplicate photos?
                </button>
            </h2>
            <div id="faq_dedup" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Chege Photos computes cryptographic <strong>SHA-256 hashes</strong> before saving files. If a photo with the exact same content hash already exists in your library, the system prevents duplicate disk usage and skips redundant AI embedding generation, keeping your storage clean and efficient.
                </div>
            </div>
        </div>

        <!-- 11. Offsite Backups & GCS -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq12">
                    Can I back up my photos offsite or to cloud storage?
                </button>
            </h2>
            <div id="faq12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes. In addition to standard filesystem tools (like <code>restic</code>, <code>rsync</code>, or <code>rclone</code>), Chege Photos supports optional <strong>Google Cloud Storage (GCS)</strong> disaster-recovery hydration. You can configure encrypted bucket replication for offsite disaster recovery while keeping all active serving and AI inference 100% local.
                </div>
            </div>
        </div>

        <!-- 12. Installation & Quick Start -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                    How do I install and deploy Chege Photos?
                </button>
            </h2>
            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Clone the unified monorepo and run the one-click deployment script:
                    <pre class="bg-dark text-light p-3 rounded-3 mt-2 mb-2 font-monospace small"><code>git clone https://github.com/niccher/Chege-Photos-Platform.git
cd Chege-Photos-Platform
bash scripts/deploy.sh</code></pre>
                    The deployment script automatically checks prerequisites (Docker, Compose), configures environment secrets, sets standard service ports (<code>web:80</code>, <code>ml:8000</code>, <code>mysql:3306</code>, <code>redis:6379</code>, <code>qdrant:6333</code>), builds containers with layer caching, and runs health probes. See our <a href="<?= base_url('setup') ?>">Setup Guide</a> for full details.
                </div>
            </div>
        </div>

        <!-- 13. Background Crons -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_cron">
                    How are background tasks and AI clustering managed?
                </button>
            </h2>
            <div id="faq_cron" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    The platform runs a built-in container scheduler executing CodeIgniter's Spark task runner (<code>cron:run</code>) every minute. Administrative superusers can adjust execution intervals for individual jobs (Trash Purge, ML Face Clustering, Temp Upload Cleanup) directly from the Web UI under <strong>Admin Console &rarr; System Crons</strong>, complete with full execution logs and timing history.
                </div>
            </div>
        </div>

        <!-- 14. Supported Formats -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                    What file formats are supported?
                </button>
            </h2>
            <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <ul class="mb-0">
                        <li><strong>Images</strong>: JPEG, PNG, WebP, GIF, HEIC/HEIF</li>
                        <li><strong>Videos</strong>: MP4, MOV, WebM, MKV</li>
                        <li><strong>Metadata</strong>: Full EXIF extraction (camera make, model, ISO, shutter speed, focal length, GPS coordinates).</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 15. Offline / LAN operation -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                    Does Chege Photos work without an internet connection?
                </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes. The platform operates 100% offline within your local area network (LAN). All web interfaces, vector databases, and AI vision inference services operate locally. You can access your gallery, search by natural language, and sync your Android device completely off-grid.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center section-sm" style="border-top: 1px solid var(--border-color);">
    <h3 class="fw-bold mb-2" style="color: var(--text-primary);">Still have questions?</h3>
    <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
        Browse the Setup Guide for detailed instructions, or sign in to your gallery if you already have an account.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= base_url('setup') ?>" class="btn-primary-pub px-4 py-2"><i class="bi bi-book me-1"></i>Setup Guide</a>
        <a href="<?= url_to('login') ?>" class="btn-outline-pub px-4 py-2"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</a>
    </div>
</div>

<?= $this->endSection() ?>
