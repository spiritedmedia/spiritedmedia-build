<?php

namespace Pedestal\Objects;

use \Pedestal\Posts\Post;

class Stream {

    /**
     * Stream entities
     *
     * @var array
     */
    private $stream;

    /**
     * Stream query
     *
     * @var WP_Query
     */
    private $query;

    /**
     * Default query
     *
     * @var WP_Query
     */
    private $wp_query;

    public function __construct( $args = [] ) {
        $this->setup_query( $args );
        $this->setup_stream();
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
     * Get a single pagination button
     *
     * If there are no posts to be found for the specified direction ( 'prev' or
     * 'next' ), then output a disabled button.
     *
     * @param  string   $direction        Required; valid options include 'prev' or 'next'
     * @param  WP_Query $query            Optional custom query to paginate.
     * @param  string   $disabled_classes Classes to add to `<a>` when no posts are found; Defaults to 'disabled'
     * @param  string   $icon_name_prev   Name of the FontAwesome icon to use for prev link; Defaults to 'fa-angle-left'
     * @param  string   $icon_name_next   Name of the FontAwesome icon to use for next link; Defaults to 'fa-angle-right'
     *
     * @return HTML
     */
    public static function get_pagination_button( $direction, $query = null, $disabled_classes = 'disabled', $icon_name_prev = 'fa-angle-left', $icon_name_next = 'fa-angle-right' ) {

        switch ( $direction ) {
            case 'prev':
                $icon_name = $icon_name_prev;
                $link_text = esc_html__( 'Previous Posts', 'pedestal' );
                break;
            case 'next':
                $icon_name = $icon_name_next;
                $link_text = esc_html__( 'Next Posts', 'pedestal' );
                break;
        }

        $inner = '<i class="fa fa-2x ' . $icon_name . '"><span class="hide-for-small-up">' . $link_text . '</span></i>';

        switch ( $direction ) {
            case 'prev':
                $direction_link = get_previous_posts_link( $inner );
                break;
            case 'next':
                if ( is_object( $query ) ) {
                    $direction_link = get_next_posts_link( $inner, (int) $query->max_num_pages );
                } else {
                    $direction_link = get_next_posts_link( $inner );
                }
                break;
        }

        if ( $direction_link ) {
            $output = $direction_link;
            $output = str_replace( '<a href=', '<a class="pagination-link-' . $direction . ' columns small-6" href=', $output );
        } else {
            $output = '<a class="pagination-link-' . $direction . ' ' . $disabled_classes . ' columns small-6" href="#">' . $inner . '</a>';
        }

        return $output;

    }

    /**
     * Get the stream array
     *
     * @return array Array of entities in stream
     */
    public function get_stream() {
        return $this->stream;
    }

    /**
     * Set up the stream
     */
    private function setup_stream() {
        $query = $this->get_query();
        $this->stream = Post::get_posts( $query );
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
}
