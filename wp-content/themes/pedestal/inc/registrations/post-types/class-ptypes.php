<?php

namespace Pedestal\Registrations\Post_Types;

use \Pedestal\Utils\Utils;

use \Pedestal\Posts\Post;

/**
 * Post Types
 */
class Types {

    /**
     * Instance
     *
     * @var object
     */
    private static $instance;

    /**
     * Post type settings
     *
     * @var array
     */
    protected $post_types = [];

    /**
     * Post type names
     *
     * @var array
     */
    private static $post_type_names = [];

    /**
     * Map post types to their classes
     *
     * Some WP default post types are added to class mapping by default. Some
     * other types are not included because they should be hidden.
     *
     * @var array
     */
    protected static $class_map = [
        'post'         => 'Posts\\Post',
        'page'         => 'Posts\\Page',
        'attachment'   => 'Posts\\Attachment',
    ];

    /**
     * WP core post types we override with our classes
     *
     * @var array
     */
    private static $overridden_types = [
        'attachment',
        'page',
    ];

    /**
     * Groups of post types
     *
     * @var array
     */
    public static $groups = [];

    /**
     * Set up the instance
     */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new Types;
            self::$instance->setup_types();
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Set up general post type actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_post_types' ] );
        add_action( 'init', [ $this, 'action_init_disable_default_post_type' ] );
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'manage_posts_custom_column', [ $this, 'action_manage_posts_custom_column' ], 10, 2 );
        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts_sortable_columns' ] );
        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts_originals' ] );
        add_action( 'template_redirect', [ $this, 'action_redirect_found_post_names' ], 10, 2 );
    }

    /**
     * Set up general post type filters
     */
    private function setup_filters() {

        add_filter( 'post_type_link', [ $this, 'filter_post_type_link' ], 10, 2 );
        add_filter( 'wp_unique_post_slug', [ $this, 'filter_wp_unique_post_slug' ], 10, 6 );
        add_filter( 'rewrite_rules_array', [ $this, 'filter_rewrite_rules_array' ] );
        add_filter( 'query_vars', [ $this, 'filter_query_vars' ] );
        add_filter( 'template_include', [ $this, 'filter_template_include' ] );

        foreach ( self::get_post_types() as $post_type ) {
            add_filter( "manage_{$post_type}_posts_columns", [ $this, 'filter_manage_posts_columns' ] );
            add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'filter_manage_posts_sortable_columns' ] );
        }

    }

    /**
     * Setup post type groups
     */
    private function setup_types() {
        self::$groups['general']  = General_Types::get_instance();
        self::$groups['entities'] = Entity_Types::get_instance();
        self::$groups['clusters'] = Cluster_Types::get_instance();
        self::$groups['slots']    = Slot_Types::get_instance();
    }

    /**
     * Get the post type settings
     *
     * @return array
     */
    protected function get_type_settings() {
        return $this->post_types;
    }

    /**
     * Register the custom post types
     */
    public function action_init_register_post_types() {

        foreach ( self::$groups as $group ) :

            foreach ( $group->get_type_settings() as $post_type => $settings ) :

                // @TODO
                // @codingStandardsIgnoreStart
                extract( $settings );
                // @codingStandardsIgnoreEnd

                // If the post type supports the editor, then make sure it supports
                // storing revisions
                if ( in_array( 'editor', $args['supports'] ) ) {
                    $args['supports'][] = 'revisions';
                }

                $args['labels'] = [
                    'name'                => $plural,
                    'singular_name'       => $singular,
                    'all_items'           => $plural,
                    'new_item'            => sprintf( esc_html__( 'New %s', 'pedestal' ), $singular ),
                    'add_new'             => sprintf( esc_html__( 'Add New', 'pedestal' ), $singular ),
                    'add_new_item'        => sprintf( esc_html__( 'Add New %s', 'pedestal' ), $singular ),
                    'edit_item'           => sprintf( esc_html__( 'Edit %s', 'pedestal' ), $singular ),
                    'view_item'           => sprintf( esc_html__( 'View %s', 'pedestal' ), $singular ),
                    'search_items'        => sprintf( esc_html__( 'Search %s', 'pedestal' ), $plural ),
                    'not_found'           => sprintf( esc_html__( 'No %s found', 'pedestal' ), $plural ),
                    'not_found_in_trash'  => sprintf( esc_html__( 'No %s found in trash', 'pedestal' ), $plural ),
                    'parent_item_colon'   => sprintf( esc_html__( 'Parent %s', 'pedestal' ), $singular ),
                    'menu_name'           => $plural,
                ];

                $this->post_types[] = $post_type;
                self::$class_map[ $post_type ] = $class;
                register_post_type( $post_type, $args );

            endforeach;

        endforeach;

    }

    /**
     * Disable the default post type
     *
     * Unsetting causes errors, so hiding is better.
     */
    public function action_init_disable_default_post_type() {
        global $wp_post_types;
        $wp_post_types['post']->public = false;
        $wp_post_types['post']->show_ui = false;
        $wp_post_types['post']->show_in_menu = false;
        $wp_post_types['post']->show_in_admin_bar = false;
        $wp_post_types['post']->show_in_nav_menus = false;
    }

    /**
     * Register rewrites
     */
    public function action_init_register_rewrites() {
        // Rewrite rules for our custom post types
        $post_types = '';
        $date_based_pagination_pattern = '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/page/?([0-9]{1,})/?$';
        foreach ( self::get_entity_post_types() as $ptype ) {
            $post_types .= '&post_type[]=' . $ptype;
        }

        add_rewrite_rule( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/ics/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5]&post_type=pedestal_event&ics=true', 'top' );
        add_rewrite_rule( $date_based_pagination_pattern, 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&paged=$matches[5]' . $post_types, 'top' );
        add_rewrite_rule( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)(/[0-9]+)?/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5]' . $post_types, 'top' );

        // Rewrite rules we don't want
        add_filter( 'post_rewrite_rules', '__return_empty_array' );
        foreach ( self::get_entity_post_types() as $post_type ) {
            add_filter( "{$post_type}_rewrite_rules", '__return_empty_array' );
        }
    }

    /**
     * Handle the output for a custom column
     *
     * @param string $column_name
     * @param int    $post_id
     */
    public function action_manage_posts_custom_column( $column_name, $post_id ) {

        $obj = \Pedestal\Posts\Post::get_by_post_id( $post_id );
        switch ( $column_name ) :
            case 'pedestal_external_url':
                echo '<a href="' . esc_url( $obj->get_external_url() ) . '">' . esc_url( $obj->get_external_url() ) . '</a>';
                break;
            case 'pedestal_event_start_time':
                echo esc_html( $obj->get_start_time( sprintf( __( '%s \a\t %s', 'pedestal' ), get_option( 'date_format' ), get_option( 'time_format' ) ) ) );
                break;
            case 'pedestal_event_end_time':
                echo esc_html( $obj->get_end_time( sprintf( __( '%s \a\t %s', 'pedestal' ), get_option( 'date_format' ), get_option( 'time_format' ) ) ) );
                break;
            case 'pedestal_event_venue_name':
                echo esc_html( $obj->get_venue_name() );
                break;
            case 'pedestal_entity_cluster_connections':
                $clusters_with_links = $obj->get_clusters_with_links();
                if ( ! empty( $clusters_with_links ) ) {
                    echo $clusters_with_links;
                } else {
                    echo '&mdash;';
                }
                break;
            case 'pedestal_entity_story_connections':
                if ( ! empty( $obj->has_story() ) ) {
                    echo $obj->get_clusters_with_links( 'story' );
                } else {
                    echo '&mdash;';
                }
                break;
            case 'pedestal_cluster_subscribers_count':
                echo esc_html__( $obj->get_following_users_count(), 'pedestal' );
                break;
            case 'pedestal_cluster_unsent_entities_count':
                $count = $obj->get_unsent_entities( [
                    'count_only' => true,
                ] );
                echo esc_html( $count );
                break;
            case 'pedestal_id':
                echo esc_html( $obj->get_id() );
                break;
        endswitch;
    }

    /**
     * Set up sortable post admin columns
     *
     * @param object $query WP_Query
     */
    public function action_pre_get_posts_sortable_columns( $query ) {
        if ( ! is_admin() ) {
            return;
        }

        $orderby = $query->get( 'orderby' );
        if ( ! $orderby ) {
            return;
        }

        $query->set( 'orderby', 'meta_value_num' );
        switch ( $orderby ) {
            case 'pedestal_cluster_subscribers_count':
                $query->set( 'meta_key', 'subscriber_count' );
                break;
            case 'pedestal_cluster_unsent_entities_count':
                $query->set( 'meta_key', 'unsent_entities_count' );
                break;
        }
    }

    /**
     * Modify the post types to query when accessing the Originals archive
     *
     * @param  WP_Query $query The WP Query to modify
     */
    public function action_pre_get_posts_originals( $query ) {
        if ( ! $query->is_main_query() ) {
            return;
        }

        if ( ! isset( $query->query_vars['pedestal_originals'] ) || 'originals' != $query->query_vars['pedestal_originals'] ) {
            return;
        }

        // Need to override the conditionals set by WordPress to better describe this request
        $query->is_archive = true;
        $query->is_home = false;
        $query->set( 'post_type', self::get_original_post_types() );
    }

    /**
     * Customize columns on the "Manage Posts" views
     *
     * @param array $columns
     */
    public function filter_manage_posts_columns( $columns ) {

        $new_columns = [];

        foreach ( $columns as $key => $label ) :
            $new_columns[ $key ] = $label;

            // Link columns
            if ( 'pedestal_link' == get_current_screen()->post_type && 'title' == $key ) {
                $new_columns['pedestal_external_url'] = esc_html__( 'External URL', 'pedestal' );
            }

            // Event columns
            if ( 'pedestal_event' == get_current_screen()->post_type ) {
                if ( 'title' == $key ) {
                    $new_columns['pedestal_event_start_time'] = esc_html__( 'Start Time', 'pedestal' );
                    $new_columns['pedestal_event_end_time'] = esc_html__( 'End Time', 'pedestal' );
                    $new_columns['pedestal_event_venue_name'] = esc_html__( 'Venue Name', 'pedestal' );
                }
                if ( 'coauthors' == $key ) {
                    $new_columns[ $key ] = esc_html__( 'Creator', 'pedestal' );
                }
            }

            // Entity columns
            if ( self::is_entity( get_current_screen()->post_type ) ) {
                if ( 'coauthors' === $key ) {
                    $new_columns['pedestal_entity_story_connections'] = esc_html__( 'Stories', 'pedestal' );
                    $new_columns['pedestal_entity_cluster_connections'] = esc_html__( 'Clusters', 'pedestal' );
                }
            }

            // Cluster columns
            if ( in_array( get_current_screen()->post_type, self::get_cluster_post_types() ) ) {
                if ( 'title' === $key ) {
                    $new_columns['pedestal_cluster_subscribers_count'] = '№ of Subscribers';
                    $new_columns['pedestal_cluster_unsent_entities_count'] = '№ of Unsent Entities';
                }
            }
        endforeach;

        $new_columns['pedestal_id'] = esc_html__( 'ID', 'pedestal' );
        return $new_columns;
    }

    /**
     * Set up the sortable post admin columns
     *
     * @link https://wordpress.stackexchange.com/questions/173438/initial-sort-order-for-a-sortable-custom-column-in-admin
     * @param array $columns
     */
    public function filter_manage_posts_sortable_columns( $columns ) {
        $sortable = [
            'pedestal_cluster_subscribers_count',
            'pedestal_cluster_unsent_entities_count',
        ];
        foreach ( $sortable as $column_name ) {
            $columns[ $column_name ] = [ $column_name, 1 ];
        }
        return $columns;
    }

    /**
     * Filter post type links
     *
     * @param string $link Permalink
     * @param object $post WP_Post
     */
    public function filter_post_type_link( $link, $post ) {

        if ( 'pedestal_link' === $post->post_type ) {
            $obj = new \Pedestal\Posts\Entities\Link( $post );
            $link = $obj->get_external_url();
        } elseif ( in_array( $post->post_type, self::get_entity_post_types() ) ) {

            $query = parse_url( $link, PHP_URL_QUERY );
            parse_str( $query, $args );
            // Generating a preview link
            if ( ! empty( $args['post_type'] ) && $args['post_type'] === $post->post_type ) {
                return $link;
            }

            // Sometimes the permalink is passed for preview links
            if ( 'publish' === $post->post_status && $post->post_name ) {
                $post_name = $post->post_name;
            } else {
                $post_name = '%' . $post->post_type . '%';
            }

            $unixtime = strtotime( $post->post_date );
            $date = explode( ' ', date( 'Y m d H i s', $unixtime ) );
            $search_replace = [
                '%year%'       => $date[0],
                '%monthnum%'   => $date[1],
                '%day%'        => $date[2],
                '%postname%'   => $post_name,
            ];

            $permalink_struct = '%year%/%monthnum%/%day%/%postname%/';
            $link = home_url( str_replace( array_keys( $search_replace ), array_values( $search_replace ), $permalink_struct ) );
        }

        return $link;
    }

    /**
     * Filter unique slugs to ensure slugs are unique across all post types
     *
     * @param string $slug          The post slug.
     * @param int    $post_id       Post ID.
     * @param string $post_status   The post status.
     * @param string $post_type     Post type.
     * @param int    $post_parent   Post parent ID
     * @param string $original_slug The original post slug.
     */
    public function filter_wp_unique_post_slug( $slug, $post_id, $post_status, $post_type, $post_parent, $original_slug ) {
        global $wpdb, $wp_rewrite;

        if ( ! in_array( $post_type, self::get_post_types() ) ) {
            return $slug;
        }

        $feeds = $wp_rewrite->feeds;
        if ( ! is_array( $feeds ) ) {
            $feeds = [];
        }

        $post_type_permastruct = array_diff( self::get_post_types(), self::get_date_based_post_types() );
        $permastruct_groups = [
            'date_based' => self::get_date_based_post_types(),
            'post_type'  => $post_type_permastruct,
        ];

        // Loop through the groups of permastructs looking for the post type and
        // break upon the first match - if a match is not found in any of the
        // permastruct groups, then return the unaltered slug
        foreach ( $permastruct_groups as $group ) {
            if ( in_array( $post_type, $group ) ) {
                $permastruct_group = $group;
                break;
            }
            return $slug;
        }

        // Use the selected permastruct group to determine the group of post
        // types between which duplicates are disallowed
        //
        // @TODO Not entirely sure why this triggers a WPCS error because we are
        // using $wpdb->prepare() correctly(?)
        //
        // @codingStandardsIgnoreStart
        $post_types_sql = "'" . implode( "','", array_map( 'sanitize_key', $permastruct_group ) ) . "'";
        $check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type IN ({$post_types_sql}) AND ID != %d LIMIT 1";
        $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_id ) );
        if ( $post_name_check || in_array( $slug, $feeds ) ) {
            $suffix = 2;
            do {
                $alt_post_name = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
                $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_id ) );
                $suffix++;
            } while ( $post_name_check );
            $slug = $alt_post_name;
        }
        // @codingStandardsIgnoreEnd

        return $slug;

    }

    /**
     * Modify the rewrite rules array to add /originals/
     *
     * @param  array  $rules The rewrite rules to modify
     * @return array         Modified rewrite rules array
     */
    public function filter_rewrite_rules_array( $rules = [] ) {
        global $wp_rewrite;
        $original_post_types = implode( ',', self::get_original_post_types() );
        add_rewrite_tag( '%pedestal_orignals%', '(originals)', 'pedestal_originals=' );
        $new_rules = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->root . '%pedestal_orignals%' );
        $rules = $new_rules + $rules;
        return $rules;
    }

    /**
     * Register new query vars with WordPress
     *
     * @param  array  $query_vars List of whitelisted query vars
     * @return array              Modified list of query vars
     */
    public function filter_query_vars( $query_vars = [] ) {
        $query_vars[] = 'pedestal_originals';
        return $query_vars;
    }

    /**
     * Modify which template is loaded for a given request
     *
     * @param  string $include Path to a PHP template
     * @return string          Possibly modified template path
     */
    public function filter_template_include( $include ) {
        if ( ! get_query_var( 'pedestal_originals' ) ) {
            return $include;
        }
        return locate_template( [ 'archive.php', 'index.php' ] );
    }

    /**
     * If we encounter a 404 and have a 'name' query_var set, try and find a post to redirect to
     */
    public function action_redirect_found_post_names() {
        $post_name = get_query_var( 'name' );

        if ( ! is_404() || ! $post_name ) {
            // Not a 404 page or we don't have a $post_name to search for, so bail
            return;
        }

        $post = Post::get_by_post_name( $post_name );
        if ( ! $post ) {
            // No post found, so bail
            return;
        }

        $permalink = $post->get_permalink();
        if ( ! $permalink ) {
            // We don't have a permalink to redirect to, so bail
            return;
        }

        wp_safe_redirect( $permalink, 301 );
        die();
    }

    /**
     * Get the name of the class associated with the given post type
     *
     * @param string $post_type The name of the post type
     */
    public static function get_post_type_class( $post_type ) {
        return '\\Pedestal\\' . self::$class_map[ $post_type ];
    }

    /**
     * Get an array of all cluster connection types
     *
     * @param array|string $post_types Post types to get connection types for. Defaults to all.
     * @param bool         $proto      Include the connection setup data in the return array?
     * @return array
     */
    public static function get_cluster_connection_types( $post_types = [], $proto = false ) {
        $clusters = self::$groups['clusters'];
        $types = $clusters->connection_types;
        $types_by_post_type = $clusters->connection_types_by_post_type;

        if ( ! empty( $post_types ) ) {
            $types = [];
            if ( is_string( $post_types ) ) {
                $post_types = [ $post_types ];
            }

            foreach ( $post_types as $post_type ) {
                if ( ! empty( $types_by_post_type[ $post_type ] ) ) {
                    $types = $types + $types_by_post_type[ $post_type ];
                }
            }
        }

        if ( $proto ) {
            return $types;
        }
        return array_keys( $types );
    }

    /**
     * Get the name of the user connection type by post type
     *
     * @uses self::get_connection_type()
     * @param  object    $post The post object or post type string to check.
     * @return string       The name of the user connection type
     */
    public static function get_user_connection_type( $post = null ) {
        return self::get_connection_type( 'user', $post );
    }

    /**
     * Get the name of the entity connection type by post type
     *
     * @uses self::get_connection_type()
     * @param object $post The post object or post type string to check.
     * @return string       The name of the entity connection type
     */
    public static function get_entity_connection_type( $post = null ) {
        return self::get_connection_type( 'entity', $post );
    }

    /**
     * Get the name of the connection type by post type
     *
     * @param  string $rel  The relationship to return. Can be one of either 'entity' or 'user'.
     * @param  mixed  $post The post object or post type string to check.
     * @return string       The name of the user connection type
     */
    public static function get_connection_type( $rel, $post ) {

        if ( is_object( $post ) ) {
            $post_type = self::get_post_type( $post );
        } elseif ( is_string( $post ) && in_array( $post, self::get_post_types() ) ) {
            $post_type = $post;
        } else {
            return false;
        }

        $sanitized_name = self::get_post_type_name( $post_type, true, true );
        switch ( $rel ) {
            case 'user':
                return $sanitized_name . '_to_users';
            case 'entity':
                return 'entities_to_' . $sanitized_name;
        }

        return false;

    }

    /**
     * Get an array of post types with label as value
     *
     * This is useful for setting up select field options based on post types.
     *
     * @param  array  $types Optional array of post types to use as keys
     * @param  string $label Post type label to use as value. Defaults to 'name'.
     * @return array
     */
    public static function get_post_types_with_label( $types = [], $label = 'name' ) {
        $types_with_labels = [];

        if ( empty( $types ) ) {
            $types = self::get_post_types();
        }

        foreach ( $types as $type ) {
            if ( ! post_type_exists( $type ) ) {
                continue;
            }
            $labels = self::get_post_type_labels( $type );
            $types_with_labels[ $type ] = $labels[ $label ];
        }
        return $types_with_labels;
    }

    /**
     * Get a post type's capabilities
     *
     * @link https://codex.wordpress.org/Function_Reference/get_post_type_capabilities
     *
     * @param  string $post_type  Post type slug
     * @param  array  $exclusions Exclude any caps?
     * @return array Post type capabilities map
     */
    public static function get_post_type_capabilities( string $post_type, $exclusions = [] ) {
        $obj = get_post_type_object( $post_type );
        if ( empty( $obj ) || empty( $obj->cap ) ) {
            return false;
        }
        $caps = (array) $obj->cap;
        if ( empty( $exclusions ) ) {
            $exclusions = [
                'read',
                'read_post',
                'edit_post',
                'delete_post',
            ];
        }
        foreach ( $exclusions as $exclusion ) {
            unset( $caps[ $exclusion ] );
        }
        $caps = array_flip( $caps );
        return $caps;
    }

    /**
     * Get a post type's label name
     *
     * @param  string  $post_type The post type
     * @param  bool $plural    Whether to return the plural name or singular.
     * @param  bool $sanitize  Whether to return a sanitized name
     * @return string            The label name of the post type
     */
    public static function get_post_type_name( $post_type, $plural = true, $sanitize = false ) {
        $labels = self::get_post_type_labels( $post_type );
        $name = $labels['singular_name'];
        if ( $plural ) {
            $name = $labels['name'];
        }
        if ( $sanitize ) {
            $name = Utils::sanitize_name( $name );
        }
        return $name;
    }

    /**
     * Get a post type's singular and plural labels in English
     *
     * Only works for our custom post types.
     *
     * @param  string $post_type Name of the post type
     * @return array             English singular and plural labels
     */
    public static function get_post_type_labels( $post_type ) {
        $obj = get_post_type_object( $post_type );
        return (array) $obj->labels;
    }

    /**
     * Get all the post types with date-based permalinks
     *
     * @param bool $sort  Sort alphabetically or not?
     * @return array
     */
    public static function get_date_based_post_types( $sort = true ) {
        $types = array_merge( self::get_entity_post_types(), [ 'pedestal_story' ] );
        if ( $sort ) {
            sort( $types );
        }
        return $types;
    }

    /**
     * Get all the cluster post types, minus stories
     */
    public static function get_cluster_post_types_sans_story() {
        $types = self::get_cluster_post_types();
        return Utils::remove_array_item( 'pedestal_story', $types );
    }

    /**
     * Get all post types that support the specified feature
     *
     * @param  string $feature Post type feature
     * @return array
     */
    public static function get_post_types_by_supported_feature( $feature ) {
        $types = [];

        if ( ! is_string( $feature ) ) {
            return [];
        }

        foreach ( self::get_post_types() as $type ) {
            if ( post_type_supports( $type, $feature ) ) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Get all the Geospace post types
     *
     * @param bool $sort  Sort alphabetically or not?
     *
     * @return array
     */
    public static function get_geospace_post_types( $sort = true ) {
        $types = [
            'pedestal_place',
            'pedestal_locality',
        ];
        if ( $sort ) {
            sort( $types );
        }
        return $types;
    }

    /**
     * Get all the cluster post types
     *
     * @param bool   $sort  Sort alphabetically or not?
     */
    public static function get_cluster_post_types( $sort = true ) {
        return self::get_post_types( 'clusters', $sort );
    }

    /**
     * Get all the entity post types
     *
     * @param bool   $sort  Sort alphabetically or not?
     */
    public static function get_entity_post_types( $sort = true ) {
        return self::get_post_types( 'entities', $sort );
    }

    /**
     * Get all post types with custom classes
     *
     * @param bool   $sort  Sort alphabetically or not?
     *
     * @return array Array of all post type names for which we've defined our own classes
     */
    public static function get_pedestal_post_types( $sort = true ) {
        $types = array_merge(
            self::get_post_types(),
            self::get_overridden_post_types()
        );
        if ( $sort ) {
            sort( $types );
        }
        return $types;
    }

    /**
     * Get the default WordPress post types for which we have custom classes
     *
     * @return array Some default WP post types
     */
    public static function get_overridden_post_types() {
        return self::$overridden_types;
    }

    /**
     * Determine whether a post type or object is an entity
     *
     * @param string|object $post_token The post type or object to check
     * @return bool
     */
    public static function is_entity( $post_token ) {
        if (
            ( is_string( $post_token ) && in_array( $post_token, self::get_entity_post_types() ) ) ||
            ( is_object( $post_token ) && is_a( $post_token, '\\Pedestal\\Posts\\Entities\\Entity' ) )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether a post type or object is a cluster
     *
     * @param string|object $post_token The post type or object to check
     * @return bool
     */
    public static function is_cluster( $post_token ) {
        if (
            ( is_string( $post_token ) && in_array( $post_token, self::get_cluster_post_types() ) ) ||
            ( is_object( $post_token ) && is_a( $post_token, '\\Pedestal\\Posts\\Clusters\\Cluster' ) )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether a post type or object is a Story
     *
     * @param string|object $post_token The post type or object to check
     * @return bool
     */
    public static function is_story( $post_token ) {
        if (
            ( is_string( $post_token ) && 'pedestal_story' === $post_token ) ||
            ( $post_token instanceof \Pedestal\Posts\Clusters\Story )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether a post type or object is a Locality
     *
     * @param string|object $post_token The post type or object to check
     * @return bool
     */
    public static function is_locality( $post_token ) {
        if (
            ( is_string( $post_token ) && 'pedestal_locality' === $post_token ) ||
            ( is_object( $post_token ) && is_a( $post_token, '\\Pedestal\\Posts\\Clusters\\Geospaces\\Localities\\Locality' ) )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether an object is one of our Post objects
     *
     * @param  mixed  $post_obj An object to test
     * @return bool
     */
    public static function is_post( $post_obj ) {
        return is_a( $post_obj, '\\Pedestal\\Posts\\Post' );
    }

    /**
     * Get original post types
     *
     * @param bool   $sort  Sort alphabetically or not?
     */
    public static function get_original_post_types( $sort = true ) {
        $general_types = self::$groups['general']->original_post_types;
        $entity_types = self::$groups['entities']->original_post_types;
        $types = array_merge( $general_types, $entity_types );
        if ( $sort ) {
            sort( $types );
        }
        return $types;
    }

    /**
     * Get the emailable post types
     *
     * @param  bool $sort Sort alphabetically or not?
     * @return array
     */
    public static function get_emailable_post_types( $sort = true ) {
        $types = array_merge(
            [ 'pedestal_newsletter' ],
            self::get_cluster_post_types(),
            self::get_post_types_by_supported_feature( 'breaking' )
        );
        if ( $sort ) {
            sort( $types );
        }
        return $types;
    }

    /**
     * Get all of our registered post types
     *
     * @param string $group Group name
     * @param bool   $sort  Sort alphabetically or not?
     */
    public static function get_post_types( $group = '', $sort = true ) {
        $types = [];
        if ( ! empty( $group ) ) {
            $types = array_keys( self::$groups[ $group ]->post_types );
        } else {
            foreach ( self::$groups as $group ) {
                $types = array_merge( array_keys( $group->post_types ), $types );
            }
        }
        if ( $sort ) {
            sort( $types );
        }
        return $types;
    }

    /**
     * Get a post type for internal use
     *
     * Handles our Post objects with a fallback to the core get_post_type() method.
     *
     * @param  int|object     $post Post ID or post object.
     * @return string|bool
     */
    public static function get_post_type( $post = '' ) {
        if ( ! empty( $post ) && is_a( $post, '\\Pedestal\\Posts\\Post' ) ) {
            return $post->get_post_type();
        }
        return get_post_type( $post );
    }
}
