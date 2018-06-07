<?php

namespace Pedestal\Registrations\Taxonomies;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Posts\Clusters\Geospaces\Localities\Locality;

class Taxonomies {

    private static $ptypes_types_tax = [
        'pedestal_story_type' => [
            'post_type'    => 'pedestal_story',
            'name'          => 'Stories',
            'singular_name' => 'Story',
        ],
        'pedestal_org_type' => [
            'post_type'    => 'pedestal_org',
            'name'          => 'Organizations',
            'singular_name' => 'Organization',
        ],
        'pedestal_place_type' => [
            'post_type'    => 'pedestal_place',
            'name'          => 'Places',
            'singular_name' => 'Place',
        ],
        'pedestal_locality_type' => [
            'post_type'    => 'pedestal_locality',
            'name'          => 'Localities',
            'singular_name' => 'Locality',
        ],
        'pedestal_slot_item_type' => [
            'post_type'    => 'pedestal_slot_item',
            'name'          => 'Slot Items',
            'singular_name' => 'Slot Item',
        ],
    ];

    protected $taxonomies = [];

    private static $taxonomy_names = [];

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
     * Set up taxonomy actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_taxonomies' ] );
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'template_redirect', [ $this, 'action_template_redirect' ], 9 );

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
                'query_var'         => true,
                'rewrite'           => [
                    'slug'       => strtolower( $tax_settings['name'] ) . '/types',
                    'with_front' => false,
                ],
                'capabilities'      => [
                    'manage_terms'  => 'manage_terms',
                    'edit_terms'    => 'manage_terms',
                    'delete_terms'  => 'manage_terms',
                    'assign_terms'  => 'manage_terms',
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
                $args['rewrite']['slug'] = '';
            }

            register_taxonomy( $tax_name, $tax_settings['post_type'], $args );

        endforeach;

        register_taxonomy( 'pedestal_source', [ 'pedestal_link' ], [
            'hierarchical'      => true,
            'public'            => true,
            'show_in_nav_menus' => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [
                'slug'              => 'sources',
                'with_front'        => true,
                'hierarchical'      => false,
            ],
            'capabilities'      => [
                'manage_terms'  => 'manage_terms',
                'edit_terms'    => 'edit_entities',
                'delete_terms'  => 'manage_terms',
                'assign_terms'  => 'edit_entities',
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

        // Register Categories
        $labels = [
            'name'                       => 'Category',
            'singular_name'              => 'Category',
            'menu_name'                  => 'Categories',
            'all_items'                  => 'All Categories',
            'parent_item'                => 'Parent Category',
            'parent_item_colon'          => 'Parent Category:',
            'new_item_name'              => 'New Category Name',
            'add_new_item'               => 'Add New Category',
            'edit_item'                  => 'Edit Category',
            'update_item'                => 'Update Category',
            'view_item'                  => 'View Category',
            'separate_items_with_commas' => 'Separate categories with commas',
            'add_or_remove_items'        => 'Add or remove categories',
            'choose_from_most_used'      => 'Choose from the most used',
            'popular_items'              => 'Popular Categories',
            'search_items'               => 'Search Categories',
            'not_found'                  => 'Not Found',
            'no_terms'                   => 'No categories',
            'items_list'                 => 'Categories list',
            'items_list_navigation'      => 'Categories list navigation',
        ];
        $args = [
            'labels'                     => $labels,
            'hierarchical'               => false,
            'rewrite'                    => [
                'slug'                       => 'categories',
                'with_front'                 => true,
                'hierarchical'               => false,
            ],
            'capabilities'               => [
                'manage_terms'           => 'manage_categories',
                'edit_terms'             => 'manage_categories',
                'delete_terms'           => 'manage_categories',
                'assign_terms'           => 'edit_entities',
            ],
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'single_option_taxonomy'     => true, // Make this taxonomy use radio buttons
        ];
        // Categories are only available for Denverite at the moment
        if ( 4 == get_current_blog_id() ) {
            register_taxonomy( 'pedestal_category', Types::get_entity_post_types(), $args );
        }

    }

    /**
     * Register rewrites for taxonomies
     */
    public function action_init_register_rewrites() {
        $taxonomy = get_taxonomy( 'pedestal_locality_type' );

        if ( empty( $taxonomy ) || ! is_object( $taxonomy ) || 'pedestal_locality_type' !== $taxonomy->name ) {
            return;
        }

        $terms = get_terms( [
            'taxonomy'   => $taxonomy->name,
            'hide_empty' => false,
            'fields'     => 'id=>slug',
        ] );
        $slugs = array_values( $terms );

        $slugs_str = '(' . implode( '|', $slugs ) . ')';
        add_rewrite_rule( '^' . $slugs_str . '/?$', 'index.php?pedestal_locality_type=$matches[1]&post_type=pedestal_locality', 'top' );
        add_rewrite_rule( '^' . $slugs_str . '/([^/]+)/?$', 'index.php?pedestal_locality_type=$matches[1]&name=$matches[2]&post_type=pedestal_locality', 'top' );
    }

    /**
     * Handle template redirection
     */
    public function action_template_redirect() {
        $queried_locality_type = get_query_var( 'pedestal_locality_type' );
        $name = get_query_var( 'name' );

        if ( ! $queried_locality_type || ! $name ) {
            return;
        }

        $locality = Locality::get_by_post_name( $name );
        if ( ! Types::is_locality( $locality ) ) {
            return;
        }

        // Check if the requested Locality URL uses the correct Locality Type at
        // the root-level of the URL. Without this, a Locality could be accessed
        // at any arbitrary URL part before the name.
        //
        // ## E.G.
        //
        // http://site.com/asdfasdfasdf/philadelphia/
        // redirects to
        // http://site.com/cities/philadelphia/
        $canonical_locality_type = $locality->get_locality_type_slug();
        if ( $queried_locality_type !== $canonical_locality_type ) {
            wp_safe_redirect( home_url( trailingslashit( $canonical_locality_type . '/' . $name ) ) );
            exit;
        }
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

    /**
     * Get a term ID, create term if it doesn't exist
     *
     * @param  string $taxonomy Taxonomy slug
     * @param  string $slug     Term slug
     * @param  string $name     Term singular name
     * @param  string $plural   Term plural name
     * @return int Term ID if successful
     */
    public static function get_or_create_term( string $taxonomy, string $slug, string $name, string $plural ) {
        if ( empty( $taxonomy ) || empty( $slug ) || empty( $name ) || empty( $plural ) ) {
            return;
        }

        $term = get_term_by( 'slug', $slug, $taxonomy );
        if ( ! empty( $term ) && is_a( $term, 'WP_Term' ) ) {
            $term_id = (int) $term->term_id;
            return $term_id;
        }

        $term_data = wp_insert_term( $name, $taxonomy, [
            'slug' => $slug,
        ] );

        if ( is_wp_error( $term_data ) ) {
            trigger_error( $term_data->get_error_message(), E_USER_ERROR );
            return;
        }

        if ( ! is_array( $term_data ) || ! isset( $term_data['term_id'] ) ) {
            return;
        }

        $term_id = $term_data['term_id'];
        if ( ! is_numeric( $term_id ) ) {
            return;
        }
        add_term_meta( $term_id, 'plural', $plural, true );
        return $term_id;
    }
}
