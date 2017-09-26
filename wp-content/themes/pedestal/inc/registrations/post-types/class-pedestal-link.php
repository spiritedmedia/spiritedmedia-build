<?php

namespace Pedestal\Registrations\Post_Types;
use Pedestal\Posts\Entities\Link;
use Pedestal\Icons;

class Pedestal_Link {
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
        if ( empty( $context['type'] ) || 'link' != $context['type'] ) {
            return $context;
        }
        $post = $context['post'];
        $link = Link::get( $post );
        if ( method_exists( $link, 'get_external_url' ) ) {
            $context['description'] = '';
            $context['source_name']  = $link->get_source_name();
            $context['source_image'] = Icons::get_icon( 'external-link' );
            $context['source_link']  = $link->get_external_url();
        }
        return $context;
    }
}
