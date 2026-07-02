<?php
/**
 * WordPress front controller.
 * The public/ directory is the ONLY web-served path; core lives in public/wp.
 */
define( 'WP_USE_THEMES', true );
require __DIR__ . '/wp/wp-blog-header.php';
