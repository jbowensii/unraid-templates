# MusicBrainz Picard (Tower01)

`mikenye/picard` (Picard 2.13.3) with a browser UI on port 5800. `/storage` is the music share.

Used as the second identification pass for music Lidarr can't identify: beets → Picard → Lidarr Manual Import → `_to_delete`.
Driven by script via Picard's remote commands, run as the container's `app` user with its XDG environment:

```
docker exec -u app -e HOME=/config -e DISPLAY=:0 -e XDG_RUNTIME_DIR=/tmp/run/user/app \
  -e XDG_CONFIG_HOME=/config/xdg/config MusicBrainz-Picard picard -e "LOAD /storage/..." -e CLUSTER ...
```

Running `picard -e` as root starts a second instance and hangs instead of talking to the running one.
