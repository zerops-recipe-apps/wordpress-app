# wordpress-app

Production-grade WordPress on Zerops: Composer-managed core, Redis object cache, S3 media, hardened Nginx, and real cron. Requires MariaDB (`db`), Valkey (`cache`), and S3-compatible storage (`storage`) siblings.

## Zerops service facts

- HTTP port: `80`
- Siblings:
  - `db` (MariaDB) — env: `WORDPRESS_DB_*`
  - `cache` (Valkey) — env: `WORDPRESS_REDIS_*`
  - `storage` (S3-compatible) — env: `WORDPRESS_STORAGE_*`
- Runtime base: `php-nginx@8.4` (Alpine)

## Zerops dev

`setup: dev` deploys full source with `composer install` (includes dev deps) and `WORDPRESS_ENV: development`.

- Dev command: Nginx + PHP-FPM start automatically; use WP-CLI over SSH (`wp ...`)
- In-container rebuild without deploy: `composer install --optimize-autoloader --no-interaction`

**All platform operations (start/stop/status/logs of the dev server, deploy, env / scaling / storage / domains) go through the Zerops development workflow via `zcp` MCP tools. Don't shell out to `zcli`.**

## Notes

- WordPress core, plugins, and themes are Composer-managed — do not edit files under `public/wp/` or git-ignored plugin/theme dirs; use `composer require` instead.
- `utils/initialize.sh` runs once ever via `zsc execOnce wpinit`; `utils/upgrade.sh` runs per deploy version.
- `utils/run-prepare.sh` installs WP-CLI and tunes OPcache at container start.
- Site icon/favicon is configured in WordPress admin after install, not committed in this repo.
