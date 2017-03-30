<?php

namespace Pedestal;

use Pedestal\Utils\Utils;
use Pedestal\Objects\User;
use Pedestal\Registrations\Post_Types\Types;

class User_Management {

    private static $instance;

    /**
     * Roles considered to be Producers
     *
     * @var array
     */
    private static $producer_roles = [];

    /**
     * New roles and their labels
     *
     * @var array
     */
    private static $roles_labels = [
        'reporter'           => 'Reporter',
        'reporter_assoc'     => 'Associate Reporter',
        'sales_manager'      => 'Sales Manager',
        'sales_assoc'        => 'Sales Associate',
        'feat_contributor'   => 'Featured Contributor',
        'reporter_freelance' => 'Freelance Reporter',
    ];

    /**
     * WP default roles to rename
     *
     * @var array
     */
    public static $rename_roles = [
        'editor'      => 'reporter',
        'contributor' => 'reporter_assoc',
        'author'      => 'reporter_assoc',
    ];

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
    }

    /**
     * Set up user management actions
     */
    private function setup_actions() {

        // Set up custom roles and capabilities
        add_action( 'init', [ $this, 'action_author_permalink_role' ] );
        add_action( 'init', [ $this, 'action_init_edit_builtin_caps' ] );
        add_action( 'load-users.php', [ $this, 'action_load_users_setup_roles' ] );
        add_action( 'profile_update', [ $this, 'action_profile_update' ], 10, 1 );

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

        // Allow admins of the site to manage users again without making them super admins
        add_filter( 'user_has_cap', function( $allcaps, $caps, $args, $user ) {
            global $pagenow;
            $whitelisted_pages = [ 'user-new.php', 'users.php', 'user-edit.php' ];
            if ( ! in_array( $pagenow, $whitelisted_pages ) ) {
                return $allcaps;
            }
            if ( 'manage_network_users' != $args[0] || ! isset( $args[0] ) ) {
                return $allcaps;
            }
            if ( $allcaps['delete_users'] && $allcaps['create_users'] ) {
                $allcaps['manage_network_users'] = true;
            }

            return $allcaps;
        }, 10, 4 );

        add_filter( 'author_link', function( $url ) {
            $url = str_replace( '%author_role%', 'about', $url );
            return $url;
        });

        /**
         * Override 404 template if the author has a display name slug
         */
        add_filter( 'template_include', function( $template = '' ) {
            global $wp_query;
            $display_name_slug = get_query_var( 'author_name' );
            if ( ! $display_name_slug ) {
                return $template;
            }
            $users = get_users( [
                'meta_key' => 'display_name_slug',
                'meta_value' => $display_name_slug,
            ] );

            if ( empty( $users[0] ) || ! is_array( $users ) ) {
                return $template;
            }
            $user = $users[0];
            $user = new User( $user );
            $wp_query->set( 'author', $user->get_id() );
            if ( 'subscriber' == $user->get_primary_role() ) {
                return $template;
            }
            status_header( 200 );
            $wp_query->is_404 = false;
            return locate_template( [ 'author.php' ] );
        });

    }

    /**
     * Edit capabilities for builtin post types
     */
    public function action_init_edit_builtin_caps() {
        global $wp_post_types;
        $wp_post_types['attachment']->cap = (object) [
            'read'                   => 'read',
            'create_posts'           => 'upload_files',
            'edit_post'              => 'edit_attachment',
            'read_post'              => 'read_attachment',
            'delete_post'            => 'delete_attachment',
            'edit_posts'             => 'manage_uploads',
            'edit_others_posts'      => 'manage_uploads',
            'publish_posts'          => 'manage_uploads',
            'read_private_posts'     => 'manage_uploads',
            'delete_posts'           => 'manage_uploads',
            'delete_private_posts'   => 'manage_uploads',
            'delete_published_posts' => 'manage_uploads',
            'delete_others_posts'    => 'manage_uploads',
            'edit_private_posts'     => 'manage_uploads',
            'edit_published_posts'   => 'manage_uploads',
        ];
    }

    /**
     * Set up custom roles and capabilities
     */
    public function action_load_users_setup_roles() {
        $tablepress_caps = [
            'tablepress_edit_tables'           => true,
            'tablepress_delete_tables'         => true,
            'tablepress_list_tables'           => true,
            'tablepress_add_tables'            => true,
            'tablepress_copy_tables'           => true,
            'tablepress_import_tables'         => true,
            'tablepress_export_tables'         => true,
            'tablepress_access_options_screen' => true,
            'tablepress_access_about_screen'   => true,
        ];

        // "Rename" some roles
        foreach ( static::$rename_roles as $old_role => $new_role ) {
            $get_users_args = [
                'fields' => [ 'ID' ],
                'role' => $old_role,
            ];
            if ( ! get_role( $new_role ) ) {
                $this->duplicate_role( $old_role, $new_role, self::$roles_labels[ $new_role ], [] );
            }
            // Remove the old role if there are no users with that role assigned
            //
            // @TODO This user query should probably be cached or even stored in
            // database as an option, because once the role is deleted it should
            // never come back...
            if ( get_role( $old_role ) && empty( get_users( $get_users_args ) ) ) {
                remove_role( $old_role );
            }
        }

        // Sales Manager
        $caps_sales_manager =
            [ 'manage_uploads' => true ] +
            Types::get_post_type_capabilities( 'pedestal_slot_item' ) +
            Types::get_post_type_capabilities( 'pedestal_event' );
        $this->add_role( 'sales_manager', self::$roles_labels['sales_manager'], $caps_sales_manager );

        // Sales Associate
        $this->add_role( 'sales_assoc', self::$roles_labels['sales_assoc'], [
            'publish_slots'        => true,
            'edit_slots'           => true,
            'edit_published_slots' => true,
            'edit_events'          => true,
        ] );

        // Freelance Reporter
        $this->add_role( 'reporter_freelance', self::$roles_labels['reporter_freelance'], [
            'edit_articles' => true,
        ] );

        // Featured Contributor
        $this->duplicate_role( 'reporter_freelance', 'feat_contributor', self::$roles_labels['feat_contributor'], [] );

        // Administrators and Reporters
        $caps_admin_reporters = [
            'send_emails'         => '',
            'manage_spotlight'    => '',
            'manage_pinned'       => '',
            'manage_terms'        => '',
            'manage_distribution' => '',
            'manage_uploads'      => '',
        ];
        $ptypes_admin_reporters = array_merge(
            [ 'pedestal_newsletter' ],
            Types::get_entity_post_types(),
            Types::get_cluster_post_types()
        );
        foreach ( $ptypes_admin_reporters as $post_type ) {
            $caps = Types::get_post_type_capabilities( $post_type );
            if ( empty( $caps ) ) { continue; }
            $caps_admin_reporters += $caps;
        }
        $caps_admin_reporters = array_map( '__return_true', $caps_admin_reporters );
        $caps_admin = $caps_admin_reporters + $caps_sales_manager;
        $caps_admin += [
            'create_users'   => true,
            'merge_clusters' => true,
        ];
        $caps_admin = array_map( '__return_true', $caps_admin );
        $this->merge_role_caps( 'administrator', $caps_admin );
        $this->merge_role_caps( 'reporter', $caps_admin_reporters );

        // Associate Reporter
        $caps_reporter_assoc = [
            'edit_entities' => true,
            'edit_articles' => true,
            'edit_events'   => true,
            'edit_clusters' => true,
        ] + $tablepress_caps;
        $this->merge_role_caps( 'reporter_assoc', $caps_reporter_assoc );

        // Common capabilities
        foreach ( static::get_roles() as $role_name => $role_label ) {
            $basic_caps = [
                'read'                             => true,
                'manage_options'                   => false,
                'switch_themes'                    => false,
                'customize'                        => false,
                'delete_site'                      => false,
                'activate_plugins'                 => false,
                'import'                           => false,
                'export'                           => false,
                'unfiltered_upload'                => false,
                'manage_categories'                => false,
                'tablepress_access_options_screen' => false,
                'tablepress_access_about_screen'   => false,
            ];
            if ( 'subscriber' !== $role_name ) {
                $basic_caps['upload_files'] = true;
                $basic_caps['edit_posts'] = true;
            }
            $this->merge_role_caps( $role_name, $basic_caps );

            // Only Admins can manage users and manage Pages
            if ( 'administrator' !== $role_name ) {
                $non_admin_caps = [
                    'create_users',
                    'edit_users',
                    'delete_users',
                    'list_users',
                    'promote_users',
                    'add_users',
                    'remove_users',
                ];
                foreach ( Types::get_post_type_capabilities( 'page' ) as $key => $value ) {
                    $non_admin_caps[] = $key;
                }
                $this->remove_role_caps( $role_name, $non_admin_caps );
            }
        }
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
     * Filter the link returned by `get_author_posts_url()`
     *
     * @TODO Y U NO WORK? Nothing is returned.
     */
    public function filter_author_link( $link, $author_id, $author_nicename ) {
        $user = new User( $author_id );
        $link = str_replace( '%author_role%', 'about', $link );
        return $link;
    }

    /**
     * Create a slug based on the user's display name
     * Store it in user meta to be used later
     * @param  integer $user_id The ID of the user to update
     */
    public function action_profile_update( $user_id = 0 ) {
        if ( empty( $_POST['display_name'] ) ) {
            return;
        }
        $display_name_slug = sanitize_title( $_POST['display_name'] );
        update_user_meta( $user_id, 'display_name_slug', $display_name_slug );
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
     * Get all of the role slugs and their labels
     *
     * @return array [ 'slug' => 'label' ]
     */
    public static function get_roles() {
        global $wp_roles;
        return $wp_roles->role_names;
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
