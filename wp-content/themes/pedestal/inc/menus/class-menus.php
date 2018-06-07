<?php
namespace Pedestal\Menus;

use Timber\Timber;

class Menus {
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
     * Hook in to WordPress via actions
     */
    public function setup_actions() {
        add_action( 'pedestal_loaded', [ $this, 'action_pedestal_loaded' ] );
    }

    /**
     * Hook in to WordPress via filters
     */
    public function setup_filters() {
        add_filter( 'template_include', [ $this, 'filter_template_include_navigation_page' ] );
        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
        add_filter( 'pedestal_get_menu_data', [ $this, 'filter_pedestal_get_menu_data_add_search' ], 10, 2 );
    }

    /**
     * Register theme locations for menus if header navigation is enabled
     */
    public function action_pedestal_loaded() {
        if ( ! PEDESTAL_ENABLE_HEADER_NAVIGATION ) {
            return;
        }
        register_nav_menu( 'header-navigation', 'Header Navigation' );
        register_nav_menu( 'header-secondary-navigation', 'Header Secondary Navigation' );
    }

    /**
     * Create a virtual page for accessing /navigation/
     *
     * @param  string $template Template WordPress will use to render the request
     * @return string           Template location
     */
    public function filter_template_include_navigation_page( $template = '' ) {
        global $wp, $wp_query;
        $request = rtrim( $wp->request, '/' );
        if ( 'navigation' != $request ) {
            return $template;
        }

        // Redirect /navigation to /navigation/
        $requested_path = explode( '?', $_SERVER['REQUEST_URI'] )[0];
        if ( '/' !== substr( $requested_path , -1 ) ) {
            $redirect = trailingslashit( home_url( $wp->request ) );
            $redirect = add_query_arg( $_GET, $redirect );
            wp_safe_redirect( $redirect );
            die();
        }

        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        // Add a fake queried_object to prevent PHP notices
        $wp_query->queried_object = (object) [
            'ID'         => '',
            'post_title' => '',
            'post_name'  => '',
        ];
        status_header( 200 );

        return locate_template( [ 'navigation-fallback.php' ] );
    }

    /**
     * Add the site header HTML to the Timber context
     *
     * @param  array $context Timber context values
     * @return array          Timber context values with header menu HTML added
     */
    public function filter_timber_context( $context ) {
        if ( ! PEDESTAL_ENABLE_HEADER_NAVIGATION ) {
            $context['header_navigation_disabled'] = true;
            ob_start();
            Timber::render( 'partials/header/site-header.twig', $context );
            $context['site_header'] = ob_get_clean();
            return $context;
        }

        $context['header_navigation_disabled'] = false;
        $context['site_header'] = $this->get_header_menu_html();
        return $context;
    }

    /**
     * Add search menu item to the end of the header-navigation menu
     *
     * @param array  $items         Menu items
     * @param string $menu_location The location the menu is being rendered to
     */
    public function filter_pedestal_get_menu_data_add_search( $items = [], $menu_location = '' ) {
        if ( 'header-navigation' == $menu_location ) {
            $items[] = (object) [
                'title' => 'Search',
                'slug'  => 'search',
                'url'   => get_site_url() . '/search/',
                'icon'  => 'search',
            ];
        }
        return $items;
    }

    /**
     * Render the header menu
     *
     * @return string The rendered header
     */
    public function get_header_menu_html() {
        $context = [
            'twitter_url'   => PEDESTAL_TWITTER_URL,
            'facebook_url'  => PEDESTAL_FACEBOOK_PAGE,
            'instagram_url' => PEDESTAL_INSTAGRAM_URL,

            'site_url'      => get_site_url(),
            'domain_name'   => PEDESTAL_DOMAIN_PRETTY,
            'search_query'  => get_search_query(),
            'tagline'       => PEDESTAL_BLOG_TAGLINE,

            'primary_nav'   => $this->get_menu_data( 'header-navigation' ),
            'secondary_nav' => $this->get_menu_data( 'header-secondary-navigation' ),
        ];
        ob_start();
        Timber::render( 'partials/header/site-header.twig', $context );
        return ob_get_clean();
    }

    /**
     * Get menu data for the menu assigned to the given menu_locaton
     *
     * @param  string $menu_location The theme location to get the menu for
     * @return array                 Menu data
     */
    public function get_menu_data( $menu_location = '' ) {
        $output = [];
        $locations = get_nav_menu_locations();
        if ( empty( $locations[ $menu_location ] ) ) {
            return $output;
        }
        $menu = get_term( $locations[ $menu_location ], 'nav_menu' );
        $items = wp_get_nav_menu_items( $menu );
        foreach ( $items as $item ) {
            $icon = '';
            if ( isset( $item->icon ) ) {
                $icon = $item->icon;
            }
            $slug = sanitize_title( $item->title );
            $output[ $slug ] = (object) [
                'title' => $item->title,
                'slug'  => $slug,
                'url'   => $item->url,
                'icon'  => $icon,
            ];
        }
        return apply_filters( 'pedestal_get_menu_data', $output, $menu_location );
    }
}
