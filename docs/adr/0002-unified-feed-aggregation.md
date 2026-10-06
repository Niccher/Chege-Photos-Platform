# ADR 0002: Unified Polymorphic Feed Aggregation

* **Status**: Accepted
* **Date**: 2026-09-06
* **Deciders**: Systems & Mobile Team

---

## Context & Problem Statement

Users browse diverse media types (still photos, live bursts, motion clips, raw videos) across both the web dashboard and the native Android companion app. Querying distinct database tables for photos versus videos creates complex client-side pagination, synchronization gaps, and fragmented sorting.

## Decision Drivers

1. **Seamless Chronological Scrolling**: Infinite scrolling ordered strictly by capture timestamp (`taken_at`).
2. **Simplified Client Caching**: Single entity schema for Room SQLite on Android and web feed renders.
3. **Database Performance**: Consistent index scanning on `(user_id, taken_at DESC)`.

## Considered Options

1. **Split Endpoints (`/api/v1/photos` and `/api/v1/videos`)**: Client requests both and merges streams in memory.
2. **Polymorphic Database Table with Unified Endpoint**: Single `tbl_photos` table storing MIME types (`image/*`, `video/*`) served via `/api/v1/photos`.

## Decision Outcome

Chosen option: **Option 2 (Polymorphic Table with Unified Endpoint)**.

### Implementation Details:
* All media records reside in `tbl_photos` with explicit `mime_type`, `duration_seconds` (null for photos), and `thumbnail_path`.
* `/api/v1/photos` returns a unified chronological stream sorted by `taken_at DESC`.
* Android app consumes a single `PhotoEntity` Room model with a `mediaType` discriminator.

## Consequences

### Positive
* Single query with predictable pagination (`LIMIT 50 OFFSET X`).
* Android WorkManager sync downloads and caches feed items through one consolidated pipeline.

### Negative
* `tbl_photos` contains columns specific to videos (e.g. `duration_seconds`) that remain NULL for static images.
