# WordPress on Zerops — application

The forkable application code behind the [WordPress recipe](https://app.zerops.io/recipes/wordpress) on [Zerops](https://zerops.io): Composer-managed WordPress with an isolated web root, a Redis object cache, S3-backed media, a hardened Nginx and real cron.

[![Deploy on Zerops](https://github.com/zeropsio/recipe-shared-assets/blob/main/deploy-button/light/deploy-button.svg)](https://app.zerops.io/recipes/wordpress?environment=production)

> The project topology (services, env tiers) is defined in the recipe manifest at
> [`zeropsio/recipes/wordpress`](https://github.com/zeropsio/recipes/tree/main/wordpress).
> This repo is what each env's `import.yaml` `buildFromGit`s.

## `zerops.yaml` setups

| Setup | Used by | What differs |
|---|---|---|
| `base` | (shared) | run image, cross-service env wiring, init (install + db-upgrade), health check, cron |
| `prod` | Production env (`zeropsSetup: prod`) | `composer install --no-dev`, frozen OPcache (`validate_timestamps=0`), `WP_DEBUG` off, readiness check |
| `dev`  | Development env (`zeropsSetup: dev`) | dev Composer deps, OPcache revalidates (live SSH edits), `WP_DEBUG` on |

## Layout

```
wp-config.php            real config (env-driven, NOT web-served)
composer.json / .lock    core + plugins as dependencies
wp-cli.yml               points WP-CLI at public/wp
zerops.yaml              base / dev / prod setups
site.conf.tmpl           hardened Nginx server block
utils/
  run-prepare.sh         wp-cli install + OPcache tuning (via addToRunPrepare)
  initialize.sh          first-boot: wp core install (once, ever)
  upgrade.sh             per-deploy: update-db + activate infra plugins
public/                  ← web root
  index.php              front controller
  wp-config.php          bootstrap shim → ../wp-config.php + wp-settings
  wp/                    WordPress core             (Composer, git-ignored)
  wp-content/
    mu-plugins/          s3.php (media), mailer.php (SMTP)
    plugins/ themes/     (Composer, git-ignored)
vendor/                  Composer deps              (git-ignored)
```

## Working with it

- **Add a plugin/theme:** `composer require wpackagist-plugin/<slug>`, commit, push. The dashboard installer/editor are disabled (`DISALLOW_FILE_MODS`) because the filesystem is rebuilt on every deploy.
- **Upgrade WordPress:** bump `johnpbloch/wordpress` in `composer.json`, `composer update`, push — `utils/upgrade.sh` runs `wp core update-db` on deploy.
- **Config:** everything is read from the environment (see `wp-config.php`); there are no secrets in the repo.

## How this compares to Bedrock

Same Composer-managed core + isolated web root + env config, then wired natively to Zerops: managed MariaDB/Valkey/object-storage, a durable Redis object cache, S3 media, zero-downtime health/readiness, real cron and OPcache tuning — the pieces Bedrock leaves you to assemble.
