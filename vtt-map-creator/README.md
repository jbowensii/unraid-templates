# vtt-map-creator (Unraid template)

Template for the VTT Map Creator container (`ghcr.io/jbowensii/vtt-map-creator`). It lives here in the app's repository and is
copied to `jbowensii/unraid-templates/vtt-map-creator/` (with `icon.png`) when the owner approves.

**Everyone signs in.** The first account is the owner; everyone else joins with an invite link the owner makes. Choose
**exactly one** of two ways to run it (full steps: `docs/deploy.md`):

- **LAN mode** (`VTT_LAN` = `1`, "Login proxy address" empty): people on your home network open
  `http://<container address>:8000` and sign in. Only private addresses are answered; anything else gets 403. Passwords travel
  unencrypted on your network, so use it only on a network you trust, and never port-forward it to the internet.
- **Behind a login proxy** (`VTT_LAN` empty, "Login proxy address" and "Allowed host names" set): for the internet. The app
  answers only the proxy (Nginx Proxy Manager with https, optionally Authelia in front).

With neither set, the container listens only on its own loopback and nobody can reach it. With both set, the proxy wins.

## Settings you must fill in

| Field | What to put |
|---|---|
| Network | your custom network (br0); then a fixed address for the container |
| LAN mode | `1` for your home network, or empty when you use a login proxy |
| Login proxy address | proxy only: the address the proxy connects from (empty in LAN mode) |
| Allowed host names | proxy only: the name people type in the browser (empty in LAN mode) |
| Data folder | default `/mnt/user/appdata/vtt-map-creator`; create it first (below) |

## The first start: the setup code

Until the owner account exists, the app prints a one-time **setup code** in its log at every start. In the Docker tab click the
container's **log icon**, copy the code from the line "No owner account yet. ... setup code: XXXX", open the app and create the
owner account with it. The code is also in `setup-code.txt` in the data folder; it stops working once the owner exists.

Optional: Dungeondraft packs share (read only), ComfyUI and llama-swap addresses, and a Gemini key file you create and mount
read-only in "Gemini key file" (the key is never in the template). If you mount a key file, also set "Gemini key file path"
(advanced view) to `/run/gemini-key`; leave it empty otherwise, or Gemini shows as available and every paint fails.

## Before the first start: create the data folder

The container runs as `99:100` (nobody:users, set in Extra Parameters). If the data folder does not exist yet, Docker may
create it owned by root and the app cannot write it. In the Unraid terminal, before you apply the template:

```sh
mkdir -p /mnt/user/appdata/vtt-map-creator
chown nobody:users /mnt/user/appdata/vtt-map-creator
```

If you skip this and the folder ends up owned by root, the container stops right after starting and its log shows a
`PermissionError: [Errno 13] Permission denied` about `/data`. Run the two lines above and start it again.

## Things to know

- The key file must be readable by user 99.
- In LAN mode the WebUI link in the Docker tab (`http://[IP]:8000/`) opens the sign-in page. Behind a login proxy it is always
  refused (your browser is not the proxy): open the app through the proxy's name instead.
- The image on ghcr.io is public, so Unraid pulls it with no login.
- Images are built by GitHub Actions, never on Unraid.
