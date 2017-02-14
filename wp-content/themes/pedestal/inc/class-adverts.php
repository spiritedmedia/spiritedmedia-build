<?php

namespace Pedestal;

use Timber\Timber;

class Adverts {

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Adverts;
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Set up advert actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'timber_context', [ $this, 'filter_timber_context' ] );
    }

    /**
     * Set up advert filters
     */
    private function setup_filters() {
        add_filter( 'query_vars', function( $query_vars ) {
            $query_vars[] = 'pedestal-test-adverts';
            return $query_vars;
        });
    }

    /**
     * Register rewrite rules
     */
    public function action_init_register_rewrites() {
        // Currently test advert rewrites are only prepared for home and story
        // streams. Other clusters are excluded.
        add_rewrite_rule( 'pedestal-test-adverts/([^/]+)/?$', 'index.php?pedestal-test-adverts=$matches[1]', 'top' );
        add_rewrite_rule( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/page/?([0-9]{1,})/pedestal-test-adverts/([^/]+)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&paged=$matches[5]&post_type=pedestal_story&pedestal-test-adverts=$matches[6]', 'top' );
        add_rewrite_rule( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)(/[0-9]+)?/pedestal-test-adverts/([^/]+)/?$', 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5]&post_type=pedestal_story&pedestal-test-adverts=$matches[6]', 'top' );
        add_rewrite_endpoint( 'pedestal-test-adverts', EP_PERMALINK );
    }

    /**
     * Handle requests
     */
    public function filter_timber_context( $context ) {
        $context['adverts'] = [];
        if ( get_query_var( 'pedestal-test-adverts' ) && current_user_can( 'edit_slots' ) ) {
            switch ( get_query_var( 'pedestal-test-adverts' ) ) {
                case 'premium':
                    $context['adverts']['test_premium'] = true;
                    break;

                case 'all':
                default:
                    $context['adverts']['test_all'] = true;
                    break;
            }
        }
        return $context;
    }
}
