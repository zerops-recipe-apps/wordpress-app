<?php
/**
 * Web-root bootstrap. The real configuration lives one directory ABOVE the web
 * root (so the server can never serve it). Load it, then boot WordPress.
 *
 * wp-settings.php is required HERE (not in the parent) so both paths work:
 *  - Web: wp-load.php discovers this file via ABSPATH's parent and boots WP.
 *  - WP-CLI: discovers this file and accepts it (wp-settings loaded directly).
 */
require_once dirname( __DIR__ ) . '/wp-config.php';
require_once ABSPATH . 'wp-settings.php';
