# librechat-jarvis on Unraid

LibreChat with the JARVIS face: a pinned LibreChat release plus a small overlay (boot screen and
startup sound, blue HUD theme, JARVIS status bar and gear, reactor / ring HUD panel, hands-free
listening, bundled kokoro voice packs). Everything else is stock LibreChat and your existing
configuration is used unchanged. Source and build: <https://github.com/jbowensii/librechat-jarvis>.
Image: `ghcr.io/jbowensii/librechat-jarvis:latest` (Unraid shows updates in the Docker tab).

This container **replaces the `api` container** of an existing LibreChat docker-compose stack.
The rest of that stack (MongoDB, Meilisearch, RAG API, pgvector) must already be running.

## Two ways to run it

**A. Keep your compose stack (recommended if LibreChat is compose-managed).** Edit the `api`
service in `docker-compose.yml`:

```yaml
  api:
    container_name: librechat-jarvis
    image: ghcr.io/jbowensii/librechat-jarvis:latest
    labels:
      - "net.unraid.docker.icon=https://raw.githubusercontent.com/jbowensii/unraid-templates/main/librechat-jarvis/icon.png"
      - "net.unraid.docker.webui=https://jarvis.example.lan"
```

then `docker compose up -d api`. Nothing else in the stack changes.

**B. This template (LibreChat's supporting services already running, `api` stopped).**

1. Install the template (Unraid 7 has no private template repos; use the web terminal):

   ```bash
   wget -O /boot/config/plugins/dockerMan/templates-user/my-librechat-jarvis.xml \
     https://raw.githubusercontent.com/jbowensii/unraid-templates/main/librechat-jarvis/librechat-jarvis.xml
   ```

2. Docker tab → **Add Container** → pick `librechat-jarvis` from the template list.
3. Set **Network** to the network your LibreChat stack uses (default `librechat_default`), so
   `mongodb`, `meilisearch` and `rag_api` resolve by name.
4. Point the path fields at your LibreChat appdata (`.env`, `librechat.yaml`, images, uploads,
   logs, data). If the compose stack used the named volume `librechat_librechat-data`, copy it once:
   `docker run --rm -v librechat_librechat-data:/from -v /mnt/user/appdata/librechat/data:/to alpine cp -a /from/. /to/`
5. Set `DOMAIN_CLIENT` and `DOMAIN_SERVER` to the https hostname you will open it on.
6. Apply. Open the URL: boot screen → INITIALIZE → LibreChat login → JARVIS shell.

`--user 0:0` is in Extra Parameters because the upstream image runs as `node` and cannot write to
appdata folders owned by `nobody:users`; the standard LibreChat compose on Unraid runs as root too.

## Voice

Speech is LibreChat's: add a `speech:` block to `librechat.yaml` for your STT and TTS servers
(speaches and kokoro-fastapi work; private-IP targets must be listed under `allowedAddresses`).
Voice choice is in LibreChat **Settings → Speech**.

The JARVIS kokoro voice packs ship inside the image. Install them into kokoro once:

```bash
docker cp librechat-jarvis:/app/voices/kokoro/. /mnt/user/appdata/kokoro-voices/
for f in /mnt/user/appdata/kokoro-voices/*.pt; do docker cp "$f" kokoro-fastapi:/app/api/src/voices/v1_0/; done
```

kokoro keeps them in its writable layer, so repeat the second line after a kokoro update.

Hands-free listening is the **MIC** button in the JARVIS bar. It needs Speech-to-Text on with the
external engine in Settings → Speech, and an https origin for the microphone.

## Upgrading

Unraid's update check follows `ghcr.io/jbowensii/librechat-jarvis:latest`. Each image is built from
the LibreChat tag in the repo's `upstream.lock`; the version in the JARVIS bar reads
`<librechat tag>+<overlay commit>`.
