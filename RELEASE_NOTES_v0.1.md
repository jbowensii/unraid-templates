# v0.1 — MediaWiki (official image) Unraid template

First release. A self-maintained Unraid Community Applications template that runs the **official, actively-maintained** [`mediawiki`](https://hub.docker.com/_/mediawiki) Docker image (current stable **1.46**, LTS **1.43**) instead of the old CA template that ships end-of-life MediaWiki 1.33.

## What's included
- **`mediawiki.xml`** — the Unraid template (ports, appdata volumes, LocalSettings.php mount, optional Extensions/Skins volumes, PHP upload override).
- **`Dockerfile`** — optional extended image that bakes in the non-bundled extensions **Variables**, **PluggableAuth**, and **OpenID Connect** (for `#var` templates and Authelia/OIDC SSO).
- **`LocalSettings.example.php`** — a reference config for a public-read, SSO-gated wiki.
- **`README.md`** — full walkthrough (first-run, extensions, Authelia SSO, shared MariaDB, multi-wiki).

## Install
Drop the template into Unraid's user-templates folder (Unraid 7.x has no "add template by URL" field — this is the supported path since CA removed private template repos). Run in the Unraid web terminal (**Docker → `>_`**) or via SSH:

```bash
wget -O /boot/config/plugins/dockerMan/templates-user/my-mediawiki.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/mediawiki.xml
```

It then appears under **Docker → Add Container → Template → User Templates → MediaWiki**.

## First run (important)
The official image has no env-based DB config — you complete a short web wizard that writes `LocalSettings.php`, and bind-mounting that file *before it exists* makes Docker create a folder by that name. So:

1. Leave the **LocalSettings.php** path **blank** on the first start.
2. Open the WebUI, complete the setup wizard (SQLite, or point it at MySQL/MariaDB).
3. Save the generated `LocalSettings.php` into appdata and set the container's LocalSettings.php path to it, then restart.

## Verified
Smoke-tested on **Unraid 7.3.2**:
- Template installs via the `wget` above and appears under **User Templates**.
- Selecting it auto-populates the Add Container form (name, overview, repository, port, appdata paths); container builds and starts from `mediawiki:stable`.
- WebUI serves **MediaWiki 1.46.0**; the blank-LocalSettings first-run behavior is correct.
- Installer environment checks all pass — **PHP 8.3.33**, ICU 76.1, **ImageMagick** (image thumbnailing), **Git** (in-image extension installs). "You can install MediaWiki."

## Notes
- Several low-traffic wikis can share one MariaDB (one DB per wiki) — see the README.
- To bake in Variables + PluggableAuth + OpenID Connect, build the included `Dockerfile` to your own registry and point the template's Repository at that image.
