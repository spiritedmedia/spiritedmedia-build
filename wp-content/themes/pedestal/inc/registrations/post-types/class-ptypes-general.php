<?php

namespace Pedestal\Registrations\Post_Types;

use Pedestal\Utils\Utils;

class General_Types extends Types {

    protected $editorial_post_types = [];

    protected $post_types = [
        'pedestal_newsletter',
    ];

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new General_Types;
            self::$instance->setup_types();
            self::$instance->setup_actions();
        }
        return self::$instance;

    }

    /**
     * Register the custom post types
     */
    public function setup_types() {

        foreach ( $this->post_types as $post_type ) :

            $args = [
                'hierarchical'      => false,
                'public'            => true,
                'show_in_nav_menus' => true,
                'show_ui'           => true,
                'has_archive'       => true,
                'query_var'         => true,
            ];

            switch ( $post_type ) {

                case 'pedestal_newsletter':
                    $singular = esc_html__( 'Newsletter', 'pedestal' );
                    $plural = esc_html__( 'Newsletters', 'pedestal' );
                    $class = 'Posts\\Newsletter';
                    $args['menu_position'] = 12;
                    $args['menu_icon'] = 'dashicons-email-alt';
                    $args['supports'] = [
                        'title',
                        'editor',
                        'author',
                        'slots',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'newsletters',
                    ];
                    break;

            }

            $post_types[ $post_type ] = compact( 'singular', 'plural', 'class', 'args' );

        endforeach;

        $this->post_types = $post_types;

    }

    /**
     * Setup actions
     */
    private function setup_actions() {

    }
}
