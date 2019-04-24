<?php

namespace Pedestal\Posts\Entities;

use Pedestal\Objects\Source;
use Pedestal\Utils\Utils;

class Link extends Entity {

    protected static $post_type = 'pedestal_link';

    /**
     * Setup data attributes
     */
    public function set_data_atts() {
        parent::set_data_atts();
        $atts   = parent::get_data_atts();
        $source = $this->get_source();
        if ( ! $source ) {
            return;
        }
        $new_atts              = [
            'source-name' => $source->get_name(),
        ];
        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Get the external URL for the post
     *
     * @return string
     */
    public function get_external_url() {
        return $this->get_meta( 'external_url' );
    }

    /**
     * Get the name of the icon for this entity's source
     *
     * @return string
     */
    public function get_source_icon_name() {
        return 'external-link';
    }

    /**
     * Set the external URL for the post
     *
     * @return string
     */
    public function set_external_url( $value ) {
        return $this->set_meta( 'external_url', $value );
    }

    /**
     * Get the source for the link
     *
     * @return Source|false
     */
    public function get_source() {
        $sources = $this->get_taxonomy_terms( 'pedestal_source' );
        if ( $sources ) {
            $source = array_shift( $sources );
            return new Source( $source );
        }
        return false;
    }

    /**
     * Get the source name for the link
     *
     * @return string Source name if available, or domain name if Source is
     * unavailable, or empty string
     */
    public function get_source_name() {
        $source = $this->get_source();
        if ( method_exists( $source, 'get_name' ) ) {
            return $source->get_name();
        } else {
            return Utils::get_domain_from_url( $this->get_permalink() );
        }
    }

    /**
     * Get a link to the original source for the RSS feed
     *
     * @return string
     */
    public function get_the_content_rss() {
        return sprintf(
            'Read it at <a href="%s">%s</a>',
            esc_url( $this->get_the_permalink() ),
            esc_html( $this->get_source_name() )
        );
    }
}
