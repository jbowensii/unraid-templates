# chummer-anarchy2 on Unraid

A character generator and career tracker for **Shadowrun: Anarchy 2.0**. The app ships the rules
engine only — no book content — you upload your own PDFs on the Books page after install. Source
and build: <https://github.com/jbowensii/chummer-anarchy2>. Image:
`ghcr.io/jbowensii/chummer-anarchy2:latest`.

## Install

1. Install the template (Unraid 7 removed the private-template-repository picker, so add the file
   directly via the web terminal):

   ```bash
   wget -O /boot/config/plugins/dockerMan/templates-user/my-chummer-anarchy2.xml \
     https://raw.githubusercontent.com/jbowensii/unraid-templates/main/chummer-anarchy2/chummer-anarchy2.xml
   ```

2. Docker tab → **Add Container** → pick `chummer-anarchy2` from the template list → Apply.
3. The web UI is on port 8480 (mapped from container port 80).

## Volumes

| Path | Purpose |
|---|---|
| Characters | Saved runners, one XML file each |
| Custom data | House-rule and extra-content XML overlays (list them in `index.xml`) |
| Library | Uploaded PDFs, converted text, import reports and AI settings (keys). Never served over HTTP |

## Plain docker / other dashboards

```bash
docker run -d --init -p 8480:80 \
  -v /path/to/characters:/data/characters \
  -v /path/to/custom:/data/custom \
  -v /path/to/library:/data/library \
  ghcr.io/jbowensii/chummer-anarchy2:latest
```
