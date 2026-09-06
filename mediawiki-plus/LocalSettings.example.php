<?php
# =============================================================================
# LocalSettings.example.php  --  generic, environment-driven config for the
# mediawiki-plus Unraid template (ghcr.io/jbowensii/mediawiki-plus).
#
# Copy this file into your appdata folder, e.g.
#     /mnt/user/appdata/mediawiki-plus/LocalSettings.php
# then set the container's "LocalSettings.php" path to it and restart.
#
# It reads its settings from the template's environment variables, so the same
# file works for every wiki you deploy - nothing site-specific is hard-coded.
# Put your own permanent overrides at the bottom.
#
# NOTE: this is NOT a substitute for the one-time schema bootstrap. Run
# maintenance/install.php once first (see the README), then mount this file.
# =============================================================================

if ( !defined( 'MEDIAWIKI' ) ) { exit; }

# --- Small helper: read an env var, fall back to a default -------------------
function mwp_env( $name, $default = '' ) {
	$v = getenv( $name );
	return ( $v === false || $v === '' ) ? $default : $v;
}

# =============================================================================
# IDENTITY / URLs      (template variables: MW_SITE_NAME, MW_SITE_SERVER)
# =============================================================================
$wgSitename      = mwp_env( 'MW_SITE_NAME', 'Wiki' );
$wgMetaNamespace = str_replace( ' ', '_', $wgSitename );

# Canonical URL, no trailing slash, e.g. https://wiki.example.com
$wgServer          = mwp_env( 'MW_SITE_SERVER', 'http://localhost:8080' );
$wgCanonicalServer = $wgServer;

$wgScriptPath  = '';
$wgArticlePath = '/wiki/$1';
$wgUsePathInfo = true;

# Behind a reverse proxy (NGINX Proxy Manager, Traefik, Caddy, Cloudflare...)
# this makes MediaWiki trust the forwarded client IP and the https scheme.
$wgUsePrivateIPs = true;

# =============================================================================
# DATABASE - integrated SQLite (self-contained, no database container)
#            (template variable MW_DB_NAME, mapped path "Database (SQLite)")
# =============================================================================
$wgDBtype        = 'sqlite';
$wgDBname        = mwp_env( 'MW_DB_NAME', 'wiki' );
$wgSQLiteDataDir = '/var/www/html/data';
$wgDBuser        = '';
$wgDBpassword    = '';

# --- Using an external MySQL/MariaDB instead? --------------------------------
# Comment out the five lines above, uncomment this block, and add
# MW_DB_SERVER / MW_DB_USER / MW_DB_PASSWORD as extra template variables.
#
# $wgDBtype         = 'mysql';
# $wgDBserver       = mwp_env( 'MW_DB_SERVER', 'db' );
# $wgDBname         = mwp_env( 'MW_DB_NAME', 'wiki' );
# $wgDBuser         = mwp_env( 'MW_DB_USER', 'wiki' );
# $wgDBpassword     = mwp_env( 'MW_DB_PASSWORD', '' );
# $wgDBTableOptions = 'ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4';

# =============================================================================
# SECRETS             (template variables: MW_SECRET_KEY, MW_UPGRADE_KEY)
# =============================================================================
# Leave both template fields blank and install.php generates values for you.
# Paste them into the masked template fields if you would rather pin them so
# they survive a rebuild of the appdata folder.
$wgSecretKey  = mwp_env( 'MW_SECRET_KEY',  '' );
$wgUpgradeKey = mwp_env( 'MW_UPGRADE_KEY', '' );

# =============================================================================
# UPLOADS / MEDIA               (mapped path "Uploads (images)")
# =============================================================================
$wgEnableUploads   = true;
$wgUploadDirectory = "$IP/images";
$wgMaxUploadSize   = 64 * 1024 * 1024;   # 64 MB
$wgFileExtensions  = array_merge( $wgFileExtensions,
	[ 'pdf', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'mp4', 'webm' ] );

# Locale / time
$wgLanguageCode  = 'en';
$wgLocaltimezone = mwp_env( 'TZ', 'UTC' );

# =============================================================================
# BUNDLED EXTENSIONS - already inside the official image, just switched on
# =============================================================================
wfLoadExtension( 'ParserFunctions' );        # #if / #switch / #expr
wfLoadExtension( 'VisualEditor' );           # WYSIWYG editing
wfLoadExtension( 'WikiEditor' );             # enhanced wikitext toolbar
wfLoadExtension( 'CodeMirror' );             # live syntax highlighting
wfLoadExtension( 'Cite' );                   # <ref> footnotes
wfLoadExtension( 'Scribunto' );              # Lua modules
$wgScribuntoDefaultEngine = 'luastandalone';
wfLoadExtension( 'TemplateData' );           # template docs for VisualEditor
wfLoadExtension( 'SyntaxHighlight_GeSHi' );
wfLoadExtension( 'ImageMap' );
wfLoadExtension( 'InputBox' );
wfLoadExtension( 'Poem' );
wfLoadExtension( 'CharInsert' );
wfLoadExtension( 'PdfHandler' );
wfLoadExtension( 'Gadgets' );
wfLoadExtension( 'Interwiki' );
wfLoadExtension( 'Nuke' );
wfLoadExtension( 'ConfirmEdit' );            # CAPTCHA

$wgDefaultUserOptions['visualeditor-enable'] = 1;

# =============================================================================
# NON-BUNDLED EXTENSIONS - baked into the mediawiki-plus image
# =============================================================================
wfLoadExtension( 'Variables' );              # {{#var}} / {{#vardefine}}

# =============================================================================
# OPTIONAL: SSO via OpenID Connect (Authelia, Keycloak, Authentik, Entra ID...)
# ---------------------------------------------------------------------------
# PluggableAuth and OpenIDConnect are in the image but stay OFF until you
# uncomment this block. Delete it if you only want local accounts.
#
# In your identity provider, register a confidential OIDC client whose redirect
# URI is  <your MW_SITE_SERVER>/index.php/Special:PluggableAuthLogin  and which
# returns the scopes listed below.
# =============================================================================
# wfLoadExtension( 'PluggableAuth' );
# wfLoadExtension( 'OpenIDConnect' );
#
# $wgPluggableAuth_EnableLocalLogin      = false;  # hide the password form
# $wgPluggableAuth_EnableLocalProperties = false;
# $wgPluggableAuth_ButtonLabelMessage    = 'Log in with SSO';
#
# $wgOpenIDConnect_Config['https://auth.example.com'] = [
#     'clientID'     => 'mediawiki',
#     'clientsecret' => 'CHANGE-ME',
#     'scope'        => [ 'openid', 'email', 'profile', 'groups' ],
# ];
# $wgOpenIDConnect_MigrateUsersByEmail = true;

# =============================================================================
# PERMISSIONS - default: anyone may read, logged-in users may edit, and nobody
# can create their own account. Adjust to taste; presets follow.
# =============================================================================
$wgGroupPermissions['*']['read']              = true;
$wgGroupPermissions['*']['edit']              = false;
$wgGroupPermissions['*']['createaccount']     = false;
$wgGroupPermissions['*']['autocreateaccount'] = true;   # lets SSO provision users
$wgGroupPermissions['user']['edit']           = true;

# --- Preset: fully private (readers must log in) -----------------------------
# $wgGroupPermissions['*']['read'] = false;
#
# --- Preset: public read, editing restricted to one named group --------------
# Create the group with:
#   php maintenance/createAndPromote.php --group=editor "Their Username"
# $wgGroupPermissions['user']['edit']     = false;
# $wgGroupPermissions['editor']['edit']   = true;
# $wgGroupPermissions['editor']['upload'] = true;
# $wgGroupPermissions['editor']['move']   = true;

# =============================================================================
# LOOK & FEEL
# =============================================================================
wfLoadSkin( 'Vector' );
$wgDefaultSkin = 'vector-2022';

# =============================================================================
# MISC
# =============================================================================
$wgEnableEmail          = false;   # set true and configure $wgSMTP to send mail
$wgShowExceptionDetails = false;   # true only while debugging
$wgRightsText           = '';      # e.g. 'Creative Commons Attribution'
$wgRightsUrl            = '';

# --- Your own overrides below this line --------------------------------------
