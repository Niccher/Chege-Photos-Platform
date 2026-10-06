<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>Multimodal AI Engine — Local Vision & Semantic Search<?= $this->endSection() ?>
<?= $this->section('description') ?>Discover Chege Photos' local AI engine: OpenAI CLIP semantic search, InsightFace facial recognition, Ultralytics YOLOv8 object detection, Tesseract OCR, and Qdrant vector indexing.<?= $this->endSection() ?>
<?= $this->section('nav-ml') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="Chege Photos ML — On-Premise Multimodal Vision Engine">
<meta property="og:description" content="Local, high-speed neural networks running 100% on your server. Semantic search via CLIP, facial clustering via InsightFace, object tagging via YOLOv8, and vector search with Qdrant.">
<meta name="keywords" content="face recognition, CLIP semantic search, YOLOv8, OCR, insightface, qdrant, vector database, fastapi, machine learning, local AI">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>On-Premise Multimodal AI</h1>
    <p>Computer vision, natural language search, facial clustering, and text OCR — an asynchronous Python 3.12 / FastAPI microservice delivering private AI intelligence without cloud subscriptions.</p>
    <div class="mt-3 d-flex gap-2 flex-wrap justify-content-center">
        <span class="ml-tag ml-tag-fastapi">FastAPI 0.115</span>
        <span class="ml-tag ml-tag-insight">InsightFace (ArcFace)</span>
        <span class="ml-tag ml-tag-qdrant">OpenAI CLIP ViT-B/32</span>
        <span class="ml-tag ml-tag-hdbscan">YOLOv8 Nano</span>
        <span class="ml-tag ml-tag-fastapi">Tesseract OCR</span>
        <span class="ml-tag ml-tag-qdrant">Qdrant 1.13 HNSW</span>
    </div>
</div>

<section class="section">
    <div class="section-header">
        <h2>Four AI Superpowers in One Service</h2>
        <p>The vision engine extracts semantic intelligence from every image in parallel, cataloging people, scenes, objects, and text in real-time.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-search-heart"></i></div>
                <h5>Semantic Search (CLIP)</h5>
                <p style="font-size:0.85rem;">OpenAI CLIP transformer maps images and natural language queries into a unified 512-d embedding space, enabling conceptual search like <em>"sunset over water"</em>.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-person-bounding-box"></i></div>
                <h5>Face Clustering (ArcFace)</h5>
                <p style="font-size:0.85rem;">RetinaFace extracts 5-point aligned crops while ArcFace generates 512-d unit vectors. HDBSCAN clusters faces into named individuals automatically.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-tag"></i></div>
                <h5>Object Tagging (YOLOv8)</h5>
                <p style="font-size:0.85rem;">Ultralytics YOLOv8 detects 80+ visual classes (pets, vehicles, food, buildings, sports) with bounding boxes and confidence filtering.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-fonts"></i></div>
                <h5>Text Recognition (OCR)</h5>
                <p style="font-size:0.85rem;">Tesseract optical character recognition extracts textual strings from documents, signs, menus, and screenshots for full-text searchability.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Multimodal Architecture & Vector Flow</h2>
        <p>How models process incoming media and populate relational and vector storage engines.</p>
    </div>
    <div class="diagram-box">
┌──────────────┐    ┌─────────────────────────────────────────────────────────────┐    ┌───────────────────────────┐
│              │    │               FastAPI AI Inference Pipelines                │    │    Persistent Storage     │
│              │    │                                                             │    │                           │
│  Input Photo │───▶│  1. InsightFace (RetinaFace ──▶ ArcFace 512-d vector)       │───▶│  Qdrant face_embeddings   │
│  (Uploaded)  │    │  2. OpenAI CLIP (ViT-B/32 Image Encoder ──▶ 512-d vector)   │───▶│  Qdrant photo_embeddings  │
│              │    │  3. YOLOv8 (Object & Tag Detection Classes ──▶ BBoxes)      │───▶│  MySQL object_detections  │
│              │    │  4. Tesseract OCR (In-Image Text Extraction ──▶ Strings)    │───▶│  MySQL photo_ocr_texts    │
│              │    │  5. 2-Stage Safety Gate (OpenNSFW + NudeNet Verification)   │───▶│  MySQL photo flags        │
└──────────────┘    └─────────────────────────────────────────────────────────────┘    └───────────────────────────┘
    </div>

    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <h5 style="font-weight:600; color: var(--text-primary);">Neural Network Specifications</h5>
            <table class="table table-sm table-pub">
                <thead><tr><th>Task</th><th>Model / Architecture</th><th>Output / Dimension</th></tr></thead>
                <tbody>
                    <tr><td>Face Detection</td><td>RetinaFace (MobileNet0.25)</td><td>5-point landmarks + BBox</td></tr>
                    <tr><td>Face Embedding</td><td>ArcFace ResNet-100 (buffalo_l)</td><td>512 float32 (unit vector)</td></tr>
                    <tr><td>Semantic Search</td><td>OpenAI CLIP ViT-B/32</td><td>512 float32 (shared space)</td></tr>
                    <tr><td>Object Detection</td><td>Ultralytics YOLOv8n ONNX</td><td>80 COCO classes + conf</td></tr>
                    <tr><td>Text Extraction</td><td>Tesseract OCR (Eng engine)</td><td>Normalized text tokens</td></tr>
                    <tr><td>Content Safety</td><td>Hierarchical OpenNSFW + NudeNet</td><td>Confidence safety scores</td></tr>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h5 style="font-weight:600; color: var(--text-primary);">Vector Indexing (Qdrant)</h5>
            <table class="table table-sm table-pub">
                <thead><tr><th>Collection</th><th>Metric</th><th>Search Latency</th></tr></thead>
                <tbody>
                    <tr><td><code>face_embeddings</code></td><td>Cosine</td><td>&lt; 15 ms (HNSW Graph)</td></tr>
                    <tr><td><code>face_embeddings_centroids</code></td><td>Cosine</td><td>&lt; 5 ms (Person Centroids)</td></tr>
                    <tr><td><code>photo_embeddings</code></td><td>Cosine</td><td>&lt; 20 ms (CLIP Multi-Modal)</td></tr>
                    <tr><td>Storage Engine</td><td>mmap on disk</td><td>Hardware-accelerated</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Unsupervised Face Clustering (HDBSCAN)</h2>
        <p>Turning hundreds of unknown face crops into distinct, identifiable family and friends.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Two-Tier Clustering Architecture</h5>
                <p style="font-size:0.9rem;">When a photo is processed, extracted face embeddings are compared against existing person <strong>centroids</strong> using cosine similarity ($\ge 0.80$). If closely matched, the face is assigned immediately.</p>
                <p style="font-size:0.9rem;" class="mb-0">Unassigned faces accumulate in an unclustered pool. When the threshold is reached, an <strong>HDBSCAN sweep</strong> calculates density-based clusters, generating new person identities automatically.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Identity & Centroid Management</h5>
                <p style="font-size:0.9rem;">Each person cluster maintains a normalized running mean vector (centroid):</p>
                <div class="p-2 mb-2 rounded" style="background:var(--bg-primary);font-family:monospace;font-size:0.85rem;">
                    C = sum(v_i) / ||sum(v_i)||_2
                </div>
                <p style="font-size:0.9rem;" class="mb-0">This allows $O(\log N)$ nearest-neighbor lookups in Qdrant rather than scanning individual face items, guaranteeing high performance even across millions of photos.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Local AI vs. Cloud Surveillance</h2>
        <p>Why hosting your own vision engine is fundamentally superior to third-party commercial APIs.</p>
    </div>
    <div class="card-pub">
        <table class="table table-sm table-pub mb-0">
            <thead><tr><th>Evaluation Metric</th><th>Cloud Giants (AWS, Google, OpenAI)</th><th>Chege Photos Local AI</th></tr></thead>
            <tbody>
                <tr><td>Privacy & Confidentiality</td><td style="color:var(--text-muted);">Photos sent to third-party datacenters</td><td><strong>100% on-premise; zero bytes leave server</strong></td></tr>
                <tr><td>Usage Costs & Bills</td><td style="color:var(--text-muted);">$0.001 – $0.02 per image API fee</td><td><strong>$0.00 forever; run unlimited scans</strong></td></tr>
                <tr><td>Offline Capability</td><td style="color:var(--text-muted);">Fails immediately if internet disconnects</td><td><strong>Works fully offline on local LAN</strong></td></tr>
                <tr><td>Hardware Requirements</td><td style="color:var(--text-muted);">Requires high cloud bandwidth</td><td><strong>Runs on commodity CPUs (GPU optional)</strong></td></tr>
                <tr><td>Data Retention & Control</td><td style="color:var(--text-muted);">Subject to arbitrary cloud TOS changes</td><td><strong>Permanent, immutable data ownership</strong></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Production Integration</h2>
        <p>The ML microservice runs containerized in the unified platform monorepo on standard port <code>8000</code>.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Platform Docker Service</h5>
                <pre class="code-block mt-2">ml:
  build:
    context: ./ml
    dockerfile: Dockerfile
  container_name: chege-photos-ml
  ports:
    - "8000:8000"
  volumes:
    - ./web/public/uploads:/app/uploads:ro
    - insightface_models:/app/models/insightface_models
  depends_on:
    mysql:
      condition: service_healthy
    redis:
      condition: service_healthy
    qdrant:
      condition: service_started</pre>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Health & Diagnostics</h5>
                <pre class="code-block mt-2"># Check microservice readiness:
curl -s http://localhost:8000/health | jq

# Sample response:
{
  "status": "healthy",
  "database": "connected",
  "redis": "connected",
  "qdrant": "connected",
  "models_loaded": true,
  "collections": ["face_embeddings", "photo_embeddings"]
}</pre>
                <p class="mt-2 mb-0" style="color:var(--text-muted);font-size:0.85rem;">Pre-downloads models during image build with BuildKit pip caching to guarantee instant zero-delay container startup.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-sm" style="border-top: 1px solid var(--border-color);">
    <div class="text-center">
        <h3 style="font-weight: 700; color: var(--text-primary);">Experience Private AI Today</h3>
        <p style="color: var(--text-muted);">Deploy the stack using our <a href="<?= base_url('setup') ?>" class="auth-link">one-click setup guide</a> or <a href="<?= url_to('login') ?>" class="auth-link">sign in</a> to your existing library.</p>
    </div>
</section>

<?= $this->endSection() ?>
