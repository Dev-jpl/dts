"""
Embedding microservice for DTS Smart Search.
Uses sentence-transformers with all-MiniLM-L6-v2 (384-dim, ~80MB, CPU-friendly).

Run: uvicorn app:app --port 5100
"""

import os

from fastapi import FastAPI, HTTPException, Request
from fastapi.responses import JSONResponse
from pydantic import BaseModel, field_validator
from sentence_transformers import SentenceTransformer
import numpy as np

app = FastAPI(title="DTS Embedding Service", docs_url=None, redoc_url=None)

API_KEY = os.environ.get("EMBEDDING_API_KEY", "")

MAX_TEXT_LENGTH = 50_000
MAX_BATCH_SIZE = 100


@app.middleware("http")
async def check_api_key(request: Request, call_next):
    if API_KEY and request.url.path != "/health":
        if request.headers.get("X-API-Key") != API_KEY:
            return JSONResponse(status_code=401, content={"detail": "Unauthorized"})
    return await call_next(request)


# Load model once at startup (~80MB download on first run)
model = SentenceTransformer("all-MiniLM-L6-v2")

MAX_CHUNK_TOKENS = 256
OVERLAP_TOKENS = 50


class EmbedRequest(BaseModel):
    text: str | None = None
    texts: list[str] | None = None

    @field_validator("text")
    @classmethod
    def validate_text_length(cls, v):
        if v is not None and len(v) > MAX_TEXT_LENGTH:
            raise ValueError(f"Text exceeds maximum length of {MAX_TEXT_LENGTH} characters")
        return v

    @field_validator("texts")
    @classmethod
    def validate_texts(cls, v):
        if v is not None:
            if len(v) > MAX_BATCH_SIZE:
                raise ValueError(f"Batch size exceeds maximum of {MAX_BATCH_SIZE}")
            for i, t in enumerate(v):
                if len(t) > MAX_TEXT_LENGTH:
                    raise ValueError(f"Text at index {i} exceeds maximum length of {MAX_TEXT_LENGTH} characters")
        return v


class EmbedResponse(BaseModel):
    vector: list[float] | None = None
    vectors: list[list[float]] | None = None


def chunk_text(text: str, max_tokens: int = MAX_CHUNK_TOKENS, overlap: int = OVERLAP_TOKENS) -> list[str]:
    """Split text into overlapping chunks by whitespace tokens."""
    words = text.split()
    if len(words) <= max_tokens:
        return [text]

    chunks = []
    start = 0
    while start < len(words):
        end = start + max_tokens
        chunk = " ".join(words[start:end])
        chunks.append(chunk)
        start = end - overlap
    return chunks


def embed_text(text: str) -> list[float]:
    """Embed a single text, chunking if necessary and averaging vectors."""
    text = text.strip()
    if not text:
        return []

    chunks = chunk_text(text)

    if len(chunks) == 1:
        embedding = model.encode(chunks[0])
        return embedding.tolist()

    # Average chunk embeddings
    embeddings = model.encode(chunks)
    avg = np.mean(embeddings, axis=0)
    # Normalize
    norm = np.linalg.norm(avg)
    if norm > 0:
        avg = avg / norm
    return avg.tolist()


@app.post("/embed", response_model=EmbedResponse)
async def embed(request: EmbedRequest):
    if request.text is not None:
        vector = embed_text(request.text)
        if not vector:
            raise HTTPException(status_code=400, detail="Empty text provided")
        return EmbedResponse(vector=vector)

    if request.texts is not None:
        vectors = [embed_text(t) for t in request.texts]
        return EmbedResponse(vectors=vectors)

    raise HTTPException(status_code=400, detail="Provide 'text' or 'texts'")


@app.get("/health")
async def health():
    return {"status": "ok", "model": "all-MiniLM-L6-v2", "dimensions": 384}
