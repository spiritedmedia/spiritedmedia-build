<?php

namespace Pedestal\Registrations\Post_Types;
use Pedestal\Posts\Entities\Originals\Factcheck;

class Pedestal_Factcheck {
    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into filters
     */
    public function setup_filters() {
        add_filter( 'pedestal_stream_item_context', [ $this, 'filter_pedestal_stream_item_context' ] );
    }

    /**
     * Setup properties unique to this stream item
     *
     * @param  array $context  List of properties for a stream item
     * @return array           Modified list of properties
     */
    public function filter_pedestal_stream_item_context( $context = [] ) {
        if ( empty( $context['type'] ) || 'factcheck' != $context['type'] ) {
            return $context;
        }
        $where_is_it_shown = 'standard';
        if ( ! empty( $context['__context'] ) ) {
            $where_is_it_shown = $context['__context'];
        }
        $factcheck = Factcheck::get( $context['post'] );
        if ( ! method_exists( $factcheck, 'get_statement_img' ) ) {
            return $context;
        }
        if ( 'featured' != $where_is_it_shown ) {
            // Don't show a featured image which is set in class-pedestal-entity.php
            $context['featured_image'] = '';
            $context['thumbnail_image'] = $factcheck->get_statement_img();
        }
        return $context;
    }
}
