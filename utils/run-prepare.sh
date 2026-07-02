#!/usr/bin/env bash
#
# Runtime image customization. Runs from run.prepareCommands, which executes
# BEFORE the deploy files arrive at /var/www — so this script is shipped into
# the prepare phase via build.addToRunPrepare and must NOT touch /var/www.
#
# Arg $1 = environment ("production" | "development"); controls OPcache
# timestamp validation (0 = never restat, fastest, safe on immutable prod
# deploys; 1 = restat so live SSH edits are picked up in dev).
#
set -euo pipefail

ENVIRONMENT="${1:-production}"
[ "$ENVIRONMENT" = "production" ] && VALIDATE_TS=0 || VALIDATE_TS=1

# --- WP-CLI: pinned version, checksum-verified -------------------------------
WP_CLI_VERSION="2.12.0"
WP_CLI_SHA256="ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c"
curl -fsSL "https://github.com/wp-cli/wp-cli/releases/download/v${WP_CLI_VERSION}/wp-cli-${WP_CLI_VERSION}.phar" -o /tmp/wp-cli.phar
echo "${WP_CLI_SHA256}  /tmp/wp-cli.phar" | sha256sum -c -
sudo install -m 0755 /tmp/wp-cli.phar /usr/local/bin/wp
rm -f /tmp/wp-cli.phar

# --- Production OPcache tuning -----------------------------------------------
# opcache.* directives are dotted, so they cannot be set via Zerops' PHP_INI_*
# env vars (those keep the underscore and PHP ignores them) — write a conf.d
# drop-in instead.
for dir in /etc/php/*/fpm/conf.d /etc/php/*/cli/conf.d; do
  [ -d "$dir" ] || continue
  sudo tee "$dir/zz-zerops-wordpress.ini" >/dev/null <<INI
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=20000
opcache.validate_timestamps=${VALIDATE_TS}
opcache.revalidate_freq=0
opcache.jit=disable
realpath_cache_size=4096K
realpath_cache_ttl=600
INI
done
