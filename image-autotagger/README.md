# image-autotagger on Unraid

Offline AI tagging, renaming and organizing for large image collections. Tags, caption and title are embedded in each file (XMP, plus IPTC/EXIF for JPEG/WebP), so any DAM can read them. Source: <https://github.com/jbowensii/image-autotagger>. Image: `ghcr.io/jbowensii/image-autotagger:latest`, rebuilt by GitHub Actions on every push to `main`.

## Install

```bash
wget -O /boot/config/plugins/dockerMan/templates-user/my-image-autotagger.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/image-autotagger/image-autotagger.xml
```

Optional, for keyword tags: put the ONNX tagger in the Models path.

```bash
mkdir -p /mnt/user/appdata/image-autotagger/models/tagger && cd "$_"
wget -O model.onnx        https://huggingface.co/Redstonexs/kagami-24k/resolve/main/onnx/model_prob.onnx
wget -O selected_tags.csv https://huggingface.co/Redstonexs/kagami-24k/resolve/main/selected_tags.csv
```

Then Docker tab → **Add Container** → pick `image-autotagger` → set the share paths and the Ollama URL → Apply. The web UI is on port 8099.

## Update

Docker tab → **Check for Updates** → **apply update** on image-autotagger. The state DB and thumbnails live in App data and are kept. Processing resumes where it stopped.

## Volumes

| Path | Purpose |
|---|---|
| Input | Drop batches here. Originals are never written to, only moved to Complete once their tagged copy validates. |
| Output | Tagged, renamed copies: `_unsorted/` plus the folders you define in the organizer. |
| Complete | Processed originals, with their sub-folders kept. |
| Errors | Only used when Error handling is `move`. |
| App data | `state.sqlite`, thumbnail cache, optional `config.yaml`. |
| Models | `tagger/model.onnx` + `tagger/selected_tags.csv`. |

Plan about 2× the collection size in free space: Output holds a second copy, and processing pauses below **Min free GB**.
