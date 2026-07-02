#!/usr/bin/env bash
#
# First-boot provisioning — runs exactly once per project (zsc execOnce wpinit).
# The database already exists (Zerops provisions ${db_dbName}); WordPress just
# creates its tables. WP-CLI resolves the install path from ../wp-cli.yml.
#
set -euo pipefail

# --- temporary RAM for the one-time install spike ---------------------------
# `wp core install` — and even `wp core is-installed` — bootstraps the full
# WordPress + PHP runtime, a ~200 MB+ spike that OOM-kills a small steady-state
# container (this is what the first-boot crash-loop was). Rather than tax every
# container with a permanent minRam floor, grab a short-lived RAM grant just for
# first boot and let it auto-release. `zsc scale` returns immediately but the
# grant lands a few seconds later (and is capped by the service's maxRam), so
# block on the cgroup memory limit before running any wp-cli command. The 10m
# window also covers the version upgrade.sh that runs right after this on the
# very first boot.
NEED_BYTES=536870912   # 512 MiB — headroom over the install's working set
have_ram() {
  local m; m=$(cat /sys/fs/cgroup/memory.max 2>/dev/null || echo max)
  [ "$m" = "max" ] && return 0
  [ "$m" -ge "$NEED_BYTES" ] 2>/dev/null
}
if ! have_ram; then
  echo "Reserving temporary RAM for first-boot install..."
  zsc scale ram 1GiB 10m >/dev/null 2>&1 || true
  for _ in $(seq 1 30); do have_ram && break; sleep 1; done
fi

if ! wp core is-installed 2>/dev/null; then
  echo "Installing WordPress..."
  wp core install \
    --url="${WORDPRESS_URL}" \
    --title="${WORDPRESS_TITLE:-WordPress on Zerops}" \
    --admin_user="${WORDPRESS_ADMIN_USER}" \
    --admin_password="${WORDPRESS_ADMIN_PASSWORD}" \
    --admin_email="${WORDPRESS_ADMIN_EMAIL}" \
    --skip-email
  wp theme activate twentytwentyfour
  wp rewrite structure '/%postname%/' --hard
else
  echo "WordPress already installed — skipping core install."
fi
