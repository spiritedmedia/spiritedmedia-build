<?php

namespace Pedestal\Registrations\Post_Types;
use Pedestal\Posts\Entities\Embed;
use Pedestal\Icons;

class Pedestal_Embed {
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
        if ( empty( $context['type'] ) || 'embed' != $context['type'] ) {
            return $context;
        }
        $post = $context['post'];
        $embed = new Embed( $post );
        $embed_type = $embed->get_embed_type();
        if ( 'twitter' === $embed_type ) {
            $context['description'] = $embed->get_embed_html();
        }
        if ( 'instagram' === $embed_type ) {
            $data = $embed->fetch_embed_data();
            if ( ! empty( $data['image_url_large'] ) ) {
                $context['thumbnail_image'] = '<img src="' . esc_url( $data['image_url_large'] ) . '">';
            }
        }
        $context['source_name']  = $embed->get_source();
        $context['source_image'] = Icons::get_icon( $embed_type );
        $context['source_link']  = $embed->get_embed_url();
        return $context;
    }
}
