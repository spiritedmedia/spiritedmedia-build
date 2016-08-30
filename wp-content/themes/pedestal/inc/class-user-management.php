<?php

namespace Pedestal;

use \Pedestal\Objects\User;

class User_Management {

    private static $instance;

    /**
     * Array of all producers
     *
     * @var array
     */
    private static $producers = [];

    /**
     * Roles considered to be Producers
     *
     * @var array
     */
    private static $producer_roles = [];

    /**
     * Sanitized URL bases for producer roles
     *
     * @var array
     */
    private static $producer_role_url_bases = [];

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new User_Management;
            self::$instance->setup_producer_roles();
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Setup the Producer roles
     */
    private function setup_producer_roles() {
        if ( empty( self::$producer_roles ) ) {
            $roles = self::get_roles();
            // Subscribers are not Producers, so exclude them
            unset( $roles['subscriber'] );
            self::$producer_roles = $roles;
        }

        // Set up producer role url bases
        if ( empty( self::$producer_role_url_bases ) ) {
            foreach ( self::$producer_roles as $role => $role_label ) {
                $role = self::get_role_url_base( $role );
                if ( in_array( $role, self::$producer_role_url_bases ) ) {
                    continue;
                }
                self::$producer_role_url_bases[] = $role;
            }
        }

        if ( empty( get_option( PEDESTAL_PREFIX . 'users_producers' ) ) ) {
            $this->update_option_producers();
        }
        self::$producers = get_option( PEDESTAL_PREFIX . 'users_producers' );

    }

    /**
     * Set up user management actions
     */
    private function setup_actions() {

        /**
         * Set up custom roles and capabilities
         */
        add_action( 'init', function() {
            // Clone the Featured Contributor role from Contributor
            $this->duplicate_role( 'contributor', 'feat_contributor', 'Featured Contributor', [] );

            // Allow contributors and feat. contributors to upload media
            $this->merge_role_caps( 'contributor', [
                'upload_files' => true,
            ] );
            $this->merge_role_caps( 'feat_contributor', [
                'upload_files' => true,
            ] );
        } );

        add_action( 'init', [ $this, 'action_author_permalink_role' ] );
        add_action( 'pre_get_posts', [ $this, 'action_author_permalink_name' ] );
        add_action( 'profile_update', [ $this, 'update_option_producers' ] );

    }

    /**
     * Set up user management filters
     */
    private function setup_filters() {

        /**
         * Remove author rewrite rules if no author name is present
         *
         * Allows for pages like /about/ to be at the base of the URL
         */
        add_filter( 'author_rewrite_rules', function( $rules ) {
            foreach ( $rules as $pattern => $substitution ) {
                if ( false === strpos( $substitution, 'author_name' ) ) {
                    unset( $rules[ $pattern ] );
                }
            }
            return $rules;
        }, 10, 1 );

        // @TODO Doesn't work?
        // add_filter( 'author_link', [ $this, 'filter_author_link' ], 10, 3 );

    }

    /**
     * Change the author permalinks
     */
    public function action_author_permalink_role() {
        global $wp_rewrite;
        $tag_pattern = '(' . implode( '|', self::$producer_role_url_bases ) . ')';

        add_rewrite_tag( '%author_role%', $tag_pattern );
        $wp_rewrite->author_base = '%author_role%';
    }

    /**
     * Use author display name for all producers except Featured Contributors
     *
     * N.B. The profile is still accessible at either the canonical sanitized
     * display name URL, or the username.
     */
    public function action_author_permalink_name() {
        if ( ! is_author() ) {
            return;
        }

        $display_name = get_query_var( 'author_name' );
        $user_map = array_column( self::$producers, 'display_name', 'username' );
        $username = array_search( $display_name, $user_map );

        if ( $username ) {
            $author = new User( $username );
        } else {
            return;
        }

        if ( 'feat_contributor' != $author->get_primary_role() ) {
            set_query_var( 'author_name', $username );
        }
        set_query_var( 'author', $author->get_id() );
    }

    /**
     * Filter the link returned by `get_author_posts_url()`
     *
     * @TODO Y U NO WORK? Nothing is returned.
     */
    public function filter_author_link( $link, $author_id, $author_nicename ) {
        $user = new User( $author_id );
        $link = str_replace( '%author_role%', $user->get_permalink_role(), $link );
        return $link;
    }

    /**
     * Update the site's Producers option
     */
    public function update_option_producers() {
        $i = 1;
        $producers = [];
        $users = self::get_users( $this->get_producers_query() );

        foreach ( $users as $user ) {
            $role = self::get_role_url_base( $user->get_primary_role() );
            $sanitized_username = sanitize_title( $user->get_user_login() );
            $sanitized_display_name = sanitize_title( $user->get_display_name() );
            $sanitized_role = sanitize_title( $role );

            // Prevent duplicate display name based URLs by appending a counter
            // to duplicates
            if ( in_array( $sanitized_display_name, $producers ) ) {
                $i++;
                $sanitized_display_name .= "-$i";
            }

            $producers[ $sanitized_username ] = [
                'username'     => $sanitized_username,
                'display_name' => $sanitized_display_name,
                'role'         => $sanitized_role,
            ];
        }

        update_option( PEDESTAL_PREFIX . 'users_producers', $producers );
    }

    /**
     * Create a new user in the database
     *
     * @param string $user_login
     * @return User|WP_Error
     */
    public static function create( $user_login ) {

        $user_id = wp_insert_user( [
            'user_login' => $user_login,
            'user_pass' => wp_generate_password(),
        ] );
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        return new User( $user_id );
    }

    /**
     * Get an array of user objects based on a WP_User_Query object
     *
     * @param  WP_User_Query $query
     * @return array
     */
    public static function get_users( $query ) {
        $users = [];
        if ( $query->get_results() ) {
            foreach ( $query->get_results() as $user ) {
                $users[] = new User( $user->ID );
            }
        }
        return $users;
    }

    /**
     * Get users from a comma-separated list of IDs
     *
     * @param  string $ids Comma-separated list of user IDs
     * @return array       Array of IDs and User objects
     */
    public static function get_users_from_csv( $ids ) {
        $user_ids = array_map( 'trim', explode( ',', $ids ) );
        $users = get_users( [ 'include' => array_map( 'intval', $user_ids ) ] );
        return [
            'ids'   => $user_ids,
            'users' => $users,
        ];
    }

    /**
     * Query the users who are producers
     *
     * @return WP_User_Query
     */
    private function get_producers_query() {
        global $wpdb;
        $blog_id = get_current_blog_id();
        $roles = self::$producer_roles;

        $args = [
            'meta_query' => [
                'relation' => 'OR',
            ],
            'count_total' => false,
        ];

        foreach ( $roles as $role ) {
            $args['meta_query'][] = [
                'key' => $wpdb->get_blog_prefix( $blog_id ) . 'capabilities',
                'value' => $role,
                'compare' => 'like',
            ];
        }

        $query = new \WP_User_Query( $args );
        return $query;
    }

    /**
     * Get a URL-safe name for a given role
     *
     * @param  string $role The name of the role
     * @return string
     */
    public static function get_role_url_base( $role ) {
        if ( ! array_key_exists( $role, self::get_roles() ) ) {
            return false;
        }
        switch ( $role ) {
            case 'feat_contributor':
                $url_role = 'featured-contributor';
                break;

            default:
                $url_role = 'about';
                break;
        }
        return sanitize_title( $url_role );
    }

    /**
     * Get a role's display name from its slug
     *
     * @param  string $slug
     * @return string
     */
    public static function get_role_display_name( $slug ) {
        global $wp_roles;
        return $wp_roles->roles[ $slug ]['name'];
    }

    /**
     * Get all of the role slugs
     *
     * @return array
     */
    private static function get_roles() {
        global $wp_roles;
        return $wp_roles->role_names;
    }

    /**
     * Get the producer roles
     *
     * @return array
     */
    private function get_producer_roles() {
        return self::$producer_roles;
    }

    /**
     * Get a list of capabilities for a role.
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $role Role name
     * @return array Array of caps for the role
     */
    private function get_role_caps( $role ) {
        $caps = [];
        $role_obj = get_role( $role );

        if ( $role_obj && isset( $role_obj->capabilities ) ) {
            $caps = $role_obj->capabilities;
        }

        return $caps;
    }

    /**
     * Add a new role
     *
     * Usage: $this->add_role( 'super-editor', 'Super Editor', [ 'level_0' => true ] );
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $role Role name
     * @param string $name Display name for the role
     * @param array $capabilities Key/value array of capabilities for the role
     */
    private function add_role( $role, $name, $capabilities ) {
        global $wp_user_roles;

        $role_obj = get_role( $role );

        if ( ! $role_obj ) {
            add_role( $role, $name, $capabilities );

            if ( ! isset( $wp_user_roles[ $role ] ) ) {
                $wp_user_roles[ $role ] = [
                    'name' => $name,
                    'capabilities' => $capabilities,
                ];
            }

            $this->_maybe_refresh_current_user_caps( $role );
        } else {
            $this->merge_role_caps( $role, $capabilities );
        }
    }

    /**
     * Add new or change existing capabilities for a given role
     *
     * Usage: $this->merge_role_caps( 'author', [ 'publish_posts' => false ] );
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $role Role name
     * @param array $caps Key/value array of capabilities for this role
     */
    private function merge_role_caps( $role, $caps ) {
        global $wp_user_roles;

        $role_obj = get_role( $role );

        if ( ! $role_obj ) {
            return;
        }

        $current_caps = (array) $this->get_role_caps( $role );
        $new_caps = array_merge( $current_caps, (array) $caps );

        foreach ( $new_caps as $cap => $role_can ) {
            if ( $role_can ) {
                $role_obj->add_cap( $cap );
            } else {
                $role_obj->remove_cap( $cap );
            }
        }

        if ( isset( $wp_user_roles[ $role ] ) ) {
            $wp_user_roles[ $role ]['capabilities'] = array_merge( $current_caps, (array) $caps );
        }

        $this->_maybe_refresh_current_user_caps( $role );
    }

    /**
     * Completely override capabilities for a given role
     *
     * Usage: $this->override_role_caps( 'editor', [ 'level_0' => false ] );
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $role Role name
     * @param array $caps Key/value array of capabilities for this role
     */
    private function override_role_caps( $role, $caps ) {
        global $wp_user_roles;

        $role_obj = get_role( $role );

        if ( ! $role_obj ) {
            return;
        }

        $role_obj->capabilities = (array) $caps;

        if ( isset( $wp_user_roles[ $role ] ) ) {
            $wp_user_roles[ $role ]['capabilities'] = (array) $caps;
        }

        $this->_maybe_refresh_current_user_caps( $role );
    }

    /**
     * Duplicate an existing role and modify some caps
     *
     * Usage: $this->duplicate_role( 'administrator', 'station-administrator', 'Station Administrator', [ 'manage_categories' => false ] );
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $from_role Role name
     * @param string $to_role_slug Role name
     * @param string $to_role_name Display name for the role
     * @param array $modified_caps Key/value array of capabilities for the role
     */
    private function duplicate_role( $from_role, $to_role_slug, $to_role_name, $modified_caps ) {
        $caps = array_merge( $this->get_role_caps( $from_role ), $modified_caps );
        $this->add_role( $to_role_slug, $to_role_name, $caps );
    }

    /**
     * Add capabilities to an existing role
     *
     * Usage: $this->add_role_caps( 'contributor', [ 'upload_files' ] );
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $role Role name
     * @param array $caps Capabilities to add to the role
     */
    private function add_role_caps( $role, $caps ) {
        $filtered_caps = [];
        foreach ( (array) $caps as $cap ) {
            $filtered_caps[ $cap ] = true;
        }
        $this->merge_role_caps( $role, $filtered_caps );
    }

    /**
     * Remove capabilities from an existing role
     *
     * Usage: $this->remove_role_caps( 'author', [ 'publish_posts' ] );
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $role Role name
     * @param array $caps Capabilities to remove from the role
     */
    private function remove_role_caps( $role, $caps ) {
        $filtered_caps = [];
        foreach ( (array) $caps as $cap ) {
            $filtered_caps[ $cap ] = false;
        }
        $this->merge_role_caps( $role, $filtered_caps );
    }

    /**
     * Force refreshes the current user's capabilities if they belong to the specified role.
     *
     * This is to prevent a race condition where the WP_User and its related
     * caps are generated before or roles changes.
     *
     * @link https://vip.wordpress.com/documentation/best-practices/custom-user-roles/
     *
     * @param string $role Role name
     */
    private function _maybe_refresh_current_user_caps( $role ) {
        if ( is_user_logged_in() && current_user_can( $role ) ) {
            wp_get_current_user()->get_role_caps();
        }
    }
}
