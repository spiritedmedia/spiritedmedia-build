<?php

namespace Pedestal\Objects;

use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Post;

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
                $posts[] = Post::get_by_post_id( $post->ID );
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

        $this->query = new \WP_Query( $args );
    }

    /**
     * Check if stream is on its first page
     *
     * @return boolean
     */
    public function is_first_page() {
        $pagination = self::get_pagination( $this->get_query() );
        if ( empty( $pagination ) || 1 === intval( $pagination['pages_text']['paged'] ) ) {
            return true;
        }
        return false;
    }

    /**
     * Check if stream is on its last page
     *
     * @return boolean
     */
    public function is_last_page() {
        $pagination = self::get_pagination( $this->get_query() );
        if ( intval( $pagination['pages_text']['paged'] ) === intval( $pagination['pages_text']['total'] ) ) {
            return true;
        }
        return false;
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
