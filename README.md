# unraid-templates

Self-maintained [Unraid](https://unraid.net/) Community Applications Docker templates.

Currently ships one template:

| Template | Image | Notes |
|---|---|---|
| **MediaWiki** | official [`mediawiki`](https://hub.docker.com/_/mediawiki) | Runs the newest stable MediaWiki (`stable` = 1.46; `lts` = 1.43). |

## Why the official image (not the old CA one)

The existing Community Applications MediaWiki template (`d8sychain`) ships MediaWiki **1.33 (2019, end-of-life)**. This template uses the **official, actively-maintained** `mediawiki` Docker image instead — current stable **1.46**, LTS **1.43.9**.

## Install on Unraid (personal use)

Unraid removed the old "private template repositories" feature from Community Applications, so drop the XML into the user-templates folder directly:

```bash
wget -O /boot/config/plugins/dockerMan/templates-user/my-mediawiki.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/mediawiki.xml
```

It then appears under **Docker → Add Container → User Templates → MediaWiki**.

## First run (important)

The official image has **no env-based DB config** — you complete a short web wizard that generates `LocalSettings.php`. And bind-mounting that file *before it exists* makes Docker create a **folder** by that name, which breaks the wiki. So:

1. Leave the **LocalSettings.php** path **blank** on the first start.
2. Open the WebUI, complete the setup wizard (pick SQLite or point it at MariaDB).
3. Download the generated `LocalSettings.php`, save it to appdata (e.g. `/mnt/user/appdata/<wiki>/LocalSettings.php`).
4. Set the LocalSettings.php path on the container → restart.

## Recommended extensions (user-friendly editing + bells and whistles)

Most of what makes MediaWiki pleasant is **already bundled in the image** — you just enable it in `LocalSettings.php`:

**Bundled (enable with `wfLoadExtension('...')`):**

| Extension | What it does |
|---|---|
| **VisualEditor** | WYSIWYG, Word-like editing — the big one. Uses the built-in Parsoid. |
| **WikiEditor** | Enhanced wikitext toolbar. |
| **CodeMirror** | Live syntax highlighting while editing wikitext. |
| **Cite** | Footnotes / `<ref>` references and reference lists. |
| **ParserFunctions** | Conditional logic / string functions in templates. |
| **Scribunto** | Lua modules for advanced templates. |
| **TemplateData** | Structured template docs (drives VisualEditor's template dialog). |
| **SyntaxHighlight** | Colour-coded code blocks for hundreds of languages. |
| **ImageMap / InputBox / Poem / PdfHandler** | Clickable image regions, search/create boxes, poems, PDF thumbnails. |
| **ConfirmEdit** | CAPTCHA (mostly moot on an SSO-locked wiki). |

**Not bundled — REQUIRED for silvesti, baked into the image by the [`Dockerfile`](./Dockerfile):**

| Extension | What it does | Install |
|---|---|---|
| **Variables** | `{{#var}}`/`{{#vardefine}}` dynamic variables. **Required** — silvesti's templates use it 65× and its content 268×; the wiki breaks without it. | git clone (in Dockerfile) |
| **PluggableAuth** + **OpenID Connect** | SSO against Authelia (see below). | Composer (in Dockerfile) |

**Optional extras (only if a wiki actually needs them):**

| Extension | What it does | Install |
|---|---|---|
| **Mermaid** | Flowcharts / sequence / gantt diagrams from code fences. | git clone |
| **Charts** (or legacy **Graph**) | Data-driven charts. Charts is Wikimedia's 2025 replacement for the deprecated Graph. | git clone / Composer |
| **Math** | LaTeX math rendering. | git clone |
| **Maps** | Leaflet maps. | Composer |
| **Page Forms** + **Semantic MediaWiki** | Form-based editing + structured/queryable data. | Composer |

Tables need nothing extra — VisualEditor edits native wikitables directly.

### Baking in the required extensions (the repeatable way)

The [`Dockerfile`](./Dockerfile) here builds `FROM mediawiki:1.43` and adds **Variables**, **PluggableAuth** and **OpenIDConnect**, so they survive image updates instead of living in a mapped volume:

```bash
docker build -t ghcr.io/jbowensii/mediawiki-silvesti:latest .
docker push  ghcr.io/jbowensii/mediawiki-silvesti:latest
```

Then set the Unraid template's **Repository** to `ghcr.io/jbowensii/mediawiki-silvesti:latest`. (For a quick test you can instead run the stock `mediawiki:stable` and `git clone` Variables into a mapped Extensions volume, but the image is cleaner for production.)

## Authelia SSO — public read, edit only for the "wiki" group

silvesti's model: **anyone can READ; nobody can self-register; the only way to log in and edit is via Authelia, and edit rights are granted only to members of the approved AD/Authelia `wiki` group.**

1. **Authelia** — add an OIDC client in `configuration.yml` under `identity_providers.oidc.clients` (client_id `mediawiki-silvesti`, hashed secret, redirect URI `https://silvesti.wiki/index.php/Special:PluggableAuthLogin`, scopes `openid email profile groups`). Put the approved editors in an AD/Authelia group named **`wiki`** and include it in this client's `groups` claim.
2. **MediaWiki `LocalSettings.php`** — see [`LocalSettings.example.php`](./LocalSettings.example.php) for the full block. The key permissions:

```php
$wgGroupPermissions['*']['read']          = true;    // public read
$wgGroupPermissions['*']['edit']          = false;
$wgGroupPermissions['*']['createaccount'] = false;   // no self-registration
$wgGroupPermissions['*']['autocreateaccount'] = true;// OIDC provisions accounts on Authelia login
$wgGroupPermissions['user']['edit']       = false;   // logged in ≠ editor
$wgGroupPermissions['wiki']['edit']       = true;    // only the "wiki" group edits
$wgGroupPermissions['wiki']['upload']     = true;
```

Map the Authelia `wiki` group → the MediaWiki `wiki` group via the OIDC `groups` claim (recent OpenIDConnect builds), or, for a small editor list, promote each editor once after their first login with `php maintenance/createAndPromote.php --group=wiki "Username"`.

> Because **read is public**, do **not** put Authelia forward-auth in front of the whole site at NPM — that would block anonymous readers. Access control lives entirely in the permission rules above.

## MariaDB (shared server, one DB per wiki)

Several low-traffic wikis can share your existing MariaDB. Create one database + user per wiki:

```sql
CREATE DATABASE silvesti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'silvesti'@'%' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON silvesti.* TO 'silvesti'@'%';
FLUSH PRIVILEGES;
```

Point the setup wizard (or `LocalSettings.php`) at `host = <mariadb-host>`, `db = silvesti`, matching user/password.

## Running several wikis

One container **per** wiki: each with its own appdata folder (`mediawiki-silvesti`, `mediawiki-<next>`), its own host port, its own database on the shared MariaDB, and its own reverse-proxy URL. First planned wiki: **`silvesti.wiki`** (external; public read, edit gated to the Authelia `wiki` group). A second will follow.

## Listing this in Community Applications (later)

CA no longer supports private template repos, but you can get this public repo indexed by CA. Their rules: a real GitHub account with history, a quality template, an icon, a support link, and templates that aren't fully machine-generated boilerplate. Keep this repo tidy (README, LICENSE, working template) and submit it per the current CA process.

## License

MIT — see [LICENSE](./LICENSE).
