<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;

use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Objects\ActiveCampaign;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Clusters\Cluster;

class Email_Lists {

    /**
     * List of Newsletter names we want to track IDs for
     *
     * @var array
     */
    private $list_names = [
        'Daily Newsletter',
        'Breaking News',
    ];

    /**
     * Name of the option for storing all lists for a site
     *
     * @var string
     */
    private static $all_lists_option_name = 'activecampaign_all_lists';

    /**
     * The meta key where we store associated list IDs for a given post
     *
     * @var string
     */
    private static $list_id_meta_key = 'activecampaign-list-id';

    /**
     * Name of the key to store the address ID
     *
     * @var string
     */
    private $address_option_key = 'newsletter-address-id';

    /**
     * Slug of the settings page
     *
     * @var string
     */
    private $admin_page_slug = 'pedestal-activecampaign-list-settings';

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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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

    /**
     * Get list IDs from a given cluster
     *
     * @param  int $cluster_id                   Post ID of the Cluster
     * @param  bool $create_list_if_doesnt_exist Whether to create a list if it doesn't exist
     * @return false|int List id or False if no list id found
     */
    public static function get_list_ids_from_cluster( $cluster_id = 0, $create_list_if_doesnt_exist = true ) {
        $cluster_id = intval( $cluster_id );
        if ( ! $cluster_id ) {
            return false;
        }

        $list_id = self::get_list_id_from_meta( $cluster_id );
        if ( $list_id ) {
            return intval( $list_id );
        }

        // Looks like we'll need to fetch the List ID from ActiveCampaign
        $activecampaign = new ActiveCampaign;
        $cluster = Post::get( $cluster_id );
        if ( ! Types::is_cluster( $cluster ) ) {
            return false;
        }

        $list_name = $cluster->get_activecampaign_list_name();

        // Check if list already exists
        $resp = $activecampaign->get_list( $list_name );
        // List not found, let's add a new list
        if ( ! $resp && $create_list_if_doesnt_exist ) {
            $args = [
                'name' => $list_name,
            ];
            $resp = $activecampaign->add_list( $args );
        }
        $list_id = intval( $resp->id );
        $cluster->add_meta( self::$list_id_meta_key, $list_id );
        return $list_id;
    }

    /**
     * Get a Cluster object from a given ActiveCampaign List ID
     * @param  array $list_ids  One or more ActiveCampaign List IDs
     * @return array            Array of Cluster objects
     */
    public static function get_clusters_from_list_ids( $list_ids = [] ) {
        $output = [];
        if ( is_numeric( $list_ids ) ) {
            $list_ids = [ $list_ids ];
        }
        $list_ids = array_map( 'intval', $list_ids );
        $args = [
            'post_type' => 'any',
            'meta_key' => self::$list_id_meta_key,
            'meta_value' => $list_ids,
            'fields' => 'ids',
            'no_found_rows' => true,
        ];
        $meta_query = new \WP_Query( $args );
        $post_ids = $meta_query->posts;
        if ( empty( $post_ids ) ) {
            return $output;
        }
        foreach ( $post_ids as $post_id ) {
            $cluster = Cluster::get( $post_id );
            if ( Types::is_cluster( $cluster ) ) {
                $output[] = $cluster;
            }
        }
        return $output;
    }

    /**
     * Get an ActiveCampaign List ID stored as post_meta
     *
     * @param  int $post_id  Post ID to check
     * @return string List ID
     */
    public static function get_list_id_from_meta( $post_id = 0 ) {
        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return false;
        }
        return get_post_meta( $post_id, self::$list_id_meta_key, true );
    }

    /**
     * Delete the ActiveCampaign List ID stored as post_meta
     *
     * @param  int $post_id  Post ID to check
     * @return bool           False for failure. True for success.
     */
    public static function delete_list_id_from_meta( $post_id = 0 ) {
        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return false;
        }
        return delete_post_meta( $post_id, self::$list_id_meta_key );
    }

    /**
     * Remove '- <blog name>' from list name which we do to make them unique within ActiveCampaign
     *
     * @param  string $haystack  String to scrub
     * @return string            Scrubbed list name
     */
    public static function scrub_list_name( $haystack = '' ) {
        $needle = '- ' . PEDESTAL_BLOG_NAME;
        $parts = explode( $needle, $haystack );
        return trim( $parts[0] );
    }

    /**
     * Delete the option that stores info about all of the lists from ActiveCampaign
     */
    public static function purge_all_lists() {
        delete_option( self::$all_lists_option_name );
    }

    /**
     * Fetch all lists from ActiveCampaign and cache them in an option
     *
     * @return Array   A set of objects about lists
     */
    public static function get_all_lists() {
        $lists = get_option( self::$all_lists_option_name );
        if ( $lists ) {
            return $lists;
        }

        $activecampaign = new ActiveCampaign;
        $args = [
            'filters[name]' => '- ' . PEDESTAL_BLOG_NAME,
        ];
        $lists = $activecampaign->get_lists( $args );
        if ( $lists ) {
            foreach ( $lists as $list ) {
                // Clean up the list names before storing
                $list->name = self::scrub_list_name( $list->name );
            }
        }
        $autoload = 'no';
        add_option( self::$all_lists_option_name, (array) $lists, '', $autoload );
        return $lists;
    }

    /**
     * Filter all lists to the ones that have subscribers
     *
     * @return Array   A set of objets with lists that have 1 or more subscribers
     */
    public static function get_all_lists_with_subscribers() {
        $lists = self::get_all_lists();
        $output = [];
        foreach ( $lists as $list ) {
            if ( isset( $list->subscriber_count ) && 0 < $list->subscriber_count ) {
                $output[] = $list;
            }
        }
        return $output;
    }
}
