# unraid-templates

Self-maintained [Unraid](https://unraid.net/) Community Applications Docker templates.

| Template | Image | Notes |
|---|---|---|
| **[MediaWiki](./mediawiki.xml)** | official [`mediawiki`](https://hub.docker.com/_/mediawiki) | Stock MediaWiki, nothing added. `stable` = 1.46, `lts` = 1.43. Bring your own database. |
| **[mediawiki-plus](./mediawiki-plus/)** | [`ghcr.io/jbowensii/mediawiki-plus`](https://github.com/jbowensii/unraid-templates/pkgs/container/mediawiki-plus) | MediaWiki 1.43 LTS **plus** the Variables, PluggableAuth and OpenID Connect extensions. Self-contained on SQLite — no database container. |

## Which one do I want?

Start with **mediawiki-plus** unless you have a reason not to. It is the same official
MediaWiki, built on top of, with the three most commonly-missed extensions already
inside the image and a working SQLite database out of the box. Its full setup guide
lives in [`mediawiki-plus/README.md`](./mediawiki-plus/README.md).

Use the plain **MediaWiki** template if you want the untouched upstream image and
intend to point it at your own MySQL/MariaDB server.

## Why these and not the old Community Applications template

The existing CA MediaWiki template (`d8sychain`) ships MediaWiki **1.33 (2019, end of
life)**. These templates use the **official, actively-maintained** `mediawiki` image
instead — current stable **1.46**, LTS **1.43**.

## Install a template on Unraid

Unraid 7.x removed Community Applications' private-template-repository feature, so
drop the XML straight into the user-templates folder. Run in the Unraid web terminal
(**Docker** tab → `>_`) or over SSH:

```bash
# mediawiki-plus (recommended)
wget -O /boot/config/plugins/dockerMan/templates-user/my-mediawiki-plus.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/mediawiki-plus/mediawiki-plus.xml

# stock MediaWiki
wget -O /boot/config/plugins/dockerMan/templates-user/my-mediawiki.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/mediawiki.xml
```

They then appear under **Docker → Add Container → Template → User Templates**.

## First run (important, both templates)

The official image has **no environment-based database configuration**, so a new wiki
must be initialised once. Bind-mounting `LocalSettings.php` *before that file exists*
makes Docker create a **directory** by that name, which breaks the wiki. So in both
cases: **leave the LocalSettings.php field blank on the first start.**

- **mediawiki-plus** — run `maintenance/install.php` once from the container console,
  then mount the supplied config. Step-by-step in
  [`mediawiki-plus/README.md`](./mediawiki-plus/README.md).
- **MediaWiki** — open the WebUI, complete the web setup wizard, download the
  generated `LocalSettings.php` to appdata, then set the path and restart.

## Extensions

Most of what makes MediaWiki pleasant is **already bundled in the official image** —
you just enable it with `wfLoadExtension()`:

| Extension | What it does |
|---|---|
| **VisualEditor** | WYSIWYG, Word-like editing. Uses the built-in Parsoid. |
| **WikiEditor** | Enhanced wikitext toolbar. |
| **CodeMirror** | Live syntax highlighting while editing wikitext. |
| **Cite** | Footnotes / `<ref>` references and reference lists. |
| **ParserFunctions** | Conditional logic and string functions in templates. |
| **Scribunto** | Lua modules for advanced templates. |
| **TemplateData** | Structured template docs (drives VisualEditor's template dialog). |
| **SyntaxHighlight** | Colour-coded code blocks. |
| **ImageMap / InputBox / Poem / PdfHandler** | Clickable image regions, search/create boxes, poems, PDF thumbnails. |
| **ConfirmEdit** | CAPTCHA. |

Three commonly-wanted extensions are **not** bundled upstream. `mediawiki-plus` bakes
them into its image so they survive image updates instead of living in a mapped volume:

| Extension | What it does |
|---|---|
| **Variables** | `{{#var}}` / `{{#vardefine}}` dynamic variables. Template-heavy wikis do not render correctly without it. |
| **PluggableAuth** + **OpenID Connect** | Optional SSO against any OIDC provider. Off by default. |

Other extensions worth knowing about, none of which are included here: **Mermaid**
(diagrams), **Charts** (Wikimedia's 2025 replacement for the deprecated Graph),
**Math** (LaTeX), **Maps** (Leaflet), **Page Forms** + **Semantic MediaWiki**
(form-based editing and structured data). Tables need nothing extra — VisualEditor
edits native wikitables directly.

## Single sign-on (OIDC)

`mediawiki-plus` ships PluggableAuth and OpenIDConnect, disabled. Enable them by
uncommenting the OIDC block in
[`mediawiki-plus/LocalSettings.example.php`](./mediawiki-plus/LocalSettings.example.php)
and filling in your provider's issuer URL, client ID and secret. Register a
confidential client in your identity provider with redirect URI
`<your site URL>/index.php/Special:PluggableAuthLogin` and scopes
`openid email profile groups`.

A common permission model — public read, no self-registration, editing restricted to
one group:

```php
$wgGroupPermissions['*']['read']              = true;   // public read
$wgGroupPermissions['*']['edit']              = false;
$wgGroupPermissions['*']['createaccount']     = false;  // no self-registration
$wgGroupPermissions['*']['autocreateaccount'] = true;   // OIDC provisions accounts
$wgGroupPermissions['user']['edit']           = false;  // logged in != editor
$wgGroupPermissions['editor']['edit']         = true;   // only this group edits
$wgGroupPermissions['editor']['upload']       = true;
```

Map your provider's group to the MediaWiki group via the `groups` claim, or promote
each editor once after their first login:
`php maintenance/createAndPromote.php --group=editor "Their Username"`.

> If read is public, do **not** put forward-auth in front of the whole site at your
> reverse proxy — that blocks anonymous readers. Access control belongs in the
> permission rules above.

## MariaDB (shared server, one database per wiki)

Several low-traffic wikis can share one MariaDB. Create one database and user per wiki:

```sql
CREATE DATABASE mywiki CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mywiki'@'%' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON mywiki.* TO 'mywiki'@'%';
FLUSH PRIVILEGES;
```

Point the setup wizard (or `LocalSettings.php`) at that host, database, user and
password. See [`LocalSettings.example.php`](./LocalSettings.example.php) for a full
MySQL/MariaDB reference config.

`mediawiki-plus` does not need this — it uses an integrated SQLite database by default.

## Running several wikis

One container per wiki: its own appdata folder, its own host port, its own database
(or its own SQLite file), and its own reverse-proxy URL.

## Reverse proxy note

Both templates default to bridge networking on host port 8080. If your reverse proxy
runs as an **ipvlan** container on a custom Docker network, it cannot reach published
bridge ports on its own host — that is inherent to ipvlan. In that case put the wiki on
the same custom network with its own static IP and proxy to that IP on port 80.

## Building the mediawiki-plus image yourself

You should not need to — [`.github/workflows/mediawiki-plus.yml`](./.github/workflows/mediawiki-plus.yml)
builds and publishes it to GHCR whenever
[`mediawiki-plus/Dockerfile`](./mediawiki-plus/Dockerfile) changes, and the template
pulls the published image. If you want your own copy:

```bash
docker build -t ghcr.io/<you>/mediawiki-plus:1.43 ./mediawiki-plus
docker push  ghcr.io/<you>/mediawiki-plus:1.43
```

Then point the template's `Repository` at your image. Note that a **bare local tag**
with no registry path cannot work: Unraid's Docker cleanup prunes an unused local
image, and the next Apply fails with `pull access denied`.

## Listing in Community Applications (later)

CA no longer supports private template repos, but a public repo can be indexed by CA.
Their rules: a real GitHub account with history, a quality template, an icon, a support
link, and templates that are not machine-generated boilerplate.

## License

MIT — see [LICENSE](./LICENSE).
