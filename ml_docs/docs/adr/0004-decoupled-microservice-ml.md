# ADR 0004: Decoupled Python FastAPI Microservice for Machine Learning

* **Status**: Accepted
* **Date**: 2026-09-06
* **Deciders**: Systems & ML Architecture Team

---

## Context & Problem Statement

Chege Photos requires computationally intensive computer vision capabilities:
1. High-precision face detection and 512-dimensional ArcFace feature vector extraction (InsightFace).
2. Unsupervised facial identity clustering using density-based algorithms (HDBSCAN).
3. Real-time object detection and tagging (YOLOv8).
4. Natural language multimodal semantic search (OpenAI CLIP).

Running these deep learning workloads directly within the PHP 8.3 Apache web container causes memory exhaustion, blocking I/O on web threads, and difficult dependency management (compiling PyTorch and ONNX Runtime into PHP C extensions).

## Decision Drivers

1. **Process Isolation**: Prevent deep learning memory spikes from taking down web and mobile API serving.
2. **Ecosystem Fit**: Python has native, first-class bindings for PyTorch, ONNX Runtime, InsightFace, and Qdrant.
3. **Hardware Scalability**: Allow independent CPU/GPU resource allocation and scaling for ML workloads.

## Considered Options

1. **In-Process PHP Extensions**: Call ONNX or PyTorch via PHP FFI or C bindings.
2. **Third-Party Cloud APIs**: Offload face recognition and tagging to AWS Rekognition or Google Cloud Vision.
3. **Decoupled Python FastAPI Microservice**: Dedicated container communicating via internal HTTP/REST.

## Decision Outcome

Chosen option: **Option 3 (Decoupled Python FastAPI Microservice)**.

### Implementation Details:
* Microservice runs FastAPI with Uvicorn on container port 8000 (host port 9051).
* Attaches to `hosts-shared-network` created by the WebApp compose stack.
* Authenticates requests via `X-API-KEY`.
* High-dimensional vector search offloaded to a collocated Qdrant vector database container.

## Consequences

### Positive
* WebApp web requests remain fast and responsive.
* ML models load once into memory during service lifespan startup (`lifespan()`).
* 100% self-hosted; zero external cloud API subscriptions or recurring costs.

### Negative
* Requires running and maintaining a secondary container stack with independent health checks and memory requirements.
