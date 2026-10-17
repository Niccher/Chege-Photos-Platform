from __future__ import annotations

import logging
from fastapi import APIRouter, Depends, Header, HTTPException, Query
from qdrant_client import QdrantClient

from app.config import settings
from app.database import get_qdrant
from app.ml import semantic_search

log = logging.getLogger("ml_chege_photos.api_search")
router = APIRouter(prefix="/api/v1/search", tags=["search"])

@router.get("/semantic")
def semantic_search_endpoint(
    query: str = Query(..., description="Natural language query"),
    limit: int = Query(20, ge=1, le=100),
    user_id: int | None = Query(None, description="Target User ID for multi-tenant isolation"),
):
    if user_id is None or user_id <= 0:
        return {
            "query": query,
            "results": []
        }
    try:
        query_vec = semantic_search.encode_text(query)
        qdrant = get_qdrant()
        
        from qdrant_client.models import Filter, FieldCondition, MatchValue
        query_filter = Filter(
            must=[
                FieldCondition(
                    key="user_id",
                    match=MatchValue(value=user_id)
                )
            ]
        )

        hits = qdrant.search(
            collection_name=settings.qdrant_photo_collection,
            query_vector=query_vec,
            limit=limit,
            query_filter=query_filter,
            with_payload=True,
        )
        results = []
        for hit in hits:
            payload = hit.payload or {}
            photo_id = payload.get("photo_id")
            if photo_id is not None:
                results.append({
                    "score": hit.score,
                    "photo_id": int(photo_id),
                })
        return {
            "query": query,
            "results": results
        }
    except Exception as exc:
        log.error("Semantic search failed: %s", exc)
        raise HTTPException(500, f"Semantic search failed: {exc}")


@router.get("/similar/{photo_id}")
def similar_photos_endpoint(
    photo_id: int,
    limit: int = Query(20, ge=1, le=100),
    user_id: int | None = Query(None, description="Target User ID for multi-tenant isolation"),
    x_webapp_url: str | None = Header(None, alias="X-Webapp-Url"),
):
    """Find visually and semantically similar photos using CLIP embeddings."""
    try:
        qdrant = get_qdrant()
        from qdrant_client.models import Filter, FieldCondition, MatchValue

        # Resolve photo owner to enforce multi-tenant isolation
        if user_id is None:
            from app.database import get_db
            from sqlalchemy import text
            try:
                db_lookup = next(get_db())
                p_owner = db_lookup.execute(
                    text(f"SELECT user_id FROM `{settings.effective_db_name}`.tbl_photos WHERE id = :pid"),
                    {"pid": photo_id}
                ).fetchone()
                db_lookup.close()
                if p_owner and p_owner[0] is not None:
                    user_id = int(p_owner[0])
            except Exception:
                pass

        # 1. Attempt to fetch pre-computed CLIP vector from Qdrant
        photo_filter = Filter(
            must=[FieldCondition(key="photo_id", match=MatchValue(value=photo_id))]
        )
        scroll_res = qdrant.scroll(
            collection_name=settings.qdrant_photo_collection,
            scroll_filter=photo_filter,
            limit=1,
            with_vectors=True,
        )

        query_vec = None
        points = scroll_res[0] if scroll_res and len(scroll_res) > 0 else []
        if points and points[0].vector:
            query_vec = points[0].vector

        # 2. If vector is not yet in Qdrant, compute on the fly
        if query_vec is None:
            from app.database import get_db
            from sqlalchemy import text
            from pathlib import Path
            import uuid
            from qdrant_client.models import PointStruct

            db = next(get_db())
            photo_row = db.execute(
                text(f"SELECT id, path, user_id FROM `{settings.effective_db_name}`.tbl_photos WHERE id = :pid"),
                {"pid": photo_id}
            ).fetchone()
            db.close()

            if not photo_row:
                raise HTTPException(404, f"Photo {photo_id} not found")

            from app.services.storage import resolve_photo_path
            photo_path = resolve_photo_path(photo_row.path, webapp_url=x_webapp_url)

            if not photo_path.exists() or photo_path.stat().st_size == 0:
                raise HTTPException(404, f"Photo file not available for embedding")

            query_vec = semantic_search.encode_image(str(photo_path))

            # Store in Qdrant for next time
            owner_id = photo_row.user_id if hasattr(photo_row, "user_id") else (photo_row[2] if len(photo_row) > 2 else None)
            qdrant.upsert(
                collection_name=settings.qdrant_photo_collection,
                points=[PointStruct(
                    id=str(uuid.uuid4()),
                    vector=query_vec,
                    payload={
                        "photo_id": photo_id,
                        "user_id": int(owner_id) if owner_id is not None else None,
                    }
                )]
            )

        # 3. Query nearest neighbors
        query_filter = None
        if user_id is not None:
            query_filter = Filter(
                must=[FieldCondition(key="user_id", match=MatchValue(value=user_id))]
            )

        # Fetch extra results to allow excluding the query photo itself
        hits = qdrant.search(
            collection_name=settings.qdrant_photo_collection,
            query_vector=query_vec,
            limit=limit + 5,
            query_filter=query_filter,
            with_payload=True,
        )

        results = []
        for hit in hits:
            payload = hit.payload or {}
            hit_pid = payload.get("photo_id")
            if hit_pid is not None and int(hit_pid) != photo_id:
                results.append({
                    "score": round(float(hit.score), 4),
                    "photo_id": int(hit_pid),
                })
            if len(results) >= limit:
                break

        return {
            "photo_id": photo_id,
            "results": results,
            "count": len(results)
        }
    except HTTPException:
        raise
    except Exception as exc:
        log.error("Visual similarity search failed for photo %d: %s", photo_id, exc)
        raise HTTPException(500, f"Similarity search failed: {exc}")


@router.get("/ocr")
def ocr_search_endpoint(
    query: str = Query(..., min_length=1, description="Text snippet to search within photos"),
    limit: int = Query(20, ge=1, le=100),
    user_id: int | None = Query(None),
):
    """Search photos containing specific OCR-recognized text."""
    try:
        from app.database import get_db
        from sqlalchemy import text
        from app.config import settings

        db = next(get_db())
        _db_schema = settings.effective_db_name

        sql = f"""
            SELECT id, user_id, filename, path, thumbnail_path, ocr_text, taken_at
            FROM `{_db_schema}`.tbl_photos
            WHERE ocr_text LIKE :pattern
        """
        params = {"pattern": f"%{query}%"}
        if user_id is not None:
            sql += " AND user_id = :uid"
            params["uid"] = user_id

        sql += " ORDER BY id DESC LIMIT :limit"
        params["limit"] = limit

        rows = db.execute(text(sql), params).fetchall()
        results = []
        for r in rows:
            ocr_snippet = ""
            if r.ocr_text:
                lower_text = r.ocr_text.lower()
                idx = lower_text.find(query.lower())
                if idx >= 0:
                    start = max(0, idx - 40)
                    end = min(len(r.ocr_text), idx + len(query) + 40)
                    ocr_snippet = ("..." if start > 0 else "") + r.ocr_text[start:end].strip() + ("..." if end < len(r.ocr_text) else "")
                else:
                    ocr_snippet = r.ocr_text[:80].strip() + "..."

            results.append({
                "photo_id": int(r.id),
                "filename": r.filename,
                "path": r.path,
                "thumbnail_path": r.thumbnail_path,
                "ocr_snippet": ocr_snippet,
                "taken_at": str(r.taken_at) if r.taken_at else None,
            })

        return {
            "query": query,
            "count": len(results),
            "results": results,
        }
    except Exception as exc:
        log.error("OCR text search failed for query '%s': %s", query, exc)
        raise HTTPException(500, f"OCR search failed: {exc}")


