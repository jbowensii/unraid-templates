# PeriodiCool (Tower05, inside GluetunVPN)

Magazine manager (`ghcr.io/electrichobo/periodicool`). Runs on `container:GluetunVPN`; port 8383 is published on GluetunVPN.
Library: Tower04 `cloudfiles/magazines`. Downloads: `/mnt/user/downloads` → `/data` (same as SABnzbd).
Prowlarr `http://127.0.0.1:9696`, SABnzbd `127.0.0.1:8080` category `magazines`.
`PROWLARR_API_KEY` / `SABNZBD_API_KEY` are left empty here; fill them only in the tower's local copy.
Local URL: https://periodicool.local.owenshomeonline.com
