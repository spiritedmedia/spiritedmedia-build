<?php

namespace Pedestal\Registrations\Post_Types;
use Pedestal\Posts\Post;
use Pedestal\Posts\Entities\Entity;

class Pedestal_Entity {
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
        if ( empty( $context['post'] ) ) {
            return $context;
        }

        $entity = new Entity( $context['post'] );
        if ( ! $entity ) {
            return $context;
        }

        $story = $entity->get_primary_story();
        if ( $story ) {
            $context['overline'] = $story->get_the_title();
            $context['overline_url'] = $story->get_the_permalink();
        }
        $context['featured_image'] = $entity->get_featured_image_html( '1024-16x9' );

        return $context;
    }
}
