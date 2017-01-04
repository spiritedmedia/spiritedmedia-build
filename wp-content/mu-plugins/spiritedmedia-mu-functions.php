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

/*
 * The BWP Google Sitemaps Plugin has various options we want to
 * control once in our codebase instead of on each individual site.
 *
 * Use http://www.unserialize.com/ to unserialize value from database
 */

add_filter( 'pre_option_bwp_gxs_generator', function( $value ) {
    $bwp_option = [
      'input_cache_dir' => '',
      'input_item_limit' => 5005,
      'input_split_limit_post' => 0,
      'input_custom_xslt' => '',
      'input_ping_limit' => 100,
      'enable_sitemap_date' => '',
      'enable_sitemap_taxonomy' => 'yes',
      'enable_sitemap_external' => '',
      'enable_sitemap_author' => 'yes',
      'enable_sitemap_site' => 'yes',
      'enable_exclude_posts_by_terms' => '',
      'enable_sitemap_split_post' => 'yes',
      'enable_ping' => 'yes',
      'enable_ping_google' => 'yes',
      'enable_ping_bing' => 'yes',
      'enable_xslt' => '',
      'enable_credit' => '',
      'select_default_freq' => 'daily',
      'select_default_pri' => '1',
      'select_min_pri' => '0.1',
      'input_exclude_post_type' => 'pedestal_link',
      'input_exclude_post_type_ping' => '',
      'input_exclude_taxonomy' => 'category,post_tag,pedestal_story_type,pedestal_slot_item_type',
    ];
    return $bwp_option;
});

add_filter( 'pre_option_bwp_gxs_extensions', function( $value ) {
    $bwp_option = [
      'enable_image_sitemap' => 'yes',
      'enable_news_sitemap' => 'yes',
      'enable_news_ping' => 'yes',
      'enable_news_keywords' => '',
      'enable_news_multicat' => '',
      'select_news_post_type' => 'pedestal_article',
      'select_news_taxonomy' => '',
      'select_news_lang' => 'en',
      'select_news_keyword_source' => '',
      'select_news_cat_action' => 'inc',
      'select_news_cats' => '',
      'input_news_name' => '',
      'input_news_age' => 3,
      'input_news_genres' => [],
      'input_image_post_types' => 'page,pedestal_article,pedestal_event,pedestal_link,pedestal_factcheck,pedestal_whosnext,pedestal_story,pedestal_topic,pedestal_person,pedestal_org,pedestal_place,pedestal_locality',
    ];
    return $bwp_option;
});

/**
 * Change the site_url to http:// from https:// when being applied from the nginx helper
 * plugin. This is necessary for the cache purging to work due to other system issues.
 * @var string
 */
add_filter( 'site_url', function( $url = '' ) {
    $backtrace = wp_debug_backtrace_summary();
    if ( stripos( $backtrace, 'rtCamp\WP\Nginx\Helper' ) ) {
        $url = str_replace( 'https://', 'http://', $url );
    }
    return $url;
});
