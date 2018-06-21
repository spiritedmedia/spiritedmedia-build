<?php
// Bootstrap WordPress
require( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

// Require logged in users to see QA pages
if ( ! is_user_logged_in() ) {
    auth_redirect();
}

// Disable SSL verification so local environments will work
add_filter( 'https_ssl_verify', '__return_false' );

// Initiate Pedestal
$pedestal = \Pedestal\Pedestal();

/**
 * Get the current site URL for a given site ID
 *
 * @param  integer $site_id Site ID to get URL for
 * @return string           Site URL
 */
function pedestal_get_root_url( $site_id = 0 ) {
    $site_id = absint( $site_id );
    switch_to_blog( $site_id );
    $root = get_site_url();
    restore_current_blog();

    return pedestal_stagify_url( $root );
}

/**
 * If the URL is a staging URL add the username and password inline to the URL
 *
 * @param  string $url The URL to check
 * @return string      The modified URL
 */
function pedestal_stagify_url( $url = '' ) {
    // Staging URLs need a username and password
    if ( strpos( strtolower( $url ), 'staging' ) ) {
        $url = str_replace( 'staging.', 'spirited:media@staging.', $url );
    }
    return $url;
}
