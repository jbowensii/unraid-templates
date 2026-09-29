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

## Accounts (0.4.0 and later)

Sign-in is required from 0.4.0. On first start the container log shows a
one-time setup code (`docker logs chummer-anarchy2`); open the web UI and
use it to create the admin account. Existing runners move into that
account. Behind a reverse proxy, set **Trusted proxy** (every proxy hop's
IP) and **Public address** (the https URL people use). Authelia sign-in is
optional; see the app's README.

The core rules data isn't in the image: importing the core rulebook on the
Books page builds it (an upgraded server does this on first start), or the
admin uploads a data pack.

## Updating

Docker tab → the container's **apply update** link (shown when a new
`:latest` is published) pulls the new image and recreates the container.
**Edit → Apply** alone recreates the container from the image already on
the server and does *not* pull a new one. To update that way, pull first
in the web terminal, then Edit → Apply:

```bash
docker pull ghcr.io/jbowensii/chummer-anarchy2:latest
```

Your runners, edits, books and settings live in the three volumes and are
kept across updates.

## Backup and restore

In the app: Settings → **Backup**. **Download backup** saves one zip with
everything in the three volumes (API keys only if you tick "Include my API
keys"). **Restore from backup** shows what's in a zip, then replaces the
server's data; the server first saves its current state to
`Library/.backups` (the newest 3 are kept). Backups up to 20 GB can be
restored.
