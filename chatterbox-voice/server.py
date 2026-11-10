"""John's voice server: Chatterbox-Turbo + merged fine-tuned T3 (gokhaneraslan/chatterbox-finetuning loader).

POST /v1/audio/speech {"input": "text"} -> audio/wav (24 kHz, OpenAI-style)
GET  /health
Env: MODEL_DIR (/models/base: Turbo files from the toolkit's setup.py),
     T3_WEIGHTS (/models/t3_turbo_finetuned_merged.safetensors), REF_WAV (/models/ref.wav),
     PRONUNCIATION_FILE (/models/pronunciation.txt, re-read on change),
     TEMPERATURE (0.8), REP_PENALTY (1.2), PORT (8880).
Same text handling and sampling as the approved VO batch (voice-training/cbft_vo.py + cb_vo.py).
"""
import io, os, re, sys, threading

import numpy as np
import soundfile as sf

sys.path.insert(0, os.environ.get("CBFT_DIR", "/opt/cbft"))

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from pronounce import spoken, parse  # guide file /models/pronunciation.txt + numbers to words
MAX_CHARS = 5000  # ponytail: hard cap per request; split long scripts client-side


def chunks(text, max_words=30):
    sents = re.split(r"(?<=[.!?])\s+", text.strip())
    out, cur = [], ""
    for s in sents:
        if cur and len((cur + " " + s).split()) > max_words:
            out.append(cur); cur = s
        else:
            cur = (cur + " " + s).strip()
    return out + ([cur] if cur else [])


class Voice:
    def __init__(self):
        import inference as inf  # toolkit module; reads src/config.py (Turbo, original BPE)
        inf.BASE_MODEL_DIR = os.environ.get("MODEL_DIR", "/models/base")
        inf.FINETUNED_WEIGHTS = os.environ.get("T3_WEIGHTS", "/models/t3_turbo_finetuned_merged.safetensors")
        self.inf = inf
        self.eng = inf.load_finetuned_engine_full("cuda")
        self.ref = os.environ.get("REF_WAV", "/models/ref.wav")
        self.params = {"temperature": float(os.environ.get("TEMPERATURE", 0.8)),
                       "repetition_penalty": float(os.environ.get("REP_PENALTY", 1.2))}
        self.lock = threading.Lock()  # ponytail: one generation at a time on the 8 GB card

    def speak(self, text, max_words=30, temperature=None, seed=None):
        parts = []
        with self.lock:
            for c in chunks(spoken(text), max_words):
                p = dict(self.params)
                if temperature is not None:
                    p["temperature"] = temperature
                elif "maryak" in c.lower():  # ponytail: invented name is a coin flip at 0.8; 0.5 lands it more often, not always
                    p["temperature"] = float(os.environ.get("NAME_TEMPERATURE", 0.5))
                if seed is not None:  # same seed for every piece = same delivery style across the paragraph
                    self.inf.set_seed(seed)
                sr, a = self.inf.generate_sentence_audio(self.eng, c, self.ref, **p)
                if len(a):
                    parts += [a.astype(np.float32), np.zeros(int(0.25 * sr), dtype=np.float32)]
        if not parts:
            raise RuntimeError("no audio generated")
        buf = io.BytesIO()
        sf.write(buf, np.concatenate(parts[:-1]), 24000, format="WAV", subtype="PCM_16")
        return buf.getvalue()


def _check():
    assert spoken("Mereak's 10,000 nuyen (...) ok", parse("Mereak = Maryak\nnuyen = new yen")) == "Maryak's ten thousand new yen , ok"
    assert len(chunks(" ".join(["w."] * 61))) == 3


def make_app():
    import torch
    from fastapi import FastAPI, HTTPException
    from fastapi.responses import Response
    from pydantic import BaseModel

    class Req(BaseModel):
        input: str
        model: str | None = None
        voice: str | None = None
        response_format: str | None = "wav"
        max_words: int = 30         # words per generated piece; ~70 is the safe max (model stops at ~40 s)
        temperature: float | None = None
        seed: int | None = None

    import gc, time
    app = FastAPI()
    state = {"v": None, "last": 0.0}
    gate = threading.Lock()
    idle = int(os.environ.get("IDLE_UNLOAD_S", 300))  # 0 = keep loaded; GPU is shared with llama-swap

    def reaper():
        while idle > 0:
            time.sleep(15)
            with gate:
                if state["v"] is not None and time.time() - state["last"] > idle:
                    state["v"] = None; gc.collect(); torch.cuda.empty_cache()

    threading.Thread(target=reaper, daemon=True).start()

    @app.get("/health")
    def health():
        return {"ok": True, "loaded": state["v"] is not None,
                "vram_gb": round(torch.cuda.memory_allocated() / 1e9, 2)}

    @app.post("/v1/audio/speech")
    def speech(r: Req):
        text = r.input.strip()
        if not text or len(text) > MAX_CHARS:
            raise HTTPException(400, f"input must be 1..{MAX_CHARS} characters")
        if not 5 <= r.max_words <= 75 or (r.temperature is not None and not 0.1 <= r.temperature <= 1.5):
            raise HTTPException(400, "max_words must be 5..75, temperature 0.1..1.5")
        with gate:  # ponytail: requests serialize; fine for one-person batch VO
            if state["v"] is None:
                state["v"] = Voice()
            state["last"] = time.time()
            wav = state["v"].speak(text, r.max_words, r.temperature, r.seed)
            state["last"] = time.time()
        return Response(wav, media_type="audio/wav")

    return app


if __name__ == "__main__":
    _check()
    if "--check" in sys.argv:
        print("CHECK_OK"); sys.exit(0)
    import uvicorn
    uvicorn.run(make_app(), host="0.0.0.0", port=int(os.environ.get("PORT", 8880)))
