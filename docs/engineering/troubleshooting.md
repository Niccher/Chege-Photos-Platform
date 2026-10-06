# Engineering Troubleshooting Guide

Diagnostic procedures for compiler, wheel dependency, CPU instruction set, and runtime issues encountered during local development of ML Chege Photos.

---

## 1. PyTorch / ONNX Runtime Wheel Installation Issues

Installing PyTorch and ONNX Runtime locally on bare-metal systems may require explicit wheel channels:

```bash
# Ensure pip, setuptools, and wheel are up to date:
pip install --upgrade pip setuptools wheel

# Install CPU-specific PyTorch wheels:
pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu
```

---

## 2. Missing CPU Vector Instructions (AVX / AVX2)

InsightFace and ONNX Runtime require AVX/AVX2 instruction sets:

* **Symptom**: `Illegal instruction (core dumped)` when importing `onnxruntime` or `insightface`.
* **Fix**: If running inside a virtual machine or older hardware, install `onnxruntime` built without AVX2 flags, or enable CPU host passthrough in hypervisor settings.

---

## 3. OpenCV Headless Dependency Errors

If OpenCV throws `ImportError: libGL.so.1: cannot open shared object file`:

```bash
# Ubuntu / Debian system dependency fix:
sudo apt-get update && sudo apt-get install -y libgl1-mesa-glx libglib2.0-0

# Or switch to headless opencv:
pip uninstall -y opencv-python
pip install opencv-python-headless
```

---

## 4. Qdrant Client Connection Timeouts

If FastAPI logs `qdrant_client.http.exceptions.ResponseHandlingException: Connection refused`:

```bash
# Check if Qdrant container is up and bound to port 6333:
curl http://localhost:6333/collections

# In local bare-metal mode, set QDRANT_HOST=localhost in .env:
export QDRANT_HOST=localhost
export QDRANT_PORT=6333
```
