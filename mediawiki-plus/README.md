# mediawiki-plus

An Unraid template for **MediaWiki 1.43 LTS** with three extensions baked into the
image that the official upstream image does not bundle:

| Extension | What it gives you |
|---|---|
| **Variables** | `{{#var}}` / `{{#vardefine}}` dynamic variables. Template-heavy wikis break without it. |
| **PluggableAuth** | Pluggable authentication framework. Off by default. |
| **OpenID Connect** | SSO against any OIDC provider (Authelia, Keycloak, Authentik, Entra ID). Off by default. |

Everything else people usually want — VisualEditor, WikiEditor, CodeEditor, Cite,
ParserFunctions, Scribunto, TemplateData, SyntaxHighlight, PdfHandler and friends —
is already in the official image and is switched on for you by the example config.

The wiki is **self-contained**: it uses an integrated SQLite database in a mapped
folder, so there is no second container to run. An external MySQL/MariaDB server is
supported if you prefer one.

## Image

Published automatically from [`Dockerfile`](./Dockerfile) by
[`.github/workflows/mediawiki-plus.yml`](../.github/workflows/mediawiki-plus.yml):

```
ghcr.io/jbowensii/mediawiki-plus:1.43
ghcr.io/jbowensii/mediawiki-plus:latest
```

You do not need to build anything — Unraid pulls the published image like any other
container.

> **Why a published image and not a local build?** An Unraid template does not build
> anything; it runs `docker run` against whatever is in its `Repository` field. A bare
> local tag with no registry path can never be pulled, and Unraid's Docker cleanup
> prunes it as soon as no container is using it — after which deploying the template
> fails with `pull access denied`. Publishing to a registry is the fix.

## Install the template on Unraid

Unraid 7.x removed Community Applications' private-template-repository feature, so
drop the XML straight into the user-templates folder. Run this in the Unraid web
terminal (**Docker** tab → `>_` icon) or over SSH:

```bash
wget -O /boot/config/plugins/dockerMan/templates-user/my-mediawiki-plus.xml \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/mediawiki-plus/mediawiki-plus.xml
```

It then appears under **Docker → Add Container → Template → User Templates →
mediawiki-plus**.

## First run — the one-time bootstrap

The official MediaWiki image has **no environment-based database setup**. A brand new
wiki has to be initialised once before it will serve pages. No template can skip this
step. It takes about a minute.

### 1. Create the container

In **Add Container**, fill in:

| Field | Value |
|---|---|
| **Site Name** | What your wiki is called, e.g. `My Project Wiki` |
| **Site Server URL** | The URL people will visit, no trailing slash. `https://wiki.example.com` if you use a reverse proxy, otherwise `http://<your-unraid-ip>:8080` |
| **Wiki Admin User** | `Admin` is fine |
| **Wiki Admin Password** | **At least 10 characters.** MediaWiki rejects shorter ones |
| **LocalSettings.php** | **Leave completely empty** |

Then click **Apply**.

> **Why must LocalSettings.php start blank?** Docker bind-mounts create whatever is
> missing. If you point the mount at a file that does not exist yet, Docker creates a
> **directory** with that name, and MediaWiki then fails because it cannot read its
> config. Blank first, mount second.

At this point the WebUI shows MediaWiki's "please set up the wiki" installer page.
That is expected — you are about to do it from the command line instead, which is
faster and repeatable.

### 2. Run the schema install

Open a console on the container (**Docker** tab → click the `mediawiki-plus` icon →
**Console**) and paste:

```bash
php maintenance/install.php \
  --dbtype=sqlite \
  --dbpath=/var/www/html/data \
  --dbname="$MW_DB_NAME" \
  --server="$MW_SITE_SERVER" \
  --scriptpath="" \
  --lang=en \
  --pass="$MW_ADMIN_PASSWORD" \
  "$MW_SITE_NAME" "$MW_ADMIN_USER"
```

It reads the values you typed into the template, creates the SQLite database in the
mapped Database folder, creates your admin account, and writes a starter
`LocalSettings.php` inside the container. Expect it to finish with
`Done. Enjoy your new wiki!`.

If it complains the password is too short, raise it in the template and re-run.

**Now hand the files to the web server — do not skip this:**

```bash
chown -R www-data:www-data /var/www/html/data /var/www/html/images
```

The console runs as `root`, so `install.php` creates the database files owned by
`root` with mode `0600`. Apache serves as `www-data` and cannot open them. Command-line
maintenance scripts keep working, so the wiki looks fine until you load a page in a
browser and get **`Cannot access the database: No database connection (localhost)`** —
a misleading message, since nothing is wrong with the database itself. The `chown`
fixes it permanently; you only need it this once.

### 3. Install the real config

Copy [`LocalSettings.example.php`](./LocalSettings.example.php) into your appdata
folder. From the Unraid terminal (not the container console):

```bash
wget -O /mnt/user/appdata/mediawiki-plus/LocalSettings.php \
  https://raw.githubusercontent.com/jbowensii/unraid-templates/main/mediawiki-plus/LocalSettings.example.php
```

Then **Edit** the container, set the **LocalSettings.php** field to
`/mnt/user/appdata/mediawiki-plus/LocalSettings.php`, and **Apply**.

This config reads the template's variables at runtime, so the same file works for
every wiki you deploy — there is nothing site-specific to edit unless you want to
change behaviour.

### 4. Register the extensions

Back in the container console:

```bash
php maintenance/update.php --quick
```

Open the WebUI. The wiki is live, and `Special:Version` lists Variables alongside the
bundled extensions.

## Optional: single sign-on

PluggableAuth and OpenIDConnect are in the image but disabled. To turn them on, edit
your `LocalSettings.php` and uncomment the OIDC block near the bottom, filling in your
provider's issuer URL, client ID and client secret.

In your identity provider, register a **confidential** OIDC client with:

- **Redirect URI** — `<your Site Server URL>/index.php/Special:PluggableAuthLogin`
- **Scopes** — `openid`, `email`, `profile`, and `groups` if you want group mapping

Then run `php maintenance/update.php --quick` again.

`$wgPluggableAuth_EnableLocalLogin = false` hides the local password form entirely. Be
sure SSO works before you set that, or you will lock yourself out. If you do, set it
back to `true`, restart, and log in as the local admin.

## Reverse proxy note

The template defaults to **bridge networking on host port 8080**, which suits most
setups: point your proxy at `http://<unraid-ip>:8080`.

One exception worth knowing: if your reverse proxy runs as an **ipvlan** container on
a custom Docker network, it cannot reach published bridge ports on its own host — that
is a property of ipvlan, not a misconfiguration. In that case set this container's
**Network** to the same custom network and give it its own static IP, then point the
proxy at that IP on port 80.

## Running several wikis

One container per wiki. Give each its own container name, its own appdata folder, its
own host port, and its own Site Server URL. With SQLite each wiki is fully independent
— nothing is shared.

## Upgrading

The `:1.43` tag tracks the 1.43 LTS line. When you pull a newer image, run
`php maintenance/update.php --quick` once afterwards to apply any schema changes.
Back up your appdata folder first.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `pull access denied` on Apply | The `Repository` field points at a local tag with no registry path. It must be `ghcr.io/jbowensii/mediawiki-plus:1.43`. |
| Wiki shows the setup wizard forever | Step 2 has not been run, or `LocalSettings.php` is mounted as a directory. Check with `ls -l /var/www/html/LocalSettings.php` in the container console; if it is a directory, stop the container, delete it, and blank the template field. |
| `Cannot access the database: No database connection (localhost)` in the browser, while console commands work | The SQLite files are owned by `root` and Apache runs as `www-data`. Run the `chown` at the end of step 2. |
| `Could not find a suitable database driver` | The Database (SQLite) path is not mapped, or `--dbpath` in step 2 did not match it. |
| `<extension> requires <other> to be installed` | An extension in `LocalSettings.php` has a dependency that is not loaded. `DiscussionTools` needs `Linter`, for example. |
| `Unable to open file .../extension.json` | `LocalSettings.php` loads an extension that is not in the image. Check `ls /var/www/html/extensions` in the container console — a missing extension is a fatal error, not a warning. |
| Uploads fail | The Uploads path is not mapped, or the folder is not writable by the container. |
| Extensions missing from `Special:Version` | Step 4 was skipped. Run `php maintenance/update.php --quick`. |

## License

MIT — see [LICENSE](../LICENSE).
