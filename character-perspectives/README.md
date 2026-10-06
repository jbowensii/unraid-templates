# Character Perspectives on Unraid

Generates character turnaround views (front, back, side, action, portrait, sitting, top-down,
downed) and a 3D model from one reference image, using a self-hosted ComfyUI or Gemini.
Source: <https://github.com/jbowensii/character-perspectives>. Image:
`ghcr.io/jbowensii/character-perspectives:latest`, rebuilt by GitHub Actions on every push
to `main`.

## Install

```bash
wget -O /boot/config/plugins/dockerMan/templates-user/my-CharacterPerspectives.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/character-perspectives/character-perspectives.xml
```

Then Docker tab → **Add Container** → pick `CharacterPerspectives` → set `COMFYUI_BASE` → Apply.

## Update

Docker tab → **Check for Updates** → **apply update** on CharacterPerspectives. It pulls the
newest `latest` image; data and config live in the mapped paths and are kept.

## Volumes

| Path | Purpose |
|---|---|
| Data | References, generated views, 3D models, saved workspaces |
| Config | `models.xml`: models, LoRAs, per-model switches, API keys (owner-only) |

## Backup and restore

In the app, **Download All Characters** gives one zip with a folder per character: the original,
every view, the 3D model, a readable `Prompts.txt` and a `character.json`. **Import Character(s)**
restores one character's folder, or the top folder to restore all of them. Copying the Data and
Config folders backs up the whole install.

Full setup guide, including the ComfyUI models and nodes it needs:
<https://github.com/jbowensii/character-perspectives#setup>
