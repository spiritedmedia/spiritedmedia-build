<?php

namespace Pedestal\Registrations\Post_Types;
use Pedestal\Posts\Entities\Event;

class Pedestal_Event {
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
        add_filter( 'pedestal_stream_item_template', [ $this, 'filter_pedestal_stream_item_template' ], 10, 2 );
        add_filter( 'pedestal_stream_item_context', [ $this, 'filter_pedestal_stream_item_context' ] );
    }

    /**
     * Modify the template used for event stream items
     *
     * @param  string $template  Path to twig template to use for stream item
     * @param  array  $context   List of options specific to this stream item
     * @return string            Path to twig template to use for stream item
     */
    public function filter_pedestal_stream_item_template( $template = '', $context = [] ) {
        if ( empty( $context['type'] ) || 'event' != $context['type'] ) {
            return $template;
        }
        return 'partials/stream/event-stream-item.twig';
    }

    /**
     * Setup properties unique to this stream item
     *
     * @param  array $context  List of properties for a stream item
     * @return array           Modified list of properties
     */
    public function filter_pedestal_stream_item_context( $context ) {
        if ( empty( $context['type'] ) || 'event' != $context['type'] ) {
            return $context;
        }
        $post = $context['post'];
        $ped_event = Event::get( $post );
        if ( method_exists( $ped_event, 'get_context' ) ) {
            $context = $ped_event->get_context( $context );
        }
        return $context;
    }
}
