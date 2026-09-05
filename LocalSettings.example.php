<?php
# =============================================================================
# LocalSettings.example.php  —  reference config for silvesti.wiki
# Official MediaWiki image on Unraid, behind Cloudflare + NGINX Proxy Manager.
#
# Auth model (per John): ANYONE can READ. No self-registration. The ONLY way to
# log in / edit is via Authelia, and edit rights are granted only to members of
# the approved AD/Authelia "wiki" group.
#
# This is NOT a drop-in file. Run the web setup wizard first to generate a real
# LocalSettings.php (it fills in $wgDBpassword, $wgSecretKey, $wgUpgradeKey…),
# then merge the marked sections below into it.
# =============================================================================

if ( !defined( 'MEDIAWIKI' ) ) { exit; }

# --- Identity / URLs ---------------------------------------------------------
$wgSitename        = "Silvesti";
$wgMetaNamespace   = "Silvesti";
$wgServer          = "https://silvesti.wiki";
$wgCanonicalServer = "https://silvesti.wiki";
$wgScriptPath      = "";
$wgArticlePath     = "/wiki/$1";
$wgUsePathInfo     = true;

# Trust the reverse proxy (NPM) so logins / IPs / https detection work
$wgUsePrivateIPs = true;

# --- Database (shared MariaDB on Tower04, one DB per wiki) --------------------
$wgDBtype     = "mysql";
$wgDBserver   = "10.47.40.59";
$wgDBname     = "silvesti";
$wgDBuser     = "silvesti";
# $wgDBpassword is set by the installer — keep the wizard's value.
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4";

# --- Uploads / media ---------------------------------------------------------
$wgEnableUploads   = true;
$wgUploadDirectory = "$IP/images";
$wgMaxUploadSize   = 64 * 1024 * 1024;
$wgFileExtensions  = array_merge( $wgFileExtensions,
    [ 'pdf', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'mp4', 'webm' ] );

# =============================================================================
# BUNDLED EXTENSIONS (already in the official image — just enable)
# =============================================================================
wfLoadExtension( 'ParserFunctions' );   # #if/#switch/#expr — used heavily by silvesti
wfLoadExtension( 'VisualEditor' );      # WYSIWYG editing
wfLoadExtension( 'WikiEditor' );        # enhanced wikitext toolbar
wfLoadExtension( 'CodeMirror' );        # live wikitext syntax highlighting
wfLoadExtension( 'Cite' );              # <ref> footnotes
wfLoadExtension( 'Scribunto' );         # Lua (harmless if unused)
$wgScribuntoDefaultEngine = 'luastandalone';
wfLoadExtension( 'TemplateData' );      # template param docs (VE dialogs)
wfLoadExtension( 'SyntaxHighlight_GeSHi' );
wfLoadExtension( 'ImageMap' );
wfLoadExtension( 'InputBox' );
wfLoadExtension( 'Poem' );
wfLoadExtension( 'CharInsert' );
wfLoadExtension( 'PdfHandler' );
wfLoadExtension( 'Gadgets' );
wfLoadExtension( 'Interwiki' );
wfLoadExtension( 'Nuke' );
wfLoadExtension( 'ConfirmEdit' );       # CAPTCHA (largely moot with no self-signup)

$wgDefaultUserOptions['visualeditor-enable'] = 1;

# =============================================================================
# NON-BUNDLED EXTENSIONS  (baked in by the repo Dockerfile — see README)
#   REQUIRED: Variables — silvesti templates/content use {{#var}}/{{#vardefine}}
#   65x in templates + 268x in content. The wiki breaks without it.
# =============================================================================
wfLoadExtension( 'Variables' );

# =============================================================================
# AUTHELIA SSO  (PluggableAuth + OpenID Connect) — also baked in by the Dockerfile
# Login ONLY via Authelia; no local login form; no self-registration.
# =============================================================================
wfLoadExtension( 'PluggableAuth' );
wfLoadExtension( 'OpenIDConnect' );

$wgPluggableAuth_EnableLocalLogin      = false;   # hide username/password form
$wgPluggableAuth_EnableLocalProperties = false;
$wgPluggableAuth_ButtonLabelMessage    = 'Log in with Authelia';

# Authelia OIDC client — create the matching client in Authelia configuration.yml.
# Request the 'groups' scope so the AD/Authelia "wiki" group comes through.
$wgOpenIDConnect_Config['https://auth.owenshomeonline.com'] = [
    'clientID'     => 'mediawiki-silvesti',
    'clientsecret' => 'REPLACE-WITH-CLIENT-SECRET',
    'scope'        => [ 'openid', 'email', 'profile', 'groups' ],
];
$wgOpenIDConnect_MigrateUsersByEmail = true;

# =============================================================================
# PERMISSIONS  — public read; no self-signup; edit only for the "wiki" group
# =============================================================================
# Anonymous / everyone: can READ, cannot edit, cannot self-register.
$wgGroupPermissions['*']['read']              = true;    # <-- public read
$wgGroupPermissions['*']['edit']              = false;
$wgGroupPermissions['*']['createaccount']     = false;   # no self-registration
$wgGroupPermissions['*']['autocreateaccount'] = true;    # OIDC provisions accounts on Authelia login

# A logged-in (OIDC-provisioned) user is NOT an editor by default — being in
# Authelia is not enough; they must be in the "wiki" group.
$wgGroupPermissions['user']['edit']       = false;
$wgGroupPermissions['user']['createpage'] = false;
$wgGroupPermissions['user']['upload']     = false;
$wgGroupPermissions['user']['move']       = false;

# The approved editors' group. Members get full editing.
$wgGroupPermissions['wiki']['edit']          = true;
$wgGroupPermissions['wiki']['createpage']    = true;
$wgGroupPermissions['wiki']['createtalk']    = true;
$wgGroupPermissions['wiki']['upload']        = true;
$wgGroupPermissions['wiki']['reupload']      = true;
$wgGroupPermissions['wiki']['move']          = true;
$wgGroupPermissions['wiki']['minoredit']     = true;

# --- Mapping the Authelia "wiki" group -> the MediaWiki "wiki" group ----------
# Two supported ways; pick one at deploy and delete the other:
#
# (A) Claim-driven (preferred, fully automatic): have OpenIDConnect read the
#     'groups' claim Authelia sends and add the user to the matching MediaWiki
#     group. Confirm the exact global for the installed OpenIDConnect version
#     (recent builds support a group-name property in $wgOpenIDConnect_Config).
#     Authelia side: put the approved people in an AD/Authelia group literally
#     named "wiki" and include it in the token's groups claim for this client.
#
# (B) Manual fallback: leave mapping off and, after each new editor's first
#     Authelia login (which auto-creates their account), run once:
#         php maintenance/createAndPromote.php --group=wiki "Their Username"
#     Good enough for a small, known editor list.
#
# Belt-and-suspenders (optional): since READ is public, do NOT put Authelia
# forward-auth in front of the whole site — that would block anonymous readers.
# Access control lives entirely in these permission rules.

# --- Look & feel -------------------------------------------------------------
wfLoadSkin( 'Vector' );
$wgDefaultSkin = 'vector-2022';   # old wiki used legacy Vector; 2022 is the modern default

# --- Misc --------------------------------------------------------------------
$wgEnableEmail          = false;
$wgShowExceptionDetails = false;   # true only while debugging
$wgRightsText           = "Creative Commons Attribution";   # matches the old wiki
