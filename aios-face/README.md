# aios-face

An Unraid template for **aios-face**, a JARVIS-style web front end for a self-hosted AI
stack: streamed chat with tool calling, push-to-talk and hands-free voice, image / edit /
3D-model generation, an arc-reactor boot screen, a particle hologram that reacts to what the
assistant is doing, and live server metrics.

Source: https://github.com/jbowensii/aios-face

## What it is (and isn't)

aios-face is a **thin client**. It runs no models and stores nothing. Every request is routed
to a service you already host:

| Feature | Service it calls | Required |
|---|---|---|
| Chat, memory, documents, every tool | [LibreChat](https://www.librechat.ai/) Agents API (`/api/agents/v1/chat/completions`) | yes |
| Image, image edit, image→3D | an [imagegen-mcp](https://github.com/jbowensii/aios-face#image-bridge) bridge (MCP over streamable-http, backed by ComfyUI) | no |
| Speech to text | [speaches](https://github.com/speaches-ai/speaches) or any OpenAI-compatible `/v1/audio/transcriptions` | no |
| Text to speech | [kokoro-fastapi](https://github.com/remsky/Kokoro-FastAPI) or any OpenAI-compatible `/v1/audio/speech` | no |
| CPU / memory / array gauges | the Unraid GraphQL API with a read-only key | no |

With only LibreChat configured you get a text chat with tool calling and the visuals.

## Image

Published automatically by the source repo's workflow on every merge to `main`:

```
ghcr.io/jbowensii/aios-face:latest   <- what the template uses
ghcr.io/jbowensii/aios-face:X.Y.Z    <- pinned release
```

You do not build anything. Unraid pulls the image and shows **update available** in the
Docker tab when a new `latest` is published.

## Install the template on Unraid

Docker tab → `>_` terminal (or SSH):

```bash
wget -O /boot/config/plugins/dockerMan/templates-user/my-aios-face.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/aios-face/aios-face.xml
```

Then **Docker → Add Container → Template → User Templates → aios-face**.

## Before you click Apply

1. **LibreChat agent.** In LibreChat create a saved agent with a tool-capable model and only
   the tools you want (a dozen or two, not hundreds; oversized tool sets exceed a 7B model's
   context and the agent goes silent). Note its id (`agent_...`).
2. **LibreChat API key.** Settings → Data & Privacy → Agent API Keys → Create. Paste it into
   the template's masked field. It acts as your LibreChat user, so keep the container LAN-only.
3. **Voice services (optional).** Point `STT_URL` / `TTS_URL` at your speaches and kokoro
   base URLs (the `/v1` ones). Make sure the STT model named in `STT_MODEL` is installed on
   speaches (`POST /v1/models/<id>` downloads it).
4. **Image bridge (optional).** `IMAGEGEN_MCP_URL` is the bridge's `/mcp` endpoint.
5. **Unraid metrics (optional).** Settings → Management Access → API Keys → create a
   read-only key; set `UNRAID_GRAPHQL_URL` to `http://<tower-ip>/graphql`.

## HTTPS is required for the microphone

Browsers only grant microphone access on `https://` (or `localhost`). Put the container behind
your reverse proxy with a valid certificate (for example NGINX Proxy Manager with a wildcard
LAN cert) and open it by that hostname. Enable **Websockets Support** and raise proxy read /
send timeouts to at least 900 s so 3D generation can finish. Over plain `http://TOWER-IP:3055`
everything except the mic works.

## Using it

- Click **INITIALIZE** on the boot screen.
- Type, or click the mic and talk. With the **ear** toggle on (default) the face keeps
  listening whenever it is idle and sends whatever you say after a short pause.
- `/image <prompt>` generates a picture; `/edit <instructions>` and `/3d` (or "turn it into a
  3D model") act on the last image. Natural phrasing like "draw me a castle" also works.
- Tool calls the agent makes show up as small wrench chips above its reply.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Reply bubble says *JARVIS returned nothing* | The agent's tool definitions exceed its context window. Remove tools in LibreChat (each MCP meta-tool with a huge schema is the usual culprit). |
| *LibreChat 401* | Wrong or revoked Agent API key. |
| Mic button does nothing | Not on HTTPS, or the browser denied the microphone. |
| Speech takes 10+ seconds | A large whisper model on CPU. Use `Systran/faster-whisper-small`. |
| `/3d` fails with CUDA out of memory | Another app (InvokeAI, Ollama) is holding VRAM on the ComfyUI host. Free it and retry. |
| Metrics show *TOWER // OFFLINE* | `UNRAID_API_KEY` blank or invalid; the rest of the app still works. |
