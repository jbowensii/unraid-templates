# John's voice: Chatterbox-Turbo + fine-tuned T3, served at /v1/audio/speech.
# Model files are NOT in the image; mount them at /models (see README).
FROM pytorch/pytorch:2.6.0-cuda12.4-cudnn9-runtime

RUN apt-get update && apt-get install -y --no-install-recommends git ffmpeg \
 && rm -rf /var/lib/apt/lists/*

# Fine-tuning toolkit supplies the vendored Chatterbox code + loader; pinned commit.
ARG CBFT_REF=2a6aba38365782fce8258a6f51708ac19c8093ce
RUN git clone https://github.com/gokhaneraslan/chatterbox-finetuning.git /opt/cbft \
 && cd /opt/cbft && git checkout "$CBFT_REF" \
 && sed -i 's/extend_vocab: bool = True/extend_vocab: bool = False/' src/config.py \
 && grep -q 'extend_vocab: bool = False' src/config.py \
 && pip install --no-cache-dir -r requirements.txt fastapi==0.115.6 "uvicorn[standard]==0.34.0"

COPY server.py pronounce.py /app/
RUN python /app/server.py --check

ENV MODEL_DIR=/models/base \
    T3_WEIGHTS=/models/t3_turbo_finetuned_merged.safetensors \
    REF_WAV=/models/ref.wav \
    HF_HOME=/models/hf-cache \
    PORT=8880 \
    IDLE_UNLOAD_S=300
EXPOSE 8880
HEALTHCHECK --interval=60s --timeout=10s --start-period=60s CMD python -c "import urllib.request;urllib.request.urlopen('http://127.0.0.1:8880/health',timeout=5)"
CMD ["python", "-u", "/app/server.py"]
