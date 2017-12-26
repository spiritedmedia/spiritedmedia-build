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
        $embed = Embed::get( $post );
        if ( ! method_exists( $embed, 'get_embed_type' ) ) {
            return $context;
        }
        $context['embed_html'] = $embed->get_embed_html();
        if ( 'youtube' == $embed->get_embed_type() ) {
            $context['source_name']  = $embed->get_embed_author_name();
            $context['source_link']  = $embed->get_embed_url();
        } else {
            $context['show_meta_info'] = false;
        }
        $context['featured_image'] = '';
        return $context;
    }
}
