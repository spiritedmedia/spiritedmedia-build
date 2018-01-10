<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;

use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Objects\{
    ActiveCampaign,
    MailChimp
};
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Clusters\Cluster;

class Newsletter_Groups {

    /**
     * Slug of the settings page
     *
     * @var string
     */
    private $admin_page_slug = 'pedestal-activecampaign-list-settings';

    /**
     * The meta key where we store all of the MailChimp Newsletter Groups
     */
    private $mc_groups_option_key = 'mailchimp-newsletter-groups';

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
        add_action( 'admin_menu', function() {
            add_submenu_page(
                'edit.php?post_type=pedestal_newsletter',
                'Newsletter Settings',
                'Settings',
                'manage_options',
                $this->admin_page_slug,
                [ $this, 'render_settings_page' ]
            );
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
        $events['refresh_newsletter_groups'] = [
            'timestamp'  => date( 'U', mktime( date( 'H' ) + 1, 0, 0 ) ), // Next top of the hour
            'recurrence' => 'hourly',
            'callback'   => [ $this, 'handle_refresh_newsletter_groups_cron_event' ],
        ];
        return $events;
    }

    /**
     * Handle refreshing the newsletter groups
     */
    public function handle_refresh_newsletter_groups_cron_event() {
        // Delete the existing options
        $this->delete_options();

        // Make a call to the newsletter groups option to repopulate the data
        $this->get_all_newsletter_groups();
    }

    /**
     * Handles rendering the admin screen
     */
    public function render_settings_page() {
        $ac = ActiveCampaign::get_instance();
        $mc = MailChimp::get_instance();
        $total_groups = count( $mc->get_all_groups() );
        $remaining_groups_left = 60 - absint( $total_groups );
        $this->save_settings_page();
        $group_data = $this->get_all_newsletter_groups();
        $groups = $group_data->groups;
        $last_updated = human_time_diff( $group_data->last_updated );
        $address_id = $ac->get_address_id();

        $context = [];
        $context['form'] = [
            'action' => '?post_type=pedestal_newsletter&page=' . esc_attr( $this->admin_page_slug ),
        ];
        $context['groups_left'] = $remaining_groups_left;
        $context['mailchimp_admin_url'] = $mc->get_admin_url( '/lists/' );
        $context['last_fetched_from_api'] = $last_updated;
        $context['fields'] = [];
        foreach ( $groups as $group ) {
            $id = $group->id;
            $label = $group->name;
            $key = 'field-' . $id;

            $context['fields'][] = [
                'id'          => $group->id,
                'key'         => $key,
                'label'       => $label,
                'subscribers' => number_format( $group->subscriber_count ),
            ];
        }

        // Tack this on here since we don't have anywhere else to put it and it is unique per site
        $context['fields'][] = [
            'id'    => absint( $address_id ),
            'key'   => 'field-address-id',
            'label' => 'ActiveCampaign Address ID',
        ];
        $context['nonce_field'] = wp_nonce_field( $this->admin_page_slug, '_wpnonce', true, false );
        $context['primary_button'] = get_submit_button( 'Sync & Save', 'primary', 'sync-and-save', false );

        Timber::render( 'partials/admin/newsletter-settings.twig', $context );
    }

    /**
     * Handles saving data entered in the admin screen
     */
    public function save_settings_page() {
        // No $_POST data, so bail
        if ( empty( $_POST ) ) {
            return false;
        }

        // Check for cross-site request forgery
        $action = $this->admin_page_slug;
        if ( ! check_admin_referer( $action ) ) {
            wp_die( 'Bad nonce!' );
        }

        // We're syncing from APIs
        if ( isset( $_POST['sync-and-save'] ) ) {
            // All we need to do is delete the options and they will be regenerated
            // and fetched automatically when the options aren't present
            $this->delete_options();
        }
    }

    /**
     * Render the Newsletter Groups dashboard widget
     */
    public function handle_dashboard_widget() {
        $group_data = $this->get_all_newsletter_groups();
        $last_updated = $group_data->last_updated;
        $context = [];
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
     * Get all Newsletter groups
     *
     * @return object Newsletter group data and last updated timestamp
     */
    public function get_all_newsletter_groups() {
        $groups = get_option( $this->mc_groups_option_key );
        if ( ! empty( $groups ) ) {
            return $groups;
        }

        // Not cached locally so we need to fetch it
        $mc = MailChimp::get_instance();
        $group_category = 'Newsletters';
        $group_data = $mc->get_groups( $group_category );
        $groups = (object) [
            'last_updated' => time(),
            'groups' => $group_data,
        ];
        update_option( $this->mc_groups_option_key, $groups );
        return $groups;
    }

    /**
     * Get a newsletter group id from a given newsletter name
     *
     * @param  string  $name Name of the newsletter
     * @return string|0     The id property, 0 if nothing found
     */
    public function get_newsletter_group_id( $name = '' ) {
        $group_data = $this->get_all_newsletter_groups();
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
    public function get_subscriber_count( $name = '' ) {
        $group_data = $this->get_all_newsletter_groups();
        foreach ( $group_data->groups as $group ) {
            if ( ! is_object( $group ) || ! isset( $group->subscriber_count ) ) {
                continue;
            }
            if ( $name == $group->name || $name == $group->id ) {
                return intval( $group->subscriber_count );
            }
        }

        return 0;
    }

    /**
     * Deletes all Newsletter Group options for values on the settings page
     */
    public function delete_options() {
        $ac = ActiveCampaign::get_instance();
        $keys = [ $ac->get_address_option_key(), $this->mc_groups_option_key ];

        // Run over all of the keys and delete the option
        foreach ( $keys as $key ) {
            delete_option( $key );
        }
        // Since the option is autoloaded we need to delete the `alloptions`
        // cache to prevent race conditions with Redis object caches
        // See https://core.trac.wordpress.org/ticket/31245#comment:26
        wp_cache_delete( 'alloptions', 'options' );
    }
}
