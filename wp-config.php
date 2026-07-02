<?php
/**
 * WordPress configuration for Zerops.
 *
 * 12-factor: every setting is read from the environment — there are NO secrets
 * in this file, so it is safe to commit. It lives ABOVE the web root
 * (public/), so the web server can never serve or leak it.
 *
 * Env vars are wired in zerops.yaml (run.envVariables, cross-service refs) and
 * import.yaml (envSecrets: salts, admin password). See README.md.
 */

/* -------------------------------------------------------------------------- *
 *  Tiny env helpers — getenv() returns strings; "false" is truthy in PHP,
 *  so booleans MUST be coerced or debug/flags silently invert.
 * -------------------------------------------------------------------------- */
if ( ! function_exists( 'zerops_env' ) ) {
	function zerops_env( string $key, $default = null ) {
		$v = getenv( $key );
		return ( $v === false || $v === '' ) ? $default : $v;
	}
	function zerops_env_bool( string $key, bool $default = false ): bool {
		$v = getenv( $key );
		return ( $v === false ) ? $default : filter_var( $v, FILTER_VALIDATE_BOOLEAN );
	}
}

/* -------------------------------------------------------------------------- *
 *  Paths & Composer autoloader
 * -------------------------------------------------------------------------- */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/public/wp/' );   // WordPress core lives here
}
require_once __DIR__ . '/vendor/autoload.php';

/* -------------------------------------------------------------------------- *
 *  Database (MariaDB)
 * -------------------------------------------------------------------------- */
define( 'DB_NAME',     zerops_env( 'WORDPRESS_DB_NAME' ) );
define( 'DB_USER',     zerops_env( 'WORDPRESS_DB_USER' ) );
define( 'DB_PASSWORD', zerops_env( 'WORDPRESS_DB_PASSWORD' ) );
define( 'DB_HOST',     zerops_env( 'WORDPRESS_DB_HOST' ) );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );
$table_prefix = zerops_env( 'WORDPRESS_TABLE_PREFIX', 'wp_' );

/* -------------------------------------------------------------------------- *
 *  Authentication unique keys & salts (from envSecrets)
 * -------------------------------------------------------------------------- */
define( 'AUTH_KEY',         zerops_env( 'WORDPRESS_AUTH_KEY' ) );
define( 'SECURE_AUTH_KEY',  zerops_env( 'WORDPRESS_SECURE_AUTH_KEY' ) );
define( 'LOGGED_IN_KEY',    zerops_env( 'WORDPRESS_LOGGED_IN_KEY' ) );
define( 'NONCE_KEY',        zerops_env( 'WORDPRESS_NONCE_KEY' ) );
define( 'AUTH_SALT',        zerops_env( 'WORDPRESS_AUTH_SALT' ) );
define( 'SECURE_AUTH_SALT', zerops_env( 'WORDPRESS_SECURE_AUTH_SALT' ) );
define( 'LOGGED_IN_SALT',   zerops_env( 'WORDPRESS_LOGGED_IN_SALT' ) );
define( 'NONCE_SALT',       zerops_env( 'WORDPRESS_NONCE_SALT' ) );

/* -------------------------------------------------------------------------- *
 *  URLs — core in /wp, content in /wp-content (both under the public root)
 * -------------------------------------------------------------------------- */
$home = rtrim( (string) zerops_env( 'WORDPRESS_URL', '' ), '/' );
define( 'WP_HOME',        $home );
define( 'WP_SITEURL',     $home . '/wp' );
define( 'WP_CONTENT_DIR', __DIR__ . '/public/wp-content' );
define( 'WP_CONTENT_URL', $home . '/wp-content' );

/* -------------------------------------------------------------------------- *
 *  Environment & debug (coerced booleans — "false" strings stay false)
 * -------------------------------------------------------------------------- */
define( 'WP_ENVIRONMENT_TYPE', zerops_env( 'WORDPRESS_ENV', 'production' ) );
// Debug flags default from the environment type — development turns WP_DEBUG and
// WP_DEBUG_LOG on, production leaves everything off; display errors never default on
// (they must not reach visitors). These are intentionally not baked into zerops.yaml
// so an operator can flip any flag with a service env var + restart (no redeploy);
// an explicit WORDPRESS_DEBUG* value always wins over the derived default.
$wp_is_dev = ( WP_ENVIRONMENT_TYPE === 'development' );
define( 'WP_DEBUG',         zerops_env_bool( 'WORDPRESS_DEBUG',         $wp_is_dev ) );
define( 'WP_DEBUG_LOG',     zerops_env_bool( 'WORDPRESS_DEBUG_LOG',     $wp_is_dev ) );
define( 'WP_DEBUG_DISPLAY', zerops_env_bool( 'WORDPRESS_DEBUG_DISPLAY', false ) );
if ( ! WP_DEBUG_DISPLAY ) {
	@ini_set( 'display_errors', '0' );   // never leak errors to visitors
}

/* -------------------------------------------------------------------------- *
 *  Media uploads -> S3 (Zerops object storage / MinIO). See mu-plugins/s3.php
 *  for the path-style endpoint wiring.
 * -------------------------------------------------------------------------- */
define( 'S3_UPLOADS_BUCKET',     zerops_env( 'WORDPRESS_STORAGE_BUCKET' ) );
define( 'S3_UPLOADS_REGION',     'us-east-1' );                              // required by every S3 SDK; MinIO ignores the value
define( 'S3_UPLOADS_KEY',        zerops_env( 'WORDPRESS_STORAGE_KEY_ID' ) );
define( 'S3_UPLOADS_SECRET',     zerops_env( 'WORDPRESS_STORAGE_ACCESS_KEY' ) );
define( 'S3_UPLOADS_ENDPOINT',   zerops_env( 'WORDPRESS_STORAGE_URL' ) );
define( 'S3_UPLOADS_BUCKET_URL', rtrim( (string) zerops_env( 'WORDPRESS_STORAGE_URL' ), '/' ) . '/' . zerops_env( 'WORDPRESS_STORAGE_BUCKET' ) );
define( 'S3_UPLOADS_USE_LOCAL',  false );

/* -------------------------------------------------------------------------- *
 *  Persistent object cache -> Redis (Zerops Valkey). AUTH is mandatory on
 *  Zerops Valkey; the phpredis extension ships in the php-nginx runtime.
 * -------------------------------------------------------------------------- */
define( 'WP_REDIS_HOST',            zerops_env( 'WORDPRESS_REDIS_HOST' ) );
define( 'WP_REDIS_PORT',            (int) zerops_env( 'WORDPRESS_REDIS_PORT', '6379' ) );
define( 'WP_REDIS_PASSWORD',        zerops_env( 'WORDPRESS_REDIS_PASSWORD' ) );
define( 'WP_REDIS_PREFIX',          zerops_env( 'WORDPRESS_REDIS_PREFIX', 'wp' ) );
define( 'WP_REDIS_TIMEOUT',         2 );
define( 'WP_REDIS_READ_TIMEOUT',    2 );
define( 'WP_REDIS_RETRY_INTERVAL',  100 );  // ms
define( 'WP_REDIS_GRACEFUL',        true );  // degrade to no-cache instead of fataling if Redis blips
define( 'WP_REDIS_DISABLE_BANNERS', true );
define( 'WP_CACHE_KEY_SALT',        zerops_env( 'WORDPRESS_REDIS_PREFIX', 'wp' ) . ':' );

/* -------------------------------------------------------------------------- *
 *  Hardening — core is Composer-managed and the filesystem is ephemeral
 *  (deploy = fresh container), so all in-dashboard file writes/updates are
 *  disabled and WordPress cron is replaced by a real Zerops cron job.
 * -------------------------------------------------------------------------- */
define( 'FORCE_SSL_ADMIN',            true );
define( 'DISALLOW_FILE_EDIT',         true );
define( 'DISALLOW_FILE_MODS',         true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE',        false );
define( 'FS_METHOD',                  'direct' );
define( 'DISABLE_WP_CRON',            true );   // real cron runs via zerops.yaml crontab
define( 'WP_POST_REVISIONS',          10 );
define( 'EMPTY_TRASH_DAYS',           14 );

/* -------------------------------------------------------------------------- *
 *  Trust the Zerops L7 balancer (TLS terminated upstream, forwarded as a
 *  plain X-Forwarded-Proto header). Guarded so CLI/health contexts never warn.
 * -------------------------------------------------------------------------- */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/* --------------------------------------------------------------------------
 *  wp-settings.php is intentionally NOT loaded here — the web-root shim
 *  (public/wp-config.php) requires this file and then loads wp-settings
 *  directly, so WP-CLI accepts the config it discovers via the shim.
 * -------------------------------------------------------------------------- */
