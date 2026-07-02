#!/usr/bin/env bash
#
# First-boot provisioning — runs exactly once per project (zsc execOnce wpinit).
# The database already exists (Zerops provisions ${db_dbName}); WordPress just
# creates its tables. WP-CLI resolves the install path from ../wp-cli.yml.
#
set -euo pipefail

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
