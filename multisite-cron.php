<?php
/**
 * Trigger the cron jobs for each of the sites in a multisite setup.
 * Note: The URL pointing to this file needs to be hit from an external cron job.
 *
 * See https://tribulant.com/blog/wordpress/replace-wordpress-cron-with-real-cron-for-site-speed/
 */

if ( ! defined( 'ABSPATH' ) ) {
    require_once( dirname( __FILE__ ) . '/wp-load.php' );
}

global $wpdb;
if (
    defined( 'WP_ENV' ) &&
    (
        'development' === WP_ENV ||
        'dev' === WP_ENV
    )
) {
    // Local dev environments have custom SSL certs that cURL can't verify
    add_filter( 'https_ssl_verify', '__return_false' );
}
$sites = $wpdb->get_results( 'SELECT `domain`, `path` FROM ' . $wpdb->blogs );
$encoded_secret = 'c3Bpcml0ZWQ6bWVkaWE=';
$request_args = [
    'headers' => [
        'Authorization' => 'Basic ' . $encoded_secret,
    ],
    // We don't care about the response so we set a low timeout and blocking to false
    'timing' => 0.01,
    'blocking' => false,
];
foreach ( $sites as $site ) {
    $path = '/';
    if ( ! empty( $site->path ) ) {
        $path = $site->path;
    }
    $url = 'https://' . $site->domain . $path . 'wp-cron.php?date=' . date( 'U' );

    $request = wp_remote_get( $url, $request_args );
}
nocache_headers();
http_response_code( 200 );
