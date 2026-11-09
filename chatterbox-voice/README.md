# chatterbox-voice

John's cloned voice as an HTTP service: Chatterbox-Turbo plus a LoRA fine-tuned on about an hour of his recordings, merged into one T3 file. Built for scripted voiceovers.

```bash
curl -s http://tower03:8880/v1/audio/speech -H "Content-Type: application/json" \
  -d '{"input":"Hello everybody, this is Mereak Firmaxe."}' -o line.wav
```

- `/v1/audio/speech` (POST, `{"input": "..."}`) returns a 24 kHz mono WAV. Text over 5000 characters is rejected, so send one paragraph per request.
- `/health` returns `{"ok": true, "loaded": ..., "vram_gb": ...}`.
- The GPU is shared with llama-swap. The model loads on the first request (about 20 s) and unloads after `IDLE_UNLOAD_S` seconds idle. It needs about 5 GB of VRAM.
- The name "Mereak Firmaxe" is respelled to "Mary-ack Firmax" before generation, and sentences that contain it use `NAME_TEMPERATURE` (0.5). It is an invented word, so it can still come out wrong now and then. Re-request that line.

## Model folder (`/models`)

| Path | What |
|---|---|
| `base/` | Chatterbox-Turbo files from the toolkit's `setup.py` (original BPE tokenizer) |
| `t3_turbo_finetuned_merged.safetensors` | the fine-tuned voice (checkpoint 370, merged) |
| `ref.wav` | 10-second reference clip of John |
| `lora/` | the un-merged adapter, kept as a backup |

## Build

The image is built by GitHub Actions and pushed to `ghcr.io/jbowensii/chatterbox-voice`. It pins [gokhaneraslan/chatterbox-finetuning](https://github.com/gokhaneraslan/chatterbox-finetuning) at a fixed commit, because that toolkit provides the loader. Training notes live in John's `voice-training` scripts.
