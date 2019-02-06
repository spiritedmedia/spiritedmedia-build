<?php
namespace Pedestal;

use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Post;

class Page_Cache {

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into WordPress via filters
     */
    public function setup_filters() {
        /**
         * Make sure the Redis full page cache key end with a ?
         * See https://github.com/spiritedmedia/spiritedmedia/issues/2804
         *
         * @param string $cache_key The cache key to be modified
         * @return string           The modified cache key
         */
        add_filter( 'redis_cache_purge/cache_key', function( $cache_key = '' ) {
            $last_char = substr( $cache_key, -1 );
            if ( $last_char === '/' ) {
                $cache_key .= '?';
            }
            return $cache_key;
        } );

        /**
         * Purge connected clusters from entities when they are modified or deleted
         *
         * @param array   $urls URLs to be purged
         * @param WP_Post $post The WordPress post object being modified
         * @return array        The modified URLs
         */
        add_filter( 'redis_cache_purge/purge_post', function( $urls = [], $post ) {
            $ped_post = Post::get( $post->ID );
            if ( Types::is_post( $ped_post ) && $ped_post->is_entity() ) {
                $connected_post_ids = $ped_post->get_all_connected_object_ids();
                if ( ! empty( $connected_post_ids ) ) {
                    foreach ( $connected_post_ids as $post_id ) {
                        $urls[] = get_permalink( $post_id ) . '*';
                    }
                }
            }

            // Filter out URLs that don't contain the current site's hostname
            $site_host_name = parse_url( get_site_url(), PHP_URL_HOST );
            $urls           = array_filter( $urls, function( $url ) use ( $site_host_name ) {
                return ( stripos( $url, $site_host_name ) );
            } );

            return $urls;
        }, 10, 2 );

        if ( ! defined( 'WP_ENV' ) || WP_ENV !== 'development' ) {
            /**
             * Replace https URLs with http when in Production
             * The loadbalancer terminates SSL connections and passes the
             * request to the web server via HTTP
             *
             * See https://github.com/spiritedmedia/spiritedmedia/pull/1785
             *
             * @param  string $cache_key The cache key to be modified
             * @return string            The modified cache key
             */
            add_filter( 'redis_cache_purge/cache_key', function( $cache_key = '' ) {
                $cache_key = str_replace( ':https', ':http', $cache_key );
                return $cache_key;
            } );
        }
    }

    /**
     * Wrapper around Redis_Full_Page_Cache_Purger's purge_all method
     */
    public static function purge_all() {
        if ( class_exists( 'Redis_Full_Page_Cache_Purger' ) ) {
            \Redis_Full_Page_Cache_Purger::purge_all();
        }
    }
}
