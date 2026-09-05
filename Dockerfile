# =============================================================================
# Extended MediaWiki image for silvesti.wiki (and similar Authelia-gated wikis)
#
# Bakes the three NON-bundled extensions the wiki needs on top of the official
# image so they survive image updates and don't have to live in a mapped volume:
#   - Variables       (REQUIRED: {{#var}}/{{#vardefine}} used throughout silvesti)
#   - PluggableAuth    (Authelia SSO)
#   - OpenIDConnect    (Authelia SSO)
#
# Build & push to your own registry (GHCR), then point the Unraid template's
# <Repository> at it, e.g.  ghcr.io/jbowensii/mediawiki-silvesti:latest
#
#   docker build -t ghcr.io/jbowensii/mediawiki-silvesti:latest .
#   docker push  ghcr.io/jbowensii/mediawiki-silvesti:latest
#
# Pin BASE at build time to keep core and the composer-installed extensions in
# lockstep (PluggableAuth/OpenIDConnect releases track the core branch).
# Default: the current stable line. Use 1.43 (LTS) if you prefer longer support.
# =============================================================================
ARG BASE=mediawiki:1.43
FROM ${BASE}

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip; \
    rm -rf /var/lib/apt/lists/*

# --- Variables (no composer deps; clone the branch matching core) ------------
# REL1_43 for the 1.43 base; switch to the branch that matches your BASE.
ARG EXT_BRANCH=REL1_43
RUN git clone --depth 1 -b ${EXT_BRANCH} \
      https://github.com/wikimedia/mediawiki-extensions-Variables \
      /var/www/html/extensions/Variables

# --- Composer (for PluggableAuth + OpenIDConnect) ----------------------------
RUN set -eux; \
    php -r "copy('https://getcomposer.org/installer','/tmp/composer-setup.php');"; \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer; \
    rm /tmp/composer-setup.php

WORKDIR /var/www/html
# These two publish to Packagist and pull their own dependencies. Version
# constraints are resolved against the core in the base image. If composer
# complains, pin exact versions compatible with your MediaWiki line.
RUN COMPOSER_ALLOW_SUPERUSER=1 composer require --no-interaction \
      mediawiki/pluggable-auth \
      mediawiki/open-id-connect

# LocalSettings.php is still provided at runtime (bind-mounted via the template),
# where the wfLoadExtension() lines above are enabled. See LocalSettings.example.php.
