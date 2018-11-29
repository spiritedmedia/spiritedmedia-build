<?php

namespace Pedestal;

// use Pedestal\Utils\Utils;
// use Pedestal\Registrations\Post_Types\Types;

class Component_Previews {

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
     * Set up actions
     */
    protected function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
    }

    /**
     * Set up filters
     */
    protected function setup_filters() {
        add_filter( 'query_vars', function( $query_vars ) {
            $query_vars[] = 'pedestal-api';
            $query_vars[] = 'component-name';
            $query_vars[] = 'component-id';
            return $query_vars;
        });
        add_filter( 'template_include', [ $this, 'filter_template_include' ] );
    }

    /**
     * Set up rewrites for the component preview
     */
    public function action_init_register_rewrites() {
        add_rewrite_rule(
            'api/component-preview/([^/]+)/([^/]+)/?$',
            'index.php?pedestal-api=component-preview&component-name=$matches[1]&component-id=$matches[2]',
            'top'
        );
    }

    /**
     * Load the component preview template
     *
     * @param string $template
     * @return string Template path (maybe modified)
     */
    public function filter_template_include( $template ) {
        if ( 'component-preview' == get_query_var( 'pedestal-api' ) ) {
            $template_path = sprintf(
                'component-previews/%s.php',
                get_query_var( 'component-name' )
            );
            $new_template = locate_template( $template_path );
            if ( ! empty( $new_template ) ) {
                return $new_template;
            };
        }
        return $template;
    }
}
