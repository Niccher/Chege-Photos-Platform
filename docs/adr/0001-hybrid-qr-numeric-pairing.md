# ADR 0001: Hybrid QR & Numeric Code Mobile Pairing

* **Status**: Accepted
* **Date**: 2026-09-06
* **Deciders**: Systems & Mobile Team

---

## Context & Problem Statement

Mobile companion clients must connect and authenticate to the self-hosted WebApp securely. Traditional username/password entry on mobile devices is tedious and prone to typing errors, particularly when connecting over local private IP addresses. Conversely, camera-only QR code scanning fails when the mobile camera is broken, permissions are denied, or the web screen is viewed in poor lighting conditions.

## Decision Drivers

1. **Frictionless Onboarding**: Link mobile devices in seconds.
2. **Resilience & Fallback**: Support environments where camera scanning is unavailable.
3. **Local Wi-Fi Security**: Protect against unauthorized pairing attempts from untrusted clients on shared local networks.

## Considered Options

1. **Username & Password Authentication**: Standard login form on mobile.
2. **Camera-Only QR Code Scanning**: Encode temporary secret token inside QR code.
3. **Hybrid QR & 6-Digit Numeric Code**: WebApp displays both a visual QR code and a large, high-contrast 6-digit numeric PIN.

## Decision Outcome

Chosen option: **Option 3 (Hybrid QR & 6-Digit Numeric Code)**.

### Implementation Details:
* When a user visits **User Profile / Settings → Mobile Companion App**, the server generates an ephemeral 6-digit pairing code stored with a 10-minute expiration.
* The web UI renders a QR code embedding `{"url":"http://...", "code":"123456"}` and displays the 6 digits prominently.
* The mobile client can either scan the QR code via ML Kit or manually type the server URL and 6-digit code.
* The server exchanges the valid code for a persistent Personal Access Token (`tbl_personal_access_tokens`) bound to the device's hardware fingerprint.

## Consequences

### Positive
* Zero user friction on mobile devices.
* Immediate fallback when camera permissions are restricted.
* Short 10-minute code expiration mitigates local Wi-Fi brute-force attacks.

### Negative
* Requires maintaining both QR rendering and numerical input UI components on mobile.
