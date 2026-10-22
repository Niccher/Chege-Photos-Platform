<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>Setup Guide — Turnkey Docker Deployment<?= $this->endSection() ?>
<?= $this->section('description') ?>Complete setup and deployment guide for Chege Photos Platform. Deploy the unified monorepo with WebApp, FastAPI ML, MySQL 8.4, Redis 7, and Qdrant using Docker Compose or automated deploy script.<?= $this->endSection() ?>
<?= $this->section('nav-setup') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="Setup Guide — Chege Photos Platform Deployment">
<meta property="og:description" content="Step-by-step guide to deploying Chege Photos Platform with Docker Compose, automated health probes, and companion Android setup.">
<meta name="keywords" content="docker, setup, deployment, self-hosted, docker compose, deploy.sh, installation guide">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>Turnkey Setup Guide</h1>
    <p>Deploy the entire Chege Photos platform on your home server, NAS, or private VPS with a single command.</p>
</div>

<section class="section">
    <div class="section-header">
        <h2>Prerequisites</h2>
        <p>Ensure your host environment meets the recommended baseline requirements.</p>
    </div>
    <div class="row g-3 justify-content-center">
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div style="font-size:2.5rem; color:var(--accent-color);"><i class="bi bi-box"></i></div>
                <h5 class="mt-2">Docker Engine</h5>
                <p style="font-size:0.85rem;">Docker Engine ≥ 24.0 and Docker Compose v2. Install from <a href="https://docs.docker.com/engine/install/" class="auth-link" target="_blank">docker.com</a>.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div style="font-size:2.5rem; color:var(--accent-color);"><i class="bi bi-hdd-stack"></i></div>
                <h5 class="mt-2">Storage</h5>
                <p style="font-size:0.85rem;">At least 15 GB free disk space for OS packages, pre-downloaded AI model checkpoints (~500 MB), and photo storage.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div style="font-size:2.5rem; color:var(--accent-color);"><i class="bi bi-motherboard"></i></div>
                <h5 class="mt-2">Hardware</h5>
                <p style="font-size:0.85rem;">2+ CPU cores, 4 GB+ RAM recommended. Runs entirely on CPU via optimized ONNX Runtime; GPU is optional.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 1: Clone the Unified Platform Monorepo</h2>
        <p>All backend services (Web, ML, MySQL, Redis, Qdrant) live together in a single coordinated repository.</p>
    </div>
    <div class="card-pub">
        <pre class="code-block">git clone https://github.com/niccher/Chege-Photos-Platform.git
cd Chege-Photos-Platform</pre>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 2: Choose Your Deployment Method</h2>
        <p>Choose automated 13-stage deployment or standard Docker Compose.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary px-2 py-1">Recommended</span>
                    <h5 class="mb-0">Option A: One-Click Script</h5>
                </div>
                <p style="font-size:0.9rem; color:var(--text-muted);">Runs system diagnostics, detects LAN IP, writes <code>.env</code>, handles container lifecycle, probes service health, and migrates database tables:</p>
                <pre class="code-block">bash scripts/deploy.sh</pre>
                <p style="font-size:0.85rem; color:var(--text-muted);" class="mt-2 mb-0">Follow the terminal prompts. The script displays a live pipeline progress timer and prints the final endpoint matrix.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Option B: Manual Docker Compose</h5>
                <p style="font-size:0.9rem; color:var(--text-muted);">For traditional Docker workflows or CI/CD pipelines:</p>
                <pre class="code-block"># 1. Copy environment template
cp .env.example .env

# 2. Build and launch all containers
docker compose up --build -d

# 3. Check running services
docker compose ps</pre>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Standard Production Port Matrix</h2>
        <p>All services bind to clean, standard industry ports on your host machine.</p>
    </div>
    <div class="card-pub">
        <table class="table table-sm table-pub mb-0">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Standard Port</th>
                    <th>Protocol</th>
                    <th>Default Role / Endpoint</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Web Dashboard & API Gateway</strong></td>
                    <td><code>80</code></td>
                    <td>HTTP</td>
                    <td><code>http://localhost</code> (UI & Mobile Gateway)</td>
                </tr>
                <tr>
                    <td><strong>ML Vision Microservice</strong></td>
                    <td><code>8000</code></td>
                    <td>HTTP</td>
                    <td><code>http://localhost:8000/docs</code> (Interactive Swagger)</td>
                </tr>
                <tr>
                    <td><strong>MySQL 8.4 Relational DB</strong></td>
                    <td><code>3306</code></td>
                    <td>TCP</td>
                    <td>Database: <code>db_chege_photos</code> / Root: <code>root_password</code></td>
                </tr>
                <tr>
                    <td><strong>MinIO S3 Object Storage</strong></td>
                    <td><code>9000</code> / <code>9001</code></td>
                    <td>HTTP</td>
                    <td><code>http://localhost:9001</code> (Web Console: <code>minio_admin</code> / <code>minio_password123</code>)</td>
                </tr>
                <tr>
                    <td><strong>Dozzle Visual Logs</strong></td>
                    <td><code>8888</code></td>
                    <td>HTTP</td>
                    <td><code>http://localhost:8888</code> (Real-time split-screen container logs)</td>
                </tr>
                <tr>
                    <td><strong>Redis 7 Queue & Cache</strong></td>
                    <td><code>6379</code></td>
                    <td>TCP</td>
                    <td>Background job broker & session cache</td>
                </tr>
                <tr>
                    <td><strong>Qdrant Vector Database</strong></td>
                    <td><code>6333</code> / <code>6334</code></td>
                    <td>HTTP / gRPC</td>
                    <td><code>http://localhost:6333/dashboard</code> (Vector Console)</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 3: Verification & Initial Login</h2>
        <p>Confirm the health of your newly deployed instance.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Health Check & Diagnostics</h5>
                <pre class="code-block"># Verify Web service health (reports platform_version & php_version):
curl -s http://localhost/api/v1/health | jq

# Verify ML microservice & models:
curl -s http://localhost:8000/health | jq

# Scan across all 7 cluster containers for errors:
bash scripts/logs.sh --errors</pre>
                <p style="font-size:0.85rem; color:var(--text-muted);" class="mt-2 mb-0">The web health check confirms platform release <code>v<?= defined('PLATFORM_VERSION') ? PLATFORM_VERSION : '1.3.0' ?></code> on PHP 8.4, and the ML check confirms model weights are loaded.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Default Administrator Account</h5>
                <p style="font-size:0.9rem; color:var(--text-muted);">The database seeder automatically initializes a default superadmin user:</p>
                <ul style="color:var(--text-muted); font-size:0.88rem; line-height:2;">
                    <li><strong>Email:</strong> <code>admin@example.com</code></li>
                    <li><strong>Password:</strong> <code>admin_password</code></li>
                </ul>
                <p style="font-size:0.85rem; color:var(--text-muted);" class="mb-0">Log in immediately at <code>http://localhost/login</code> and update your password in <strong>Settings → Security</strong>.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 4: Connect the Android Companion App</h2>
        <p>Synchronize photos directly from your phone to your local cloud.</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Generate Mobile Pairing PIN</h5>
                <ol style="color:var(--text-muted); font-size:0.88rem; line-height:2;">
                    <li>Log in to your web portal</li>
                    <li>Navigate to <strong>Settings → Mobile Pairing</strong></li>
                    <li>Click <strong>Pair New Device</strong></li>
                    <li>A temporary QR code and 6-digit numeric PIN are displayed (valid for 15 minutes)</li>
                </ol>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Pair and Sync on Android</h5>
                <ol style="color:var(--text-muted); font-size:0.88rem; line-height:2;">
                    <li>Install the Chege Photos APK on Android 10+</li>
                    <li>Tap <strong>Quick Pair</strong></li>
                    <li>Point your camera at the QR code or type the 6-digit PIN</li>
                    <li>Enable background sync to backup your camera roll automatically over Wi-Fi</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section-sm" style="border-top: 1px solid var(--border-color); text-align:center;">
    <p style="color:var(--text-muted);">Instance configured? <a href="<?= url_to('login') ?>" class="auth-link">Sign in to your dashboard</a> or explore the <a href="<?= base_url('faq') ?>" class="auth-link">frequently asked questions</a>.</p>
</section>

<?= $this->endSection() ?>
