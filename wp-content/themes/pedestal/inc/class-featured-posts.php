<?php

namespace Pedestal;

use function Pedestal\Pedestal;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Featured_Posts {
    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            // Late static binding (PHP 5.3+)
            $instance = new static();
            $instance->setup_hooks();
        }
        return $instance;
    }

    private function setup_hooks() {
        // Needs to happen after post types are registered
        add_action( 'init', [ $this, 'action_init' ], 11 );
        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts' ] );
        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
    }

    public function action_init() {
        if ( ! is_admin() ) {
            return;
        }

        $fm_featured = new \Fieldmanager_Group( esc_html__( 'Featured Entities', 'pedestal' ), [
            'name' => 'pedestal_featured_posts',
            'children' => [
                'feat-1' => $this->get_child_fields( 'Featured 1' ),
                'feat-2' => $this->get_child_fields( 'Featured 2' ),
                'feat-3' => $this->get_child_fields( 'Featured 3' ),
            ],
        ] );
        $fm_featured->add_submenu_page( 'themes.php',
            esc_html__( 'Featured Entities', 'pedestal' ),
            esc_html__( 'Featured', 'pedestal' ),
            'manage_spotlight'
        );
    }

    public function action_pre_get_posts( $query ) {
        if ( ! $query->is_main_query() ) {
            return;
        }

        if ( $query->is_home() ) {
            if ( $post_ids = $this->get_featured_post_ids() ) {
                $query->set( 'post__not_in', $post_ids );
            }
        }
    }

    public function filter_timber_context( $context ) {
        $featured_posts = $this->get_posts();
        if ( ! empty( $featured_posts ) ) {
            $context['featured_posts'] = [
                'items' => $featured_posts,
            ];
        }
        return $context;
    }

    /**
     * Get the Fieldmanager child fields
     *
     * @param  string $label Label for the field
     */
    private function get_child_fields( $label = '' ) {
        return new \Fieldmanager_Group( $label, [
            'label_macro' => [
                '%s',
                'post',
            ],
            'collapsible' => true,
            'collapsed' => true,
            'children' => [
                'post' => new \Fieldmanager_Autocomplete( 'Post Selection', [
                    'name' => 'post',
                    'description' => 'Select an Entity',
                    'show_edit_link' => true,
                    'datasource' => new \Fieldmanager_Datasource_Post( [
                        'query_args' => [
                            'post_type' => [ 'pedestal_article', 'pedestal_whosnext' ],
                            'posts_per_page' => 15,
                            'post_status' => [ 'publish' ],
                        ],
                    ] ),
                ] ),
                'post_title' => new \Fieldmanager_Textfield( esc_html__( 'Title Override', 'pedestal' ), [
                    'name' => 'post_title',
                    'description' => 'Customize the display title.',
                ] ),
                'description' => new \Fieldmanager_RichTextArea( 'Description Override', [
                    'name' => 'description',
                    'description' => 'Customize the description.',
                    'editor_settings' => [
                        'teeny' => true,
                        'media_buttons' => false,
                        'editor_height' => 300,
                    ],
                ] ),
            ],
        ] );
    }

    /**
     * Get the data from the Featured Posts Fieldmanager fields
     *
     * @return array
     */
    public function get_featured_data() {
        $output = [];
        $data = get_option( 'pedestal_featured_posts' );
        if ( ! empty( $data ) ) {
            // Keep track of the index so we know what position this featured post
            // should go to
            $index = 0;
            foreach ( $data as $item ) {
                if ( isset( $item['post'] ) && $item['post'] ) {
                    $key = $item['post'];
                    $item['index'] = $index;
                    $output[ $key ] = $item;
                }
                $index++;
            }
        }
        return $output;
    }

    /**
     * Get the IDs of the featured posts
     *
     * If manually featured posts are specified, then weave those in to the
     * array of most recent articles.
     *
     * If the 2nd position is overriden:
     *  - position 1 = the most recent article
     *  - position 2 = the manually selected article
     *  - position 3 = the 2nd most recent article
     *
     * @param  integer $num Number of post IDs to return
     * @return array Post IDs
     */
    public function get_featured_post_ids( $num = 3 ) {
        $num = absint( $num );
        if ( ! $num ) {
            $num = 3;
        }
        // Get most recent original articles
        $args = [
            'post_type' => 'pedestal_article',
            'post_status' => 'publish',
            'posts_per_page' => $num,
            'fields' => 'ids',
        ];
        $posts = new \WP_Query( $args );
        $post_ids = $posts->posts;

        // Weave in featured posts
        // If the 2nd featured spot is manually overriden then we should reflect
        // that positioning here
        $featured_data = $this->get_featured_data();
        $featured_post_ids = [];
        foreach ( $featured_data as $data ) {
            if ( ! empty( $data['post'] ) && is_int( $data['post'] ) ) {
                $index = absint( $data['index'] );
                $new_post_id = $data['post'];

                // Add the new post ID in to the array in the position it should
                // live, bumping other post IDs back
                array_splice( $post_ids, $index, 0, $new_post_id );
            }
        }

        // Trim the number of post IDs to the specificed $num
        $post_ids = array_slice( $post_ids, 0, $num );
        return $post_ids;
    }

    /**
     * Get an array of all the featured posts
     *
     * @return array Posts
     */
    public function get_posts() {
        $featured_data = $this->get_featured_data();
        $args = [
            'post_type' => [ 'pedestal_article', 'pedestal_whosnext' ],
            'post_status' => 'publish',
            'post__in' => $this->get_featured_post_ids(),
            'orderby' => 'post__in',
        ];
        $posts = new \WP_Query( $args );
        if ( empty( $posts->posts ) ) {
            return [];
        }
        $new_posts = [];
        foreach ( $posts->posts as $post ) {
            if ( isset( $featured_data[ $post->ID ] ) ) {
                $override = $featured_data[ $post->ID ];
                if ( ! empty( $override['post_title'] ) ) {
                    $post->post_title = trim( $override['post_title'] );
                }

                if ( ! empty( $override['description'] ) ) {
                    $post->post_excerpt = trim( $override['description'] );
                }
            }
            $new_posts[] = Post::get_instance( $post );
        }
        return $new_posts;
    }
}
