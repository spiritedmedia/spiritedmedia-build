<?php
namespace Pedestal\Objects;

use function Pedestal\Pedestal;

use Pedestal\Objects\ActiveCampaign;

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
        ?>
        <div class="wrap">
            <h1>Newsletter List Settings</h1>
            <form action="<?php echo esc_attr( '?post_type=pedestal_newsletter&page=' . $this->admin_page_slug ) ?>" method="post">
                <?php if ( $lists ) : ?>
                <table class="form-table">
                <?php foreach ( $lists as $id => $list ) :
                    $key = 'field-' . sanitize_title( $list );
                    $field_name = $this->sanitize_option_name( $list );
                    ?>
                    <tr valign="top">
                        <th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo $list; ?></label></th>
                        <td>
                            <input type="number" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo absint( $id ); ?>" id="<?php echo esc_attr( $key ); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
            <?php wp_nonce_field( $action = $this->admin_page_slug ); ?>
            <p class="submit">
                <?php submit_button( 'Sync & Save', 'primary', 'sync-and-save', $wrap = false ); ?>
                <?php submit_button( 'Save', 'secondary', 'save', $wrap = false ); ?>
            </p>
            </form>
        </div>
        <?php
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
        if ( ! check_admin_referer( $action = $this->admin_page_slug ) ) {
            wp_die( 'Bad nonce!' );
        }

        // We're saving the data manually
        if ( isset( $_POST['save'] ) ) {
            foreach ( $this->list_names as $name ) {
                // Employ a whitelist of option names we will store
                // Prevents someone from arbitrarily adding option data
                $key = $this->sanitize_option_name( $name );
                if ( empty( $_POST[ $key ] ) ) {
                    continue;
                }
                $this->save_option( $name, $_POST[ $key ] );
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
        $key = $this->sanitize_option_name( $name );
        if ( $id = get_option( $key ) ) {
            return $id;
        }

        if ( $id = $this->fetch_list_id_from_api( $name ) ) {
            // If the ID was fetched then we should save it
            $this->save_option( $name, $id );
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
     * Handle saving an option to the database
     * @param  string  $newsletter_name         Unsanitized name of the Newsletter
     * @param  integer $activecampaign_list_id  List ID to store
     * @return boolean                          Success of failure of save
     */
    public function save_option( $newsletter_name = '', $activecampaign_list_id = 0 ) {
        $key = $this->sanitize_option_name( $newsletter_name );
        $list_id = absint( $activecampaign_list_id );
        if ( ! $list_id ) {
            return false;
        }

        return update_option( $key, $list_id );
    }

    /**
     * Deletes all Newsletter options
     */
    public function delete_options() {
        foreach ( $this->list_names as $name ) {
            $key = $this->sanitize_option_name( $name );
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
    public function sanitize_option_name( $name = '' ) {
        $sanitized_named = sanitize_title( $name );
        if ( ! $sanitized_named ) {
            return false;
        }
        return 'newsletter-id-' . $sanitized_named;
    }
}
