<?php

namespace Pedestal\Objects;

use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Post;
use Pedestal\Posts\Attachment;
use Pedestal\Posts\Slots\Slots;

class Stream {

    /**
     * Array of WP_Post objects
     *
     * @var array
     */
    private $posts = [];

    /**
     * Stream query
     *
     * @var WP_Query
     */
    private $query;

    /**
     * __construct
     *
     * @param WP_Query|array $query_args WP_Query object, an array of WP_Post
     *     objects, or query args
     */
    public function __construct( $query_args = [] ) {
        if ( $query_args instanceof \WP_Query && $query_args->have_posts() ) {
            $this->query = $query_args;
            $this->posts = $query_args->posts;
        } elseif ( is_array( $query_args ) && reset( $query_args ) instanceof \WP_Post ) {
            $this->posts = $query_args;
        } else {
            $this->setup_query( $query_args );
            if ( $this->get_query() instanceof \WP_Query && isset( $this->get_query()->posts ) ) {
                $this->posts = $this->get_query()->posts;
            }
        }
    }

    /**
     * Get a stream array quickly
     *
     * @param WP_Query|array $query_args WP_Query object, an array of WP_Post
     *     objects, or query args
     * @return array Pedestal Post object array or empty array
     */
    public static function get( $query_args ) {
        $stream = new Stream( $query_args );
        return $stream->get_stream();
    }

    /**
     * Get the array of posts using our post objects
     *
     * @return array
     */
    public function get_stream() {
        $posts = [];
        if ( ! empty( $this->get_posts() ) ) {
            foreach ( $this->get_posts() as $post ) {
                if ( ! $post instanceof \WP_Post ) {
                    continue;
                }
                $posts[] = Post::get_instance( $post );
            }
        }
        return $posts;
    }

    /**
     * Does the stream have posts?
     *
     * @return boolean
     */
    public function has_posts() {
        return (bool) $this->get_posts();
    }

    /**
     * Get the array of WP_Posts
     *
     * @return array
     */
    public function get_posts() {
        return $this->posts;
    }

    /**
     * Get the WP_Query object for the stream
     *
     * @return WP_Query
     */
    public function get_query() {
        return $this->query;
    }

    /**
     * Set up the stream query
     *
     * @param  array  $args WP_Query args
     */
    private function setup_query( $args = [] ) {
        $defaults = [
            'posts_per_page' => 20,
            'post_type'      => \Pedestal\Registrations\Post_Types\Types::get_entity_post_types(),
        ];
        $args = wp_parse_args( $args, $defaults );

        // Custom post types use `page`, not `paged`
        if ( get_query_var( 'paged' ) ) {
            $paged = get_query_var( 'paged' );
        } elseif ( get_query_var( 'page' ) ) {
            $paged = get_query_var( 'page' );
        } else {
            $paged = 1;
        }

        // Only set `paged` if not already set
        if ( empty( $args['paged'] ) ) {
            $args['paged'] = $paged;
        }

        // Sort the $args for caching consistency
        ksort( $args );
        // json_encode is faster than serialize()
        $cache_key = md5( json_encode( $args ) );
        $cache_group = 'ped_stream';
        $query = wp_cache_get( $cache_key, $cache_group );
        if ( ! $query ) {
            $query = new \WP_Query( $args );
            wp_cache_set( $cache_key, $query, $cache_group );
        }
        $this->query = $query;
    }

    /**
     * Check if stream is on its first page
     *
     * @return boolean
     */
    public function is_first_page() {
        $pagination = self::get_pagination( $this->get_query() );
        return $pagination['is_first_page'];
    }

    /**
     * Check if stream is on its last page
     *
     * @return boolean
     */
    public function is_last_page() {
        $pagination = self::get_pagination( $this->get_query() );
        return $pagination['is_last_page'];
    }

    /**
     * Get data about any sponsored stream items
     *
     * @return Array|False An array of data or false if no sponsored itmes found
     */
    public static function get_sponsored_items() {
        $slots = Slots::get_slot_data( 'slot_item', [
            'type' => 'stream',
        ] );
        if ( ! $slots ) {
            return false;
        }

        // Get the slot data
        $data = $slots->get_fm_field( 'slot_item_type', 'sponsored-stream-items' );
        // Whitelisted keys to ensure a consistent output
        $whitelisted_keys = [ 'position', 'url', 'title', 'sponsored_by', 'image', 'featured_image' ];
        $output = [];
        foreach ( $whitelisted_keys as $key ) {
            $output[ $key ] = '';
            if ( ! empty( $data[ $key ] ) ) {
                $output[ $key ] = $data[ $key ];
            }
        }

        /*
        If we don't have a sponsored_by value then bail to prevent an empty
        sponsored slot item from rendering
        */
        if ( empty( $output['sponsored_by'] ) ) {
            return false;
        }

        $output['position'] = 2; // After the 2nd item

        // Get an image
        if ( is_numeric( $output['image'] ) ) {
            $attachment = new Attachment( $output['image'] );
            // Make sure we have a proper Attachment object
            if ( method_exists( $attachment, 'get_html' ) ) {
                $image = $attachment->get_html( 'medium-square' );
                if ( ! empty( $output['url'] ) ) {
                    $image = sprintf( '<a href="%s" rel="nofollow" target="_blank" data-ga-category="Sponsored" data-ga-label="Image|%s">%s</a>',
                        esc_url( $output['url'] ),
                        esc_attr( $output['title'] ),
                        $image
                    );
                }
                $output['featured_image'] = $attachment->get_img_caption_html( $image, [
                    'classes' => 'o-media__img c-overview__img',
                ] );
            }
        }
        return $output;
    }

    /**
     * Get the stream pagination HTML
     *
     * @return HTML
     */
    public static function get_pagination( $query ) {
        return wp_pagenavi( [
            'query' => $query,
            'data'  => true,
        ] );
    }
}
