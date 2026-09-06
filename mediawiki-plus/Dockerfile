# =============================================================================
# mediawiki-plus - official MediaWiki 1.43 LTS + three non-bundled extensions
#
#   Variables       {{#var}} / {{#vardefine}} dynamic variables
#   PluggableAuth   pluggable authentication framework
#   OpenIDConnect   OIDC / SSO provider plugin for PluggableAuth
#
# Everything else (VisualEditor, ParserFunctions, Cite, Scribunto, ...) is
# already bundled in the official image and only needs wfLoadExtension().
#
# Built and published to ghcr.io/jbowensii/mediawiki-plus by
# .github/workflows/mediawiki-plus.yml - you should not need to build this
# by hand. The Unraid template pulls the published image directly.
# =============================================================================
FROM mediawiki:1.43

LABEL org.opencontainers.image.source="https://github.com/jbowensii/unraid-templates"
LABEL org.opencontainers.image.description="Official MediaWiki 1.43 LTS plus the Variables, PluggableAuth and OpenIDConnect extensions."
LABEL org.opencontainers.image.licenses="MIT"

RUN set -eux; apt-get update; apt-get install -y --no-install-recommends git unzip; rm -rf /var/lib/apt/lists/*

# Composer
RUN set -eux; php -r "copy('https://getcomposer.org/installer','/tmp/c.php');"; \
    php /tmp/c.php --install-dir=/usr/local/bin --filename=composer; rm /tmp/c.php

WORKDIR /var/www/html/extensions

# Variables (no composer deps) - REQUIRED for #var/#vardefine templates
RUN git clone --depth 1 -b REL1_43 https://github.com/wikimedia/mediawiki-extensions-Variables Variables

# PluggableAuth + OpenIDConnect (OIDC SSO) - each pulls its own composer deps
RUN git clone --depth 1 -b REL1_43 https://github.com/wikimedia/mediawiki-extensions-PluggableAuth PluggableAuth \
 && cd PluggableAuth && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction
RUN git clone --depth 1 -b REL1_43 https://github.com/wikimedia/mediawiki-extensions-OpenIDConnect OpenIDConnect \
 && cd OpenIDConnect && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction

WORKDIR /var/www/html
