from __future__ import annotations

import logging
from typing import Set
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import text

from app.config import settings
from app.database import get_db, get_qdrant, FaceEncoding, Person

log = logging.getLogger("ml_chege_photos.maintenance")
router = APIRouter(prefix="/api/v1/maintenance", tags=["maintenance"])


@router.post("/reconcile-vectors")
def reconcile_vectors(
    dry_run: bool = Query(False, description="If True, only audit without deleting orphaned vectors"),
    db: Session = Depends(get_db),
):
    """Audits and purges orphaned Qdrant points whose source photos in MySQL are either
    permanently deleted or soft-deleted (deleted_at IS NOT NULL).
    """
    qdrant = get_qdrant()
    _db_schema = settings.effective_db_name

    # 1. Fetch all active photo IDs from MySQL into a fast lookup set
    try:
        active_rows = db.execute(
            text(f"SELECT id FROM `{_db_schema}`.tbl_photos WHERE deleted_at IS NULL")
        ).fetchall()
        active_photo_ids: Set[int] = {int(r[0]) for r in active_rows}
    except Exception as exc:
        log.error("Failed to fetch active photos from MySQL: %s", exc)
        raise HTTPException(500, f"Failed to query MySQL active photos: {exc}")

    # ── Reconcile CLIP photo embeddings ─────────────────────────────
    photo_col = settings.qdrant_photo_collection
    total_photo_vecs = 0
    orphan_photo_point_ids = []
    offset = None

    try:
        while True:
            records, next_offset = qdrant.scroll(
                collection_name=photo_col,
                limit=250,
                offset=offset,
                with_payload=True,
                with_vectors=False,
            )
            for p in records:
                total_photo_vecs += 1
                payload = p.payload or {}
                pid = payload.get("photo_id")
                if pid is None or int(pid) not in active_photo_ids:
                    orphan_photo_point_ids.append(p.id)
            if next_offset is None:
                break
            offset = next_offset
    except Exception as exc:
        log.warning("Could not scroll photo_embeddings collection: %s", exc)

    deleted_photo_orphans = 0
    if not dry_run and orphan_photo_point_ids:
        for chunk in [orphan_photo_point_ids[i:i + 100] for i in range(0, len(orphan_photo_point_ids), 100)]:
            try:
                qdrant.delete(
                    collection_name=photo_col,
                    points_selector=chunk,
                )
                deleted_photo_orphans += len(chunk)
            except Exception as exc:
                log.error("Failed to delete chunk of photo orphan vectors: %s", exc)

    # ── Reconcile Face embeddings ──────────────────────────────────
    face_col = settings.qdrant_collection
    total_face_vecs = 0
    orphan_face_point_ids = []
    offset = None

    try:
        while True:
            records, next_offset = qdrant.scroll(
                collection_name=face_col,
                limit=250,
                offset=offset,
                with_payload=True,
                with_vectors=False,
            )
            for p in records:
                total_face_vecs += 1
                payload = p.payload or {}
                pid = payload.get("photo_id")
                if pid is None or int(pid) not in active_photo_ids:
                    orphan_face_point_ids.append(p.id)
            if next_offset is None:
                break
            offset = next_offset
    except Exception as exc:
        log.warning("Could not scroll face_embeddings collection: %s", exc)

    deleted_face_orphans = 0
    if not dry_run and orphan_face_point_ids:
        for chunk in [orphan_face_point_ids[i:i + 100] for i in range(0, len(orphan_face_point_ids), 100)]:
            try:
                qdrant.delete(
                    collection_name=face_col,
                    points_selector=chunk,
                )
                deleted_face_orphans += len(chunk)
            except Exception as exc:
                log.error("Failed to delete chunk of face orphan vectors: %s", exc)

    # ── Reconcile Person Centroids ─────────────────────────────────
    centroid_col = settings.qdrant_collection + "_centroids"
    total_centroids = 0
    orphan_centroid_point_ids = []
    offset = None

    try:
        active_person_rows = db.execute(
            text(f"SELECT DISTINCT person_id FROM `{_db_schema}`.tbl_face_encodings WHERE person_id IS NOT NULL")
        ).fetchall()
        active_person_ids = {int(r[0]) for r in active_person_rows}
    except Exception:
        active_person_ids = set()

    try:
        while True:
            records, next_offset = qdrant.scroll(
                collection_name=centroid_col,
                limit=250,
                offset=offset,
                with_payload=True,
                with_vectors=False,
            )
            for p in records:
                total_centroids += 1
                payload = p.payload or {}
                person_id = payload.get("person_id")
                if person_id is None or int(person_id) not in active_person_ids:
                    orphan_centroid_point_ids.append(p.id)
            if next_offset is None:
                break
            offset = next_offset
    except Exception:
        pass

    deleted_centroid_orphans = 0
    if not dry_run and orphan_centroid_point_ids:
        for chunk in [orphan_centroid_point_ids[i:i + 100] for i in range(0, len(orphan_centroid_point_ids), 100)]:
            try:
                qdrant.delete(
                    collection_name=centroid_col,
                    points_selector=chunk,
                )
                deleted_centroid_orphans += len(chunk)
            except Exception:
                pass

    return {
        "status": "success",
        "dry_run": dry_run,
        "photo_embeddings": {
            "total_scanned": total_photo_vecs,
            "orphans_found": len(orphan_photo_point_ids),
            "orphans_deleted": deleted_photo_orphans,
        },
        "face_embeddings": {
            "total_scanned": total_face_vecs,
            "orphans_found": len(orphan_face_point_ids),
            "orphans_deleted": deleted_face_orphans,
        },
        "centroids": {
            "total_scanned": total_centroids,
            "orphans_found": len(orphan_centroid_point_ids),
            "orphans_deleted": deleted_centroid_orphans,
        },
    }
