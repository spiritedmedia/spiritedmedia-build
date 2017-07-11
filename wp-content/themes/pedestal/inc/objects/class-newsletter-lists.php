<?php
namespace Pedestal\Objects;

use function Pedestal\Pedestal;

use Pedestal\Objects\ActiveCampaign;
use Timber\Timber;

class Newsletter_Lists {

    /**
     * List of Newsletter names we want to track IDs for
     * @var array
     */
    private $list_names = [
        'Daily Newsletter',
        'Breaking News',
    ];

    /**
     * Name of the key to store the address ID
     * @var string
     */
    private $address_option_key = 'newsletter-address-id';

    /**
     * Slug of the settings page
     * @var string
     */
    private $admin_page_slug = 'pedestal-activecampaign-list-settings';

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            // Late static binding (PHP 5.3+)
            $instance = new static();
            $instance->load();
        }
        return $instance;
    }

    /**
     * Hook into various actions
     */
    public function load() {
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
    }

    /**
     * Handles rendering the admin screen
     */
    public function render_settings_page() {
        $this->save_settings_page();
        $lists = $this->get_all_newsletters();
        $address_id = $this->get_address_id();

        $context = [];
        $context['form'] = [
            'action' => '?post_type=pedestal_newsletter&page=' . esc_attr( $this->admin_page_slug ),
        ];
        $context['fields'] = [];
        foreach ( $lists as $id => $list ) {
            $key = 'field-' . sanitize_title( $list );
            $name = $this->sanitize_newsletter_option_name( $list );
            $context['fields'][] = [
                'id' => absint( $id ),
                'key' => esc_attr( $key ),
                'name' => esc_attr( $name ),
                'label' => $list,
            ];
        }
        $context['fields'][] = [
            'id' => absint( $address_id ),
            'key' => 'field-address-id',
            'name' => esc_attr( $this->address_option_key ),
            'label' => 'Address ID',
        ];
        $context['nonce_field'] = wp_nonce_field( $this->admin_page_slug, '_wpnonce', true, false );
        $context['primary_button'] = get_submit_button( 'Sync & Save', 'primary', 'sync-and-save', false );
        $context['secondary_button'] = get_submit_button( 'Save', 'secondary', 'save', false );

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

        // We're saving the data manually
        if ( isset( $_POST['save'] ) ) {
            foreach ( $this->list_names as $name ) {
                // Employ a whitelist of option names we will store
                // Prevents someone from arbitrarily adding option data
                $key = $this->sanitize_newsletter_option_name( $name );
                if ( empty( $_POST[ $key ] ) ) {
                    continue;
                }
                $this->save_newsletter_option( $name, $_POST[ $key ] );
            }

            if ( ! empty( $_POST[ $this->address_option_key ] ) ) {
                $this->save_address_option( $_POST[ $this->address_option_key ] );
            }
        }

        // We're syncing the IDs from ActiveCampaign
        if ( isset( $_POST['sync-and-save'] ) ) {
            // All we need to do is delete the options and they will be regenerated
            // and fetched automatically when the options aren't present
            $this->delete_options();
        }
    }

    /**
     * Get a newsletter list ID from a given newsletter name
     * @param  string  $name Name of the newsletter
     * @return int     The ID
     */
    public function get_newsletter_list_id( $name = '' ) {
        $key = $this->sanitize_newsletter_option_name( $name );
        $id = get_option( $key );
        if ( $id ) {
            return $id;
        }

        $id = $this->fetch_list_id_from_api( $name );
        if ( $id ) {
            // If the ID was fetched then we should save it
            $this->save_newsletter_option( $name, $id );
            return $id;
        }

        return 0;
    }

    /**
     * Make an API request to ActiveCampaign to get the list ID
     * @param  string $name  Name of the list
     * @return int|false     List ID or false on failure
     */
    public function fetch_list_id_from_api( $name = '' ) {
        $activecampaign = new ActiveCampaign;
        $list_name = $name . ' - ' . PEDESTAL_BLOG_NAME;
        $list = $activecampaign->get_list( $list_name );
        if ( ! $list || ! is_object( $list ) || ! isset( $list->id ) ) {
            return false;
        }
        return intval( $list->id );
    }

    /**
     * Get all Newsletter names and their IDs
     * @return array $id => $name result
     */
    public function get_all_newsletters() {
        $output = [];
        foreach ( $this->list_names as $name ) {
            $id = $this->get_newsletter_list_id( $name );
            $output[ $id ] = $name;
        }
        return $output;
    }

    /**
     * Get the address ID from an option in the database
     * or fall back to an API request
     * @return Integer  ID of the address or 0 if not found
     */
    public function get_address_id() {
        // Check if the ID was previously saved
        $id = get_option( $this->address_option_key );
        if ( $id ) {
            return $id;
        }

        // Check the API for the address ID
        $id = $this->fetch_address_id_from_api();
        if ( $id ) {
            // If the ID was fetched then we should save it
            $this->save_address_option( $id );
            return $id;
        }

        // Exhausted all options, 0 tells ActiveCampaign to use the default address
        return 0;
    }

    /**
     * Fetch the addresses from the ActiveCampaign API
     * Loop over each address and match based on zip code to find the ID
     * @return integer|false The ActiveCampaign address ID
     */
    public function fetch_address_id_from_api() {
        $activecampaign = new ActiveCampaign;
        $addresses = $activecampaign->get_addresses();
        foreach ( $addresses as $address ) {
            if (
                ! is_object( $address ) ||
                ! isset( $address->zip ) ||
                ! isset( $address->id )
            ) {
                continue;
            }
            // If the zip code matches then we're good!
            if ( PEDESTAL_ZIPCODE == $address->zip ) {
                return intval( $address->id );
            }
        }
        return false;
    }

    /**
     * Handle saving a newsletter option to the database
     * @param  string  $newsletter_name         Unsanitized name of the Newsletter
     * @param  integer $activecampaign_list_id  List ID to store
     * @return boolean                          Success of failure of save
     */
    public function save_newsletter_option( $newsletter_name = '', $activecampaign_list_id = 0 ) {
        $key = $this->sanitize_newsletter_option_name( $newsletter_name );
        $list_id = absint( $activecampaign_list_id );
        if ( ! $list_id ) {
            return false;
        }

        return update_option( $key, $list_id );
    }

    /**
     * Handle saving an address option to the database
     * @param  integer $activecampaign_list_id  List ID to store
     * @return boolean                          Success of failure of save
     */
    public function save_address_option( $activecampaign_address_id = 0 ) {
        $address_id = absint( $activecampaign_address_id );
        if ( ! $address_id ) {
            return false;
        }

        return update_option( $this->address_option_key, $address_id );
    }

    /**
     * Deletes all Newsletter options
     */
    public function delete_options() {
        // Start with the address option key...
        $keys = [ $this->address_option_key ];

        // Then add all of the options for each list names
        foreach ( $this->list_names as $name ) {
            $keys[] = $this->sanitize_newsletter_option_name( $name );
        }

        // Run over all of the keys and delete the option
        foreach ( $keys as $key ) {
            delete_option( $key );
            // Since the option is autoloaded we need to delete the `alloptions`
            // cache to prevent race conditions with Redis object caches
            // See https://core.trac.wordpress.org/ticket/31245#comment:26
            wp_cache_delete( 'alloptions', 'options' );
        }
    }

    /**
     * Sanitize an option name consistently
     * @param  string $name  Name of the List
     * @return string        Sanitized option name
     */
    public function sanitize_newsletter_option_name( $name = '' ) {
        $sanitized_named = sanitize_title( $name );
        if ( ! $sanitized_named ) {
            return false;
        }
        return 'newsletter-id-' . $sanitized_named;
    }
}
