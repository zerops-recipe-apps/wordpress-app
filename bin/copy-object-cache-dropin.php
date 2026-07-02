<?php
/**
 * Install the redis-cache object-cache.php drop-in into wp-content/.
 *
 * WordPress loads wp-content/object-cache.php in wp_start_object_cache(), BEFORE
 * any plugin runs, so a persistent cache backend must be a drop-in at that fixed
 * path — it cannot be an activatable plugin. The redis-cache package ships the
 * backend as includes/object-cache.php; this copies it into place.
 *
 * Invoked from Composer's post-install / post-update hooks, so the drop-in is
 * baked into the artifact — version-matched to the installed plugin — in every
 * context that runs `composer install` (the Zerops build, local dev, CI). Exits
 * non-zero if the source is missing, so a moved/renamed plugin file fails the
 * build loudly instead of silently shipping without an object cache.
 */

$root = dirname(__DIR__);
$src  = $root . '/public/wp-content/plugins/redis-cache/includes/object-cache.php';
$dst  = $root . '/public/wp-content/object-cache.php';

if ( ! is_file( $src ) ) {
	fwrite( STDERR, "redis-cache drop-in not found at {$src}\n" );
	exit( 1 );
}

if ( ! copy( $src, $dst ) ) {
	fwrite( STDERR, "failed to install object-cache.php drop-in to {$dst}\n" );
	exit( 1 );
}

echo "Installed object-cache.php drop-in from redis-cache\n";
