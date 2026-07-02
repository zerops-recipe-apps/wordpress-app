<?php
/**
 * Plugin Name: Zerops S3 Uploads wiring
 * Description: Points humanmade/s3-uploads at the Zerops object-storage
 *              (MinIO) endpoint using path-style addressing.
 */

add_filter( 's3_uploads_s3_client_params', function ( array $params ): array {
	if ( defined( 'S3_UPLOADS_ENDPOINT' ) && S3_UPLOADS_ENDPOINT ) {
		$params['endpoint'] = S3_UPLOADS_ENDPOINT;
	}
	// MinIO / Zerops object storage requires path-style bucket addressing.
	$params['use_path_style_endpoint'] = true;
	return $params;
} );
