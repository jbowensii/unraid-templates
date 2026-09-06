# v0.2 — mediawiki-plus (extended image) + published container

Adds a second template, **`mediawiki-plus`**: the official MediaWiki 1.43 LTS image extended with three extensions that upstream does not bundle, published as a real container image so Unraid pulls it like anything else.

## What's new

- **`mediawiki-plus/mediawiki-plus.xml`** — the Unraid template. Bridge networking on host port 8080, integrated SQLite database (no second container), and masked variables for the admin password and the MediaWiki secret/upgrade keys.
- **`mediawiki-plus/Dockerfile`** — `FROM mediawiki:1.43` plus **Variables**, **PluggableAuth** and **OpenID Connect**, all cloned at `REL1_43` with their composer dependencies installed.
- **`.github/workflows/mediawiki-plus.yml`** — builds and pushes `:1.43` and `:latest` to GHCR whenever the Dockerfile changes, using the workflow's own `GITHUB_TOKEN`. No personal access token needed.
- **`mediawiki-plus/LocalSettings.example.php`** — a generic, environment-driven config. It reads the template's variables at runtime, so the same file works for every wiki you deploy without editing.
- **`mediawiki-plus/README.md`** — install, the one-time bootstrap, SSO, reverse-proxy guidance and troubleshooting.

## The image

```
ghcr.io/jbowensii/mediawiki-plus:1.43
ghcr.io/jbowensii/mediawiki-plus:latest
```

Public, no authentication needed to pull.

## Why an image and not a local build

An Unraid template does not build anything — it runs `docker run` against whatever is in its `Repository` field. A bare local tag with no registry path can never be pulled, and Unraid's Docker cleanup prunes it as soon as no container is using it, after which the next **Apply** fails with `pull access denied`. (This was observed happening during testing.) Publishing to a registry is the fix.

## Install

```bash
wget -O /boot/config/plugins/dockerMan/templates-user/my-mediawiki-plus.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/mediawiki-plus/mediawiki-plus.xml
```

Then **Docker → Add Container → Template → User Templates → mediawiki-plus**.

## First run

The official image has no environment-based database setup, so a new wiki needs a one-time bootstrap. Full walkthrough in [`mediawiki-plus/README.md`](./mediawiki-plus/README.md). In short: start with the `LocalSettings.php` field blank, run `maintenance/install.php` from the container console, `chown -R www-data:www-data` the data and images folders, mount the config, then run `maintenance/update.php`.

## Verified

Tested on **Unraid 7.3.x**, from a clean slate with the local image deleted first so the pull came from GHCR:

- Anonymous pull with no `docker login` and no stored credentials.
- MediaWiki **1.43.9**; Variables, PluggableAuth and OpenIDConnect all present in the image.
- Bootstrap, config mount and `update.php` complete; `Main_Page` and `Special:Version` both return **HTTP 200** with 32 extensions loaded.
- Functional checks: `{{#vardefine:g|X}}{{#var:g}}` returns `X`, and `{{#expr:6*7}}` returns `42`.
- PluggableAuth and OpenIDConnect correctly stay unloaded until enabled — they are opt-in.

## Also in this release

- **Site-specific values removed from the shared files.** The v0.1 `README.md` and `LocalSettings.example.php` were written against one private deployment and named its wiki hostname, an OIDC issuer on a personal domain, and a database server's LAN address. All replaced with `example.com` placeholders. Note that the git history still contains the previous values.
- **Root `Dockerfile` removed.** It was a second, diverging recipe for the same image — installing PluggableAuth and OpenIDConnect from Packagist rather than cloning `REL1_43` — and was never the version that was built and verified. It remains in the `v0.1` tag.
- **`README.md` rewritten** to cover both templates and point at the published image.
- **`MW_SITE_NAME` added.** The template previously had no site-name variable, so a wiki deployed from it came up called "MediaWiki" with no supported way to change it.

## Fixes found during verification

Three defects that would have affected every user, each caught by running the documented procedure rather than reading it:

1. The example config loaded **CodeMirror**, which is not in the official 1.43 image (neither is CharInsert). A missing extension is a fatal error, so the wiki would not start at all. The extension list is now built from the image's actual contents, with `CodeEditor` in its place.
2. **DiscussionTools requires Linter**, which had been deliberately left off. Now loaded.
3. **`Cannot access the database: No database connection (localhost)`** on every page while `install.php` and `update.php` both reported success. The console runs as `root`, so the SQLite files are created `root:root` mode `0600`, and Apache runs as `www-data`. Command-line maintenance keeps working throughout, which makes it hard to diagnose, and the error text points at the wrong subsystem. The required `chown` is now an explicit documented step.

## Unchanged

The stock **MediaWiki** template (`mediawiki.xml`) from v0.1 is untouched and still works.
