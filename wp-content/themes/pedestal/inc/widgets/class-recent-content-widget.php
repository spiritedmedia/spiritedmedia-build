<?php

namespace Pedestal\Widgets;

use Timber\Timber;
use \Pedestal\Registrations\Post_Types\Types;
use \Pedestal\Posts\Post;

class Recent_Content_Widget extends \WP_Widget {

    /**
     * Setup the widget
     */
    public function __construct() {
        $widget_options = [
            'description' => esc_html( 'The most recent stories or articles.' ),
        ];
        parent::__construct( 'pedestal-recent-content',
            esc_html( 'Recent Content' ),
            $widget_options
        );

        wp_enqueue_script(
            'recent-content-widget',
            get_template_directory_uri() . '/assets/dist/js/recent-content-widget-admin.js',
            [ 'jquery-ui-autocomplete' ],
            false,
            true
        );

        add_action( 'wp_ajax_recent-content-widget-cluster-autocomplete', [ $this, 'handle_ajax' ] );
    }

    /**
     * Handes the display of the widget
     *
     * @param  array $args     Arguments to modify the widget
     * @param  array $instance Saved data for this widget instance
     */
    public function widget( $args, $instance ) {
        $post_types = [];
        switch ( $instance['type'] ) {
            case 'stories':
                $post_types[] = 'pedestal_story';
                break;
            case 'articles':
                $post_types[] = 'pedestal_article';
                break;
            case 'factchecks':
                $post_types[] = 'pedestal_factcheck';
                break;
            case 'original':
                $post_types = Types::get_original_post_types();
                break;
        }

        if ( empty( $post_types ) ) {
            return;
        }

        $query_args = [
            'posts_per_page'         => intval( $instance['number'] ),
            'paged'                  => 1,
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'post_type'              => $post_types,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];

        if ( ! empty( $instance['clusters'] ) && is_array( $instance['clusters'] ) ) {
            $query_args['connected_items'] = $instance['clusters'];
            $query_args['connected_type'] = Types::get_cluster_connection_types( $post_types );
        }

        $posts = new \WP_Query( $query_args );

        if ( empty( $posts->posts ) ) {
            return false;
        }

        $items = '';
        foreach ( $posts->posts as $index => $post ) {
            $ped_post = Post::get( $post );
            if ( ! Types::is_post( $ped_post ) ) {
                continue;
            }
            $item_context = [
                'title'     => $ped_post->get_the_title(),
                'permalink' => $ped_post->get_the_permalink(),
                'type'      => $instance['type'],
                'thumbnail' => false,
            ];
            if ( $instance['show_thumbs'] ) {
                $feat_image = $ped_post->get_featured_image_html( 48, [
                    'class' => 'o-media__img recent-content-widget__thumbnail',
                    'sizes' => '48px',
                    'srcset' => [
                        'ratio'  => 1,
                        'widths' => 48,
                    ],
                ] );

                // If no featured image, use a placeholder graphic
                if ( ! $feat_image ) {
                    $feat_image = '<i class="o-media__img recent-content-widget__placeholder"></i>';
                }
                $item_context['thumbnail'] = $feat_image;
            }
            ob_start();
                Timber::render( 'widgets/recent-content-widget/recent-content-item.twig', $item_context );
            $items .= ob_get_clean();
        }

        $widget_context = [
            'before_widget' => $args['before_widget'],
            'after_widget'  => $args['after_widget'],
            'before_title'  => $args['before_title'],
            'after_title'   => $args['after_title'],
            'title'         => esc_html( $instance['title'] ),
            'title_link'    => esc_url( $instance['title_link'] ),
            'items'         => $items,
        ];
        Timber::render( 'widgets/recent-content-widget/recent-content-widget.twig', $widget_context );
    }

    /**
     * Render the admin form for populating widget data
     *
     * @param  array $instance Saved data for this widget instance
     */
    public function form( $instance ) {

        $instance = wp_parse_args( $instance, [
            'title'       => esc_html( PEDESTAL_BLOG_NAME . ' Originals' ),
            'title_link'  => '',
            'number'      => 10,
            'show_thumbs' => true,
            'type'        => 'original',
            'clusters'    => [],
        ] );

        $types = [
            'stories'    => 'Stories',
            'articles'   => 'Articles',
            'factchecks' => 'Factchecks',
            'original'   => 'Original Content',
        ];

        // Escape field ids and names
        $field_ids = $instance;
        $field_names = $instance;
        array_walk( $field_ids, function( &$v, $k ) {
            $v = $this->get_field_id( $k );
        } );
        array_walk( $field_names, function( &$v, $k ) {
            $v = $this->get_field_name( $k );
        } );

        $has_cluster_filter = false;
        $selected_clusters = '';
        if ( ! empty( $instance['clusters'] ) ) {
            $has_cluster_filter = true;
            foreach ( $instance['clusters'] as $cluster_id ) {
                $selected_clusters .= $this->get_selected_cluster_item( $cluster_id );
            }
        }

        $widget_context = [
            'title'              => $instance['title'],
            'title_id'           => $field_ids['title'],
            'title_name'         => $field_names['title'],

            'title_link'         => $instance['title_link'],
            'title_link_id'      => $field_ids['title_link'],
            'title_link_name'    => $field_names['title_link'],

            'types'              => $types,
            'selected_type'      => $instance['type'],
            'types_id'           => $field_ids['type'],
            'types_name'         => $field_names['type'],

            'selected_clusters'  => $selected_clusters,
            'has_cluster_filter' => $has_cluster_filter,

            'number'             => $instance['number'],
            'number_id'          => $field_ids['number'],
            'number_name'        => $field_names['number'],

            'show_thumbs'        => (bool) $instance['show_thumbs'],
            'show_thumbs_id'     => $field_ids['show_thumbs'],
            'show_thumbs_name'   => $field_names['show_thumbs'],
        ];
        Timber::render( 'widgets/recent-content-widget/admin-widget-form.twig', $widget_context );
    }

    /**
     * Logic for saving data when the widget is updated
     *
     * @param  array $new_instance New widget data
     * @param  array $instance     Old widget data
     * @return array               Update widget data to be saved to the database
     */
    public function update( $new_instance, $instance ) {
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['title_link'] = esc_url_raw( $new_instance['title_link'] );
        $instance['number'] = (int) $new_instance['number'];
        $instance['show_thumbs'] = $new_instance['show_thumbs'];
        $instance['type'] = sanitize_key( $new_instance['type'] );

        $instance['clusters'] = [];
        if ( ! empty( $_REQUEST['recent-content-widget-clusters'] ) ) {
            $clusters = $_REQUEST['recent-content-widget-clusters'];
            foreach ( $clusters as $id ) {
                if ( is_numeric( $id ) ) {
                    $instance['clusters'][] = intval( $id );
                }
            }
        }
        $instance['clusters'] = array_unique( $instance['clusters'] );

        return $instance;
    }

    /**
     * Handle the AJAX request from the autocomplete field
     *
     */
    public function handle_ajax() {
        if ( empty( $_REQUEST['action'] || empty( $_REQUEST['term'] ) ) ) {
            wp_send_json_error( [], 500 );
        }

        $query = new \WP_Query([
            'post_type'      => Types::get_cluster_post_types(),
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            's'              => sanitize_text_field( $_REQUEST['term'] ),
        ]);

        $output = [];
        foreach ( $query->posts as $post ) {
            $ped_post = Post::get( $post );
            if ( ! Types::is_post( $ped_post ) ) {
                continue;
            }
            $label = $ped_post->get_the_title();
            $label .= ' (' . $ped_post->get_type_name() . ')';

            $output[] = (object) [
                'label'         => $label,
                'title'         => $ped_post->get_the_title(),
                'type'          => $ped_post->get_type_name(),
                'post_id'       => $ped_post->get_id(),
                'selected_item' => $this->get_selected_cluster_item( $ped_post ),
            ];
        }
        wp_send_json_success( $output );
        die();
    }

    /**
     * Render the markup for a selected cluster item
     *
     * Used server side when the widget is first loaded and
     * passed to the AJAX request where JavaScript inserts the markup
     * straight into the DOM
     *
     * @param  integer|object $ped_post Cluster Post object or ID
     * @return string                   Rendered markup
     */
    public function get_selected_cluster_item( $ped_post = 0 ) {
        if ( ! Types::is_cluster( $ped_post ) ) {
            $ped_post = Post::get( $ped_post );
            if ( ! Types::is_cluster( $ped_post ) ) {
                return;
            }
        }

        $context = [
            'title'      => $ped_post->get_the_title(),
            'type'       => $ped_post->get_type_name(),
            'post_id'    => $ped_post->get_id(),
            'field_name' => $this->get_field_name( 'clusters' ) . '[]',
        ];

        ob_start();
        Timber::render( 'widgets/recent-content-widget/selected-cluster-item.twig', $context );
        return ob_get_clean();
    }
}
