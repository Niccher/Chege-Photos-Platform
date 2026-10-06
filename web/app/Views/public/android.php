<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>Android App — Native Mobile Companion<?= $this->endSection() ?>
<?= $this->section('description') ?>Native Android companion app for Chege Photos. Background photo sync, face recognition overlays, biometric unlock, and instant 6-digit & QR pairing. Built with Kotlin and Jetpack Compose.<?= $this->endSection() ?>
<?= $this->section('nav-android') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="Chege Photos Android App — Native Kotlin Companion">
<meta property="og:description" content="Native Android companion app with background photo sync, real-time face bounding box overlays, biometric fingerprint unlock, and frictionless QR pairing.">
<meta name="keywords" content="android, kotlin, jetpack compose, photo sync, face recognition, biometric lock, open source">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>Android Companion App</h1>
    <p>A native Kotlin app built with Jetpack Compose that seamlessly connects your mobile device to your private photo cloud — with full face recognition support.</p>
</div>

<section class="section">
    <div class="row g-5 align-items-start">
        <div class="col-lg-7">
            <h3 style="font-weight: 700; color: var(--text-primary);">Mobile Superpowers</h3>

            <div class="d-flex flex-column gap-4 mt-4">
                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-arrow-up-circle"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Automated Background Sync</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">WorkManager monitors your device's MediaStore and uploads new camera photos in the background. Features SHA-256 hash deduplication to ensure photos are never uploaded twice, with per-file progress notifications.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-qr-code-scan"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Hybrid QR & 6-Digit PIN Pairing</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Connect to your self-hosted server in seconds without typing long passwords or URLs on your phone. Scan a single QR code or enter a 6-digit numeric pairing PIN generated from the web portal.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-person-bounding-box"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Interactive Face Pager & Bounding Boxes</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Explore auto-clustered persons with thumbnail avatars. The <strong>PersonPhotoPager</strong> enables fullscreen swiping with real-time face bounding box overlays — highlighting the active individual in gold and companions in green.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-fingerprint"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Hardware Biometric Security</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Keep your private memories safe from prying eyes. Protect app launch with device biometric authentication (Fingerprint or Face Unlock) using AndroidX Biometrics.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-images"></i></div>
                    <div>
                        <h6 style="font-weight:600;">High-Performance Photo Browser</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Butter-smooth grid powered by Jetpack Compose and Coil image loading. Long-press to trigger batch actions: favorite, archive, add to album, or download.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-palette"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Dynamic Theming</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Material 3 dynamic theming adapting to your wallpaper on Android 12+, with manual dark, light, solarized, and AMOLED black options.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-pub">
                <h5>Frictionless Authentication</h5>
                <p style="font-size:0.9rem;">Two secure connection modes:</p>
                <ul style="color:var(--text-muted);font-size:0.88rem;line-height:2;">
                    <li><strong>Instant QR / 6-Digit PIN</strong> — Generate an ephemeral pairing code on your web dashboard; scan with the app or type 6 numbers.</li>
                    <li><strong>Direct Credential Login</strong> — Standard email & password authentication via <code>POST /api/v1/login</code>.</li>
                </ul>
                <p style="font-size:0.9rem;">Token authentication enforces cryptographic device fingerprinting to ensure stolen tokens cannot be used on rogue hardware.</p>
                <hr style="border-color:var(--border-color);">
                <h5>Network Auto-Detection</h5>
                <p style="color:var(--text-muted);font-size:0.88rem;line-height:1.6;">The Android app auto-detects private LAN subnets (<code>192.168.x.x</code>, <code>10.0.2.2</code> in emulator) and smoothly transitions between local Wi-Fi and external domain reverse proxies.</p>
                <p style="font-size:0.85rem;color:var(--text-muted);">Current host base URL: <code><?= base_url() ?></code></p>
            </div>

            <div class="card-pub mt-3">
                <h5>Engineering Specifications</h5>
                <table class="table table-sm table-pub mb-0">
                    <tbody>
                        <tr><td>Language</td><td>Kotlin 2.0+ (Coroutines & Flow)</td></tr>
                        <tr><td>UI Toolkit</td><td>Jetpack Compose + Material 3</td></tr>
                        <tr><td>Networking</td><td>Retrofit 2.11 + OkHttp 4.12</td></tr>
                        <tr><td>Background Sync</td><td>AndroidX WorkManager (Durable)</td></tr>
                        <tr><td>Local Cache</td><td>Room 2.6 (Offline MediaStore Index)</td></tr>
                        <tr><td>Target Platform</td><td>Android 10+ (API 29 – 36)</td></tr>
                        <tr><td>Scanner</td><td>CameraX 1.4 + Google ML Kit</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Three Simple Steps to Connect</h2>
        <p>Pairing your mobile device to your private cloud takes under 30 seconds.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-pub h-100 text-center">
                <div class="step-num mx-auto mb-2">1</div>
                <h6>Generate Pairing Code</h6>
                <p style="font-size:0.85rem;">In your web portal, navigate to <strong>Settings → Mobile Pairing</strong> and click "Pair Device". A QR code and a 6-digit numeric PIN appear on screen.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100 text-center">
                <div class="step-num mx-auto mb-2">2</div>
                <h6>Scan or Enter PIN</h6>
                <p style="font-size:0.85rem;">Launch Chege Photos on Android, tap "Quick Pair", and point your camera at the QR code or type the 6-digit PIN. Your session is authenticated instantly.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100 text-center">
                <div class="step-num mx-auto mb-2">3</div>
                <h6>Automate Background Sync</h6>
                <p style="font-size:0.85rem;">Enable background sync to automatically backup your camera roll whenever you connect to Wi-Fi. All face clustering and AI tagging run on your server.</p>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
