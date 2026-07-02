#!/usr/bin/env bash
#
# Per-deploy tasks — runs once per app version (zsc execOnce ${appVersionId}).
# Idempotent; must never revert admin-made choices.
#
set -euo pipefail

# Apply any database schema migrations after a WordPress core version bump.
wp core update-db || true

# Keep the infrastructure plugins active (does NOT touch other admin plugins).
# The object-cache.php drop-in is already baked into the build artifact, so
# there's nothing to enable here — it just connects to the managed Valkey.
wp plugin activate redis-cache s3-uploads || true

# Regenerate rewrite rules (does not change the admin's permalink structure).
wp rewrite flush || true
