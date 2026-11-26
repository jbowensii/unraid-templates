#!/bin/bash
F=/mnt/cache_virtual_ssd/appdata/forgejo/data/gitea/conf/app.ini
cp "$F" "$F.bak-prebaseurl-$(date +%Y%m%d-%H%M%S)"
# Web/HTTPS -> hostname; SSH clone URLs stay on the IP (SSH goes direct to Tower05 .45:2222, not through NPM).
sed -i 's|^ROOT_URL = .*|ROOT_URL = https://github.local.owenshomeonline.com/|' "$F"
sed -i 's|^DOMAIN = .*|DOMAIN = github.local.owenshomeonline.com|' "$F"
# Pin SSH_DOMAIN to the IP. Add it under [server] if not present.
if grep -q '^SSH_DOMAIN = ' "$F"; then
  sed -i 's|^SSH_DOMAIN = .*|SSH_DOMAIN = 10.47.40.45|' "$F"
else
  sed -i '/^\[server\]/a SSH_DOMAIN = 10.47.40.45' "$F"
fi
echo "--- [server] relevant keys now ---"
grep -A20 '^\[server\]' "$F" | grep -iE 'ROOT_URL|^DOMAIN|SSH_DOMAIN|SSH_PORT|HTTP_PORT|PROTOCOL' | head
