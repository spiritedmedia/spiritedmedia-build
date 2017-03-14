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
            $query_vars[] = 'pedestal-ad-tester';
            return $query_vars;
        });
        add_filter( 'template_include', function( $template_path ) {
            if ( 1 == get_query_var( 'pedestal-ad-tester' ) ) {
                if ( $new_template_path = locate_template( [ 'ad-tester.php' ] ) ) {
                    $template_path = $new_template_path;
                }
            }
            return $template_path;
        });
    }

    /**
     * Register rewrite rules
     */
    public function action_init_register_rewrites() {
        add_rewrite_rule( '^ad-tester/?$', 'index.php?pedestal-ad-tester=1', 'top' );
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
