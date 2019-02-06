<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;

use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Objects\MailChimp;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Clusters\Cluster;

class Email_Groups {

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into various actions
     */
    public function setup_actions() {
        // Add settings submenu page for each followable post type
        add_action( 'admin_menu', function() {
            $post_types = Types::get_mailchimp_integrated_post_types();
            foreach ( $post_types as $post_type ) {
                $labels = (object) Types::get_post_type_labels( $post_type );
                add_submenu_page(
                    'edit.php?post_type=' . $post_type,
                    $labels->singular_name . ' Email Group Settings',
                    'Settings',
                    'manage_options',
                    $this->get_admin_page_slug( $post_type ),
                    [ $this, 'render_settings_page' ]
                );
            }
        });

        add_action( 'wp_dashboard_setup', function() {
            wp_add_dashboard_widget(
                'pedestal_newsletter_groups_info',
                'Newsletters',
                [ $this, 'handle_dashboard_widget' ]
            );
        } );
    }

    /**
     * Hook into various filters
     */
    public function setup_filters() {
        add_filter( 'pedestal_cron_events', [ $this, 'filter_pedestal_cron_events' ] );
    }

    /**
     * Setup cron event for refreshing newsletter groups
     *
     * @param  array  $events Cron events
     * @return array          Modified cron events
     */
    public function filter_pedestal_cron_events( $events = [] ) {
        $events['refresh_email_groups'] = [
            'timestamp'  => date( 'U', mktime( date( 'H' ) + 1, 0, 0 ) ), // Next top of the hour
            'recurrence' => 'quarter-hourly',
            'callback'   => [ $this, 'handle_refresh_email_groups_cron_event' ],
        ];
        return $events;
    }

    /**
     * Handle refreshing the newsletter groups
     */
    public function handle_refresh_email_groups_cron_event() {
        $post_types = Types::get_mailchimp_integrated_post_types();
        foreach ( $post_types as $post_type ) {
            $group_category = $this->get_group_category_from_post_type( $post_type );
            // Delete the existing option
            $this->delete_option( $group_category );

            // Make a call to the group option to repopulate the data
            $this->get_groups( $group_category );
        }
    }

    /**
     * Handles rendering the admin screen
     */
    public function render_settings_page() {
        $mc                    = MailChimp::get_instance();
        $total_groups          = count( $mc->get_all_groups() );
        $remaining_groups_left = 60 - absint( $total_groups );
        $this->save_settings_page();

        $post_type = sanitize_text_field( $_GET['post_type'] );
        if ( ! Types::is_mailchimp_integrated_post_type( $post_type ) ) {
            wp_die( $post_type . ' is not integrated with MailChimp' );
        }
        $group_category   = $this->get_group_category_from_post_type( $post_type );
        $post_type_labels = (object) Types::get_post_type_labels( $post_type );
        $admin_page_slug  = $this->get_admin_page_slug( $post_type );
        $group_data       = $this->get_groups( $group_category );
        $groups           = $group_data->groups;
        $last_updated     = human_time_diff( $group_data->last_updated );

        $context                          = [];
        $context['singular_name']         = $post_type_labels->singular_name;
        $context['plural_name']           = $post_type_labels->name;
        $context['post_type']             = $post_type;
        $context['form']                  = [
            'action' => '?post_type=' . esc_attr( $post_type ) . '&page=' . esc_attr( $admin_page_slug ),
        ];
        $context['groups_left']           = $remaining_groups_left;
        $context['mailchimp_admin_url']   = $mc->get_admin_url( '/lists/' );
        $context['last_fetched_from_api'] = $last_updated;
        $context['fields']                = [];
        foreach ( $groups as $group ) {
            $id    = $group->id;
            $label = $group->name;
            $key   = 'field-' . $id;

            $context['fields'][] = [
                'id'          => $group->id,
                'key'         => $key,
                'label'       => $label,
                'subscribers' => number_format( $group->subscriber_count ),
            ];
        }

        $context['nonce_field']    = wp_nonce_field( $admin_page_slug, '_wpnonce', true, false );
        $context['primary_button'] = get_submit_button( 'Sync & Save', 'primary', 'sync-and-save', false );

        Timber::render( 'partials/admin/email-group-settings.twig', $context );
    }

    /**
     * Handles saving data entered in the admin screen
     */
    public function save_settings_page() {
        // No $_POST data, so bail
        if ( empty( $_POST ) ) {
            return false;
        }

        // We need the post type to get the proper admin page slug and verify
        // the referrer/nonce
        if ( empty( $_POST['post_type'] ) ) {
            return false;
        }
        $post_type = sanitize_text_field( $_POST['post_type'] );
        if ( ! Types::is_mailchimp_integrated_post_type( $post_type ) ) {
            return false;
        }

        // Check for cross-site request forgery
        $action = $this->get_admin_page_slug( $post_type );
        if ( ! check_admin_referer( $action ) ) {
            wp_die( 'Bad nonce!' );
        }

        // We're syncing from APIs
        if ( isset( $_POST['sync-and-save'] ) ) {
            // All we need to do is delete the options and they will be regenerated
            // and fetched automatically when the options aren't present
            $group_category = $this->get_group_category_from_post_type( $post_type );
            $this->delete_option( $group_category );
        }
    }

    /**
     * Render the Newsletter Groups dashboard widget
     */
    public function handle_dashboard_widget() {
        $group_data       = $this->get_groups( 'Newsletters' );
        $last_updated     = $group_data->last_updated;
        $context          = [];
        $context['items'] = [];
        foreach ( $group_data->groups as $group ) {
            $context['items'][] = [
                'id'            => $group->id,
                'name'          => $group->name,
                'count'         => $group->subscriber_count,
                'time_absolute' => date( 'Y-m-d H:i:s', $last_updated ),
                'time_relative' => human_time_diff( $last_updated ),
            ];
        }
        Timber::render( 'partials/admin/dash-widget-email-primary-lists-count.twig', $context );
    }

    /**
     * Get the option key for a given group category name
     * @param  string $group_category Name of the group category
     * @return stirng                 'mailchimp-<$group_category>-groups'
     */
    public function get_groups_option_key( $group_category = '' ) {
        return 'mailchimp-' . sanitize_title( $group_category ) . '-groups';
    }

    /**
     * Generate a unique admin page slug for the settings page from a given post type slug
     *
     * NOTE: If the amdmin page slugs aren't unique per post type then the sub menus will go wonky
     * ( Stories setting page listed under Newsletters for example )
     *
     * @param  string $post_type Post type slug
     * @return string            Admin page slug
     */
    public function get_admin_page_slug( $post_type = '' ) {
        return sanitize_title( $post_type . '-email-group-settings' );
    }

    /**
     * Get all groups from a given group category from the local cache
     *
     * @param  string  $group_category Name of the group category
     * @return object Email group data and last updated timestamp
     */
    public function get_groups( $group_category = '' ) {
        if ( empty( $group_category ) ) {
            throw new \Exception( 'No name of $group_category passed!' );
        }
        $key    = $this->get_groups_option_key( $group_category );
        $groups = get_option( $key );
        if ( ! empty( $groups ) ) {
            return $groups;
        }

        // Not cached locally so we need to fetch it
        $mc         = MailChimp::get_instance();
        $group_data = $mc->get_groups( $group_category );
        $groups     = (object) [
            'last_updated' => time(),
            'groups'       => $group_data,
        ];
        update_option( $key, $groups );
        return $groups;
    }

    /**
     * Get a single MailChimp group object from our local cache or
     * go out and fetch it from the API
     *
     * @param  string $group_id       Name or id of the group to get
     * @param  string $group_category Name of the group category
     * @return Object|false           MailChimp group object or false
     */
    public function get_group( $group_id = '', $group_category = '' ) {
        $groups = $this->get_groups( $group_category );
        foreach ( $groups->groups as $group ) {
            if ( $group->id == $group_id ) {
                return $group;
            }
            if ( $group->name == $group_id ) {
                return $group;
            }
        }
        return false;
    }

    /**
     * Get group id from a given Newsletter group name
     *
     * @param  string  $name Name of the newsletter group
     * @return string|0     The id property, 0 if nothing found
     */
    public function get_newsletter_group_id( $name = '' ) {
        $group_data = $this->get_groups( 'Newsletters' );
        foreach ( $group_data->groups as $group ) {
            if ( ! is_object( $group ) || ! isset( $group->id ) ) {
                continue;
            }
            if ( $name == $group->name ) {
                return $group->id;
            }
        }

        return 0;
    }

    /**
     * Get the subscriber count for a given email group name or id
     *
     * @param  string  $name Name or id of the MailChimp group
     * @return integer       Number of subscribers
     */
    public function get_subscriber_count( $group_id = '', $group_category = '' ) {
        $group = $this->get_group( $group_id, $group_category );
        if ( is_object( $group ) && isset( $group->subscriber_count ) ) {
            return intval( $group->subscriber_count );
        }
        return 0;
    }

    /**
     * Deletes all group options for a given group category name
     *
     * @param  string $group_category Name of the group category to delete option for
     */
    public function delete_option( $group_category = '' ) {
        $key = $this->get_groups_option_key( $group_category );
        delete_option( $key );
        // Since the option is autoloaded we need to delete the `alloptions`
        // cache to prevent race conditions with Redis object caches
        // See https://core.trac.wordpress.org/ticket/31245#comment:26
        wp_cache_delete( 'alloptions', 'options' );
    }

    /**
     * Get the group category name from a post type slug
     *
     * @param  string $post_type Slug of post type
     * @return string|False      Group category name or false
     */
    public function get_group_category_from_post_type( $post_type = '' ) {
        if ( ! Types::is_mailchimp_integrated_post_type( $post_type ) ) {
            throw new \Exception( $post_type . ' is not integrated with MailChimp' );
        }
        $labels = Types::get_post_type_labels( $post_type );
        if ( empty( $labels ) || ! is_array( $labels ) || ! isset( $labels['name'] ) ) {
            throw new \Exception( 'Problem getting post type labels for ' . $post_type );
        }
        return $labels['name'];
    }
}
