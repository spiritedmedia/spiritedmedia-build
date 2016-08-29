<?php

namespace Pedestal\Registrations\Taxonomies;

class Taxonomies {

    private static $ptypes_types_tax = [
        'pedestal_story_type' => [
            'post_types'    => [ 'pedestal_story' ],
            'name'          => 'Stories',
            'singular_name' => 'Story',
        ],
        'pedestal_org_type' => [
            'post_types'    => [ 'pedestal_org' ],
            'name'          => 'Organizations',
            'singular_name' => 'Organization',
        ],
        'pedestal_place_type' => [
            'post_types'    => [ 'pedestal_place' ],
            'name'          => 'Places',
            'singular_name' => 'Place',
        ],
        'pedestal_locality_type' => [
            'post_types'    => [ 'pedestal_locality' ],
            'name'          => 'Localities',
            'singular_name' => 'Locality',
        ],
        'pedestal_article_type' => [
            'post_types'    => [ 'pedestal_article' ],
            'name'          => 'Articles',
            'singular_name' => 'Article',
        ],
        'pedestal_slot_item_type' => [
            'post_types'    => [ 'pedestal_slot_item' ],
            'name'          => 'Slot Items',
            'singular_name' => 'Slot Item',
        ],
    ];

    protected $taxonomies = [];

    private static $taxonomy_names = [];

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Taxonomies;
            self::$instance->setup_actions();
        }
        return self::$instance;
    }

    /**
     * Set up taxonomy actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_taxonomies' ] );

        // Register additional fields for each of the Types taxonomies
        foreach ( self::$ptypes_types_tax as $tax_name => $v ) {
            add_action( "fm_term_{$tax_name}", function() use ( $tax_name ) {
                $fm = new \Fieldmanager_TextField( [
                    'name'        => 'plural',
                    'description' => esc_html__( 'Plural name for displaying multiple posts using this Type.', 'pedestal' ),
                    'required'    => true,
                ] );
                $fm->add_term_meta_box( esc_html__( 'Plural Name', 'pedestal' ), $tax_name );
            } );
        }
    }


    /**
     * Register custom taxonomies
     */
    public function action_init_register_taxonomies() {

        foreach ( self::$ptypes_types_tax as $tax_name => $tax_settings ) :

            $singular = sprintf( '%s Type', $tax_settings['singular_name'] );
            $plural = sprintf( '%s Types', $tax_settings['singular_name'] );

            $args = [
                'hierarchical'      => true,
                'public'            => true,
                'show_in_nav_menus' => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => true,
                'capabilities'      => [
                    'manage_terms'  => 'edit_posts',
                    'edit_terms'    => 'edit_posts',
                    'delete_terms'  => 'edit_posts',
                    'assign_terms'  => 'edit_posts',
                ],
                'labels'            => [
                    'name'                       => __( $plural, 'pedestal' ),
                    'singular_name'              => _x( $singular, 'taxonomy general name', 'pedestal' ),
                    'search_items'               => __( "Search {$plural}", 'pedestal' ),
                    'popular_items'              => __( "Popular {$plural}", 'pedestal' ),
                    'all_items'                  => __( "All {$plural}", 'pedestal' ),
                    'parent_item'                => __( "Parent {$singular}", 'pedestal' ),
                    'parent_item_colon'          => __( "Parent {$singular}:", 'pedestal' ),
                    'edit_item'                  => __( "Edit {$singular}", 'pedestal' ),
                    'update_item'                => __( "Update {$singular}", 'pedestal' ),
                    'add_new_item'               => __( "New {$singular}", 'pedestal' ),
                    'new_item_name'              => __( "New {$singular}", 'pedestal' ),
                    'separate_items_with_commas' => __( "{$plural} separated by comma", 'pedestal' ),
                    'add_or_remove_items'        => __( "Add or remove {$plural}", 'pedestal' ),
                    'choose_from_most_used'      => __( "Choose from the most used {$plural}", 'pedestal' ),
                    'menu_name'                  => __( "{$plural}", 'pedestal' ),
                ],
            ];

            if ( 'pedestal_locality_type' === $tax_name ) {
                $args['rewrite'] = [ 'with_front' => false ];
            }

            register_taxonomy( $tax_name, $tax_settings['post_types'], $args );

        endforeach;

        register_taxonomy( 'pedestal_source', [ 'pedestal_link' ], [
            'hierarchical'      => true,
            'public'            => true,
            'show_in_nav_menus' => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => true,
            'capabilities'      => [
                'manage_terms'  => 'edit_posts',
                'edit_terms'    => 'edit_posts',
                'delete_terms'  => 'edit_posts',
                'assign_terms'  => 'edit_posts',
            ],
            'labels'            => [
                'name'                       => __( 'Sources', 'pedestal' ),
                'singular_name'              => _x( 'Source', 'taxonomy general name', 'pedestal' ),
                'search_items'               => __( 'Search Sources', 'pedestal' ),
                'popular_items'              => __( 'Popular Sources', 'pedestal' ),
                'all_items'                  => __( 'All Sources', 'pedestal' ),
                'parent_item'                => __( 'Parent Source', 'pedestal' ),
                'parent_item_colon'          => __( 'Parent Source:', 'pedestal' ),
                'edit_item'                  => __( 'Edit Source', 'pedestal' ),
                'update_item'                => __( 'Update Source', 'pedestal' ),
                'add_new_item'               => __( 'New Source', 'pedestal' ),
                'new_item_name'              => __( 'New Source', 'pedestal' ),
                'separate_items_with_commas' => __( 'Sources separated by comma', 'pedestal' ),
                'add_or_remove_items'        => __( 'Add or remove Sources', 'pedestal' ),
                'choose_from_most_used'      => __( 'Choose from the most used Sources', 'pedestal' ),
                'menu_name'                  => __( 'Sources', 'pedestal' ),
            ],
        ] );

        register_taxonomy( 'pedestal_subscriptions', 'user', [
            'hierarchical'      => false,
            'public'            => false,
            'show_in_nav_menus' => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => true,
            'update_count_callback' => function( $terms ) {
                global $wpdb;

                foreach ( $terms as $term_taxonomy_id ) {

                    $callback = function( $wp_user_query ) use ( $term_taxonomy_id ) {
                        global $wpdb;
                        $tax_query = new \WP_Tax_Query( [
                            [
                                'taxonomy'       => 'pedestal_subscriptions',
                                'terms'          => [ (int) $term_taxonomy_id ],
                                'field'          => 'term_taxonomy_id',
                            ],
                        ] );
                        $clauses = $tax_query->get_sql( $wpdb->users, 'ID' );
                        $wp_user_query->query_from .= $clauses['join'];
                        $wp_user_query->query_where .= $clauses['where'];

                    };
                    add_action( 'pre_user_query', $callback );
                    $users = new \WP_User_Query( [ 'count_total' => true ] );
                    $user_count = $users->get_total();
                    remove_action( 'pre_user_query', $callback );

                    $wpdb->update( $wpdb->term_taxonomy, [ 'count' => $user_count ], [ 'term_taxonomy_id' => $term_taxonomy_id ] );

                }
            },
            'capabilities'      => [
                'manage_terms'  => 'list_users',
                'edit_terms'    => 'list_users',
                'delete_terms'  => 'do_not_allow',
                'assign_terms'  => 'list_users',
            ],
            'labels'            => [
                'name'                       => __( 'Subscriptions', 'pedestal' ),
                'singular_name'              => _x( 'Subscriptions', 'taxonomy general name', 'pedestal' ),
                'search_items'               => __( 'Search Subscriptions', 'pedestal' ),
                'popular_items'              => __( 'Popular Subscriptions', 'pedestal' ),
                'all_items'                  => __( 'All Subscriptions', 'pedestal' ),
                'parent_item'                => __( 'Parent Subscriptions', 'pedestal' ),
                'parent_item_colon'          => __( 'Parent Subscriptions:', 'pedestal' ),
                'edit_item'                  => __( 'Edit Subscriptions', 'pedestal' ),
                'update_item'                => __( 'Update Subscriptions', 'pedestal' ),
                'add_new_item'               => __( 'New Subscriptions', 'pedestal' ),
                'new_item_name'              => __( 'New Subscriptions', 'pedestal' ),
                'separate_items_with_commas' => __( 'Subscriptions separated by comma', 'pedestal' ),
                'add_or_remove_items'        => __( 'Add or remove Subscriptions', 'pedestal' ),
                'choose_from_most_used'      => __( 'Choose from the most used Subscriptions', 'pedestal' ),
                'menu_name'                  => __( 'Subscriptions', 'pedestal' ),
            ],
        ] );

    }

    /**
     * Get the Locality Type term ID given a slug
     *
     * @param  string $type_slug Locality Type slug
     * @return int
     */
    public static function get_locality_type_id( $type_slug ) {
        $term = get_term_by( 'slug', $type_slug, 'pedestal_locality_type' );
        return (int) $term->term_id;
    }
}
