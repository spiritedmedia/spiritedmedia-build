<?php
namespace Pedestal\Menus;

use Pedestal\Icons;
use Timber\Timber;

class Menu_Icons {

    /**
     * Get an instance of this class
     */
    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into WordPress via actions
     */
    public function setup_actions() {
        add_action( 'admin_print_styles-nav-menus.php', [ $this, 'action_admin_print_styles_nav_menus_php' ] );
        add_action( 'wp_nav_menu_item_custom_fields', [ $this, 'action_wp_nav_menu_item_custom_fields' ], 10, 2 );
        add_action( 'wp_update_nav_menu_item', [ $this, 'action_wp_update_nav_menu_item' ], 10, 2 );
    }

    /**
     * Hook into WordPress via filters
     */
    public function setup_filters() {
        add_filter( 'manage_nav-menus_columns', [ $this, 'filter_manage_nav_menus_columns' ], 99 );
        add_filter( 'wp_setup_nav_menu_item', [ $this, 'filter_wp_setup_nav_menu_item' ] );
    }

    /**
     * Enqueue CSS and JavaScript files on nav-menus.php admin page
     */
    public function action_admin_print_styles_nav_menus_php() {
        wp_enqueue_script( 'pedestal-menu-icons', PEDESTAL_DIST_DIRECTORY_URI . '/js/pedestal-menu-icons.js', [ 'jquery-ui-autocomplete' ], PEDESTAL_VERSION, true );
        wp_localize_script( 'pedestal-menu-icons', 'PedestalIcons', Icons::get_all_icons_svg() );

        wp_enqueue_style( 'pedestal-menu-icons', PEDESTAL_DIST_DIRECTORY_URI . '/css/pedestal-menu-icons.css', [], PEDESTAL_VERSION );
    }

    /**
     * Add HTML for the icon field to each menu item
     *
     * @param int    $id    Nav menu ID
     * @param object $item  Menu item data object
     */
    public function action_wp_nav_menu_item_custom_fields( $id = 0, $item ) {
        $value = '';
        $icon = '';

        $icon_name = get_post_meta( $item->ID, 'menu-item-icon', true );
        if ( $icon_name ) {
            $value = $icon_name;
            $icon = Icons::get_icon( $icon_name );
        }

        $context = [
            'id'    => $id,
            'icon'  => $icon,
            'value' => $value,
        ];

        Timber::render( 'partials/admin/menu-icons/menu-icon-field.twig', $context );
    }

    /**
     * Save icon field value
     *
     * @param int   $menu_id         Nav menu ID
     * @param int   $menu_item_db_id Menu item ID
     */
    public function action_wp_update_nav_menu_item( $menu_id = 0, $menu_item_db_id = 0 ) {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

        $meta_key = 'menu-icon';
        $key = 'menu-item-icon';
        $value = null;
        if ( ! empty( $_POST[ $key ][ $menu_item_db_id ] ) ) {
            $value = $_POST[ $key ][ $menu_item_db_id ];
        }

        if ( ! is_null( $value ) ) {
            update_post_meta( $menu_item_db_id, $key, $value );
        } else {
            delete_post_meta( $menu_item_db_id, $key );
        }
    }

    /**
     * Add a toggle to disable the icon field
     *
     * @param  array $columns Columns to show
     * @return array          Modified columns
     */
    public function filter_manage_nav_menus_columns( $columns = [] ) {
        // WordPress will toggle an element with the class field-icon
        $columns['icon'] = 'Icons';
        return $columns;
    }

    /**
     * Add the menu item icon to the returned $post object
     *
     * @param  object $post WP Menu Item post object
     * @return object       Modified post object
     */
    public function filter_wp_setup_nav_menu_item( $post ) {
        $post->icon = '';
        $icon_name = get_post_meta( $post->ID, 'menu-item-icon', true );
        if ( $icon_name ) {
            $post->icon = $icon_name;
        }
        return $post;
    }
}
