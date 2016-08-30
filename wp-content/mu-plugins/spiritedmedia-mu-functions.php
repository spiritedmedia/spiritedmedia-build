<?php
/*
Plugin Name: Spirited Media Multisite Functions
Description: Special functions available to all of the sites hosted on spiritedmedia.com
Author: Russell Heimlich
Version: 0.1
*/

/**
 * Composer autoload
 *
 * Loads Composer dependencies as well as Pedestal classes
 */
require_once ABSPATH . '/vendor/autoload.php';


/**
 * Correct the cookie domain when a custom domain is mapped to the site.
 *
 * @see https://blog.handbuilt.co/2016/07/07/fixing-cookie_domain-for-mapped-domains-on-multisite/
 */
add_action( 'muplugins_loaded', function() {
    global $current_blog, $current_site;
    if ( false === stripos( $current_blog->domain, $current_site->cookie_domain ) ) {
        $current_site->cookie_domain = $current_blog->domain;
    }
} );

// Disable Mercators SSO functionality which slows down page loads via an AJAX request
add_filter( 'mercator.sso.enabled', '__return_false' );
add_filter( 'mercator.sso.multinetwork.enabled', '__return_false' );

add_action( 'set_auth_cookie', function( $auth_cookie, $expire, $expiration, $user_id, $scheme ) {
    setcookie( 'is_logged_in', 'true', $expire, COOKIEPATH, COOKIE_DOMAIN, false, false );
}, 10, 5 );

add_action( 'clear_auth_cookie', function() {
    if ( isset( $_COOKIE['is_logged_in'] ) ) {
        unset( $_COOKIE['is_logged_in'] );
    }
    setcookie( 'is_logged_in', 'false', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false, false );
});

/**
 * Add internal server IP to the footer of all requests
 */
add_action( 'wp_footer', function() {
    echo '<!-- ' . $_SERVER['SERVER_ADDR'] . ' -->';
} );
