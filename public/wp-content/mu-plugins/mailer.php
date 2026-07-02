<?php
/**
 * Plugin Name: Zerops SMTP
 * Description: Routes WordPress email through the configured SMTP relay
 *              (Mailpit in the recipe; override with a real SMTP in production).
 */

add_action( 'phpmailer_init', function ( $phpmailer ) {
	$host = getenv( 'WORDPRESS_SMTP_HOST' );
	if ( ! $host ) {
		return; // no relay configured — leave PHP's default mail() transport
	}
	$phpmailer->isSMTP();
	$phpmailer->Host        = $host;
	$phpmailer->Port        = (int) ( getenv( 'WORDPRESS_SMTP_PORT' ) ?: 1025 );
	$phpmailer->SMTPAuth    = filter_var( getenv( 'WORDPRESS_SMTP_AUTH' ), FILTER_VALIDATE_BOOLEAN );
	$phpmailer->SMTPAutoTLS = $phpmailer->SMTPAuth;
	if ( $phpmailer->SMTPAuth ) {
		$phpmailer->Username = (string) getenv( 'WORDPRESS_SMTP_USER' );
		$phpmailer->Password = (string) getenv( 'WORDPRESS_SMTP_PASSWORD' );
	}
} );
