# Ecosystem Release Matrix & Compatibility

Release versioning strategy, multi-repo compatibility contracts, and deprecation policies across the Chege Photos platform.

---

## 1. Version Compatibility Matrix

Because Chege Photos is distributed across three repositories, services must maintain strict backward compatibility:

| WebApp Release | API Contract Version | Database Migration Level | Minimum Android App | Supported ML Service |
|---|---|---|---|---|
| **v1.0.x** | `/api/v1` | Migrations 001–015 | v1.0.0+ (Build 1) | v0.1.0+ |
| **v1.1.x** | `/api/v1` | Migrations 016–024 | v1.1.0+ (Build 5) | v0.2.0+ (CLIP + YOLO) |
| **v1.2.x** | `/api/v1` | Migrations 016–024 | v1.1.0+ (Build 5) | v0.2.0+ (Docker BuildKit caching & context slimming) |

---

## 2. Breaking Changes Protocol

1. **API Endpoints**: Existing `/api/v1/` routes must never break existing fields. If a breaking response schema change is necessary, introduce `/api/v2/`.
2. **Database Migrations**: Column renames or drops must follow the two-phase migration pattern:
   - Phase 1: Add new column, replicate data, keep old column intact.
   - Phase 2: After all clients and services migrate, drop the legacy column in a subsequent release.
3. **Deprecation Window**: Deprecated endpoints must return the `Sunset: <date>` HTTP header and remain functional for at least two minor release cycles.
