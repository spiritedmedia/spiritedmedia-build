<?php

namespace Pedestal;

use function Pedestal\Pedestal;
use Pedestal\Frontend;
use Pedestal\Posts\Post;
use Pedestal\Objects\Stream;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Utils\Utils;
use Pedestal\Page_Cache;

class Featured_Posts {

    private $post_types = [
        'pedestal_article',
        'pedestal_link',
        'pedestal_whosnext',
        'pedestal_story',
        'pedestal_topic',
        'pedestal_person',
        'pedestal_org',
        'pedestal_embed',
        'pedestal_place',
        'pedestal_locality',
        'pedestal_factcheck',
    ];

    private $option_key = 'pedestal_featured_posts';

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Hook in to various actions
     */
    private function setup_actions() {
        // Needs to happen after post types are registered
        add_action( 'init', [ $this, 'action_init' ], 11 );
        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts' ] );
        add_action( 'wp_ajax_pedestal-featured-entities-placeholder', [ $this, 'action_wp_ajax_featured_entities_placeholder' ] );
        add_action( 'admin_print_scripts-appearance_page_pedestal_featured_posts', function() {
            wp_enqueue_script( 'featured-posts-admin', PEDESTAL_DIST_DIRECTORY_URI . '/js/featured-posts-admin.js', [ 'jquery' ], PEDESTAL_VERSION );
        });
        add_action( 'update_option_' . $this->option_key, function() {
            Page_Cache::purge_all();
        } );
    }

    /**
     * Setup submenu page and fields
     */
    public function action_init() {
        if ( ! is_admin() ) {
            return;
        }

        $fm_featured = new \Fieldmanager_Group( esc_html__( 'Featured Posts', 'pedestal' ), [
            'name'     => $this->option_key,
            'children' => [
                'feat-1' => $this->get_child_fields( 'Featured 1', 'feat-1' ),
                'feat-2' => $this->get_child_fields( 'Featured 2', 'feat-2' ),
                'feat-3' => $this->get_child_fields( 'Featured 3', 'feat-3' ),
            ],
        ] );
        $fm_featured->add_submenu_page( 'themes.php',
            esc_html__( 'Featured Entities', 'pedestal' ),
            esc_html__( 'Featured', 'pedestal' ),
            'manage_featured_posts'
        );
    }

    /**
     * Modify the main query to exclude featured posts from the stream
     *
     * @param  \WP_Query $query  The WordPress query we're modifying
     */
    public function action_pre_get_posts( $query ) {
        if ( ! $query->is_main_query() ) {
            return;
        }

        if ( $query->is_home() ) {
            $post_ids = $this->get_featured_post_ids();
            if ( $post_ids ) {
                $post_not_in = $query->get( 'post__not_in' );
                $post_not_in = array_merge( $post_not_in, $post_ids );
                $query->set( 'post__not_in', $post_not_in );
            }
        }
    }

    /**
     * Return the homepage description value for a given Post ID via AJAX
     */
    public function action_wp_ajax_featured_entities_placeholder() {
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( null, 500 );
            die();
        }

        $post_id  = absint( $_POST['post_id'] );
        $ped_post = Post::get( $post_id );
        if ( ! Types::is_post( $ped_post ) ) {
            wp_send_json_error( null, 500 );
            die();
        }

        $placeholder = $ped_post->get_homepage_description();
        wp_send_json_success( $placeholder );
        die();
    }

    /**
     * Get the Fieldmanager child fields
     *
     * @param  string $label Label for the field
     */
    private function get_child_fields( $label = '', $data_key = '' ) {
        $placeholder = '';
        $data        = get_option( $this->option_key );
        if ( is_array( $data ) && ! empty( $data[ $data_key ]['post'] ) ) {
            $post_id  = $data[ $data_key ]['post'];
            $ped_post = Post::get( $post_id );
            if ( Types::is_post( $ped_post ) ) {
                $placeholder = $ped_post->get_homepage_description();
            }
        }
        return new \Fieldmanager_Group( $label, [
            'label_macro' => [
                '%s',
                'post',
            ],
            'collapsible' => true,
            'collapsed'   => true,
            'children'    => [
                'post'        => new \Fieldmanager_Autocomplete( 'Post Selection', [
                    'name'           => 'post',
                    'description'    => 'Select a Post (anything except Events)',
                    'show_edit_link' => true,
                    'datasource'     => new \Fieldmanager_Datasource_Post( [
                        'query_args' => [
                            'post_type'      => $this->post_types,
                            'posts_per_page' => 15,
                            'post_status'    => [ 'publish' ],
                        ],
                    ] ),
                ] ),
                'post_title'  => new \Fieldmanager_Textfield( esc_html__( 'Title Override', 'pedestal' ), [
                    'name'        => 'post_title',
                    'description' => 'Customize the display title.',
                ] ),
                'description' => new \Fieldmanager_TextArea( 'Description Override', [
                    'name'        => 'description',
                    'description' => 'This will appear below the headline and image, when the article is featured. It defaults to the subhead of the article. If there is a homepage summary specificed on the article, that will override the subhead. If specified here, this text will override both.',
                    'attributes'  => [
                        'placeholder' => $placeholder,
                        'style'       => 'width: 100%;',
                        'rows'        => 4,
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
        $data   = get_option( $this->option_key );
        if ( ! empty( $data ) ) {
            // Keep track of the index so we know what position this featured post
            // should go to
            $index = 0;
            foreach ( $data as $item ) {
                if ( isset( $item['post'] ) && $item['post'] ) {
                    $key            = $item['post'];
                    $item['index']  = $index;
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
        // Get most recent original content
        $args     = [
            'post__not_in'   => Frontend::get_post_ids_excluded_from_home_stream(),
            'post_type'      => Types::get_original_post_types(),
            'post_status'    => 'publish',
            'posts_per_page' => $num,
            'fields'         => 'ids',
        ];
        $posts    = new \WP_Query( $args );
        $post_ids = $posts->posts;

        // Weave in featured posts
        // If the 2nd featured spot is manually overriden then we should reflect
        // that positioning here
        $featured_data     = $this->get_featured_data();
        $featured_post_ids = [];
        foreach ( $featured_data as $data ) {
            if ( ! empty( $data['post'] ) && is_int( $data['post'] ) ) {
                $index       = absint( $data['index'] );
                $new_post_id = $data['post'];

                // Prevent duplicate post IDs
                $post_ids = Utils::remove_array_item( $data['post'], $post_ids );
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
     * @return array Post data
     */
    protected function get_posts() {
        $args  = [
            'post_type'   => $this->post_types,
            'post_status' => 'publish',
            'post__in'    => $this->get_featured_post_ids(),
            'orderby'     => 'post__in',
        ];
        $posts = new \WP_Query( $args );
        if ( empty( $posts->posts ) ) {
            return [];
        }
        return $posts->posts;
    }

    /**
     * Get array of rendered featured post items
     *
     * @return array  Set of HTML strings for each of the featured posts
     */
    public function get_the_featured_posts() {
        $posts         = $this->get_posts();
        $featured_data = $this->get_featured_data();
        $stream        = new Stream;
        $items         = [];

        foreach ( $posts as $index => $post ) :
            if ( empty( $post ) ) {
                continue;
            }
            $index++;
            $ped_post = Post::get( $post );
            if ( ! Types::is_post( $ped_post ) ) {
                continue;
            }

            $default_context = [
                'post'                     => $post,
                'type'                     => $ped_post->get_type(),
                // Where is this stream item going to be displayed?
                '__context'                => 'featured',
                'primary_item'             => false,
                'stream_index'             => $index,
                'featured_image'           => '',
                'featured_image_src_width' => 584,
                'featured_image_sizes'     => [
                    '(max-width: 584px) 100vw',
                    '(min-width: 1025px) 308px',
                    '44vw',
                ],
                'featured_image_srcset'    => [
                    'ratio'  => 16 / 9,
                    'widths' => [ 308, 320, 480, 640, 800, 1024 ],
                ],
                'thumbnail_image'          => '',
                'thumbnail_image_sizes'    => [],
                'overline'                 => '',
                'overline_url'             => '',
                'title'                    => $ped_post->get_the_title(),
                'permalink'                => $ped_post->get_the_permalink(),
                'date_time'                => '',
                'machine_time'             => '',
                'description'              => $ped_post->get_homepage_description(),
                'show_meta_info'           => true,
                'author_names'             => '',
                'author_image'             => '',
                'author_link'              => '',
                'source_name'              => '',
                'source_image'             => '',
                'source_link'              => '',
            ];

            if ( 1 == $index ) {
                $default_context['featured_image_src_width'] = 942;
                $default_context['featured_image_sizes']     = [
                    '(max-width: 584px) 100vw',
                    '(min-width: 1025px) 640px',
                    '92vw',
                ];
            }

            $context = apply_filters( 'pedestal_stream_item_context', $default_context, $ped_post );

            if ( isset( $featured_data[ $post->ID ] ) ) {
                $override = $featured_data[ $post->ID ];
                if ( ! empty( $override['post_title'] ) ) {
                    $context['title'] = trim( $override['post_title'] );
                }

                if ( ! empty( $override['description'] ) ) {
                    $context['description'] = trim( $override['description'] );
                }
            }

            if ( 1 == $index ) {
                $context['primary_item'] = true;
            }

            ob_start();
            do_action( 'pedestal_before_featured_item_' . $index, $post );
            echo $stream->get_the_stream_item( $context );
            do_action( 'pedestal_after_featured_item_' . $index, $post );
            $html    = ob_get_clean();
            $items[] = compact( 'context', 'html' );
        endforeach;
        return $items;
    }
}
