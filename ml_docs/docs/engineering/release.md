# Ecosystem Release Matrix & Model Versioning

Release tracking, model weight versions, and vector dimension compatibility for ML Chege Photos.

---

## 1. Model & Vector Schema Compatibility

Changes to underlying computer vision backbones alter vector embeddings and require collection recreation:

| Service Release | Face Model | Face Vector Dims | CLIP Model | CLIP Vector Dims | WebApp Version |
|---|---|---|---|---|---|
| **v0.1.x** | InsightFace Buffalo-S | 512 (Cosine) | None | — | v1.0.x |
| **v0.2.x** | InsightFace Buffalo-L | 512 (Cosine) | OpenAI ViT-B/32 | 512 (Cosine) | v1.1.x |

---

## 2. Re-Embedding & Migration Protocol

If a future release changes the model backbone:
1. **Side-by-side Collection**: Create a new versioned Qdrant collection (e.g. `clip_embeddings_v2`).
2. **Background Rescan**: Invoke the batch rescan worker to populate the new collection while the old collection serves live queries.
3. **Cutover**: Switch the query alias in settings and drop the legacy collection.
