<?php

namespace Pedestal\Posts\Clusters\Geospaces\Localities;

use Pedestal\Posts\Attachment;

/**
 * Neighborhood
 */
class Neighborhood extends Locality {

    protected $email_type = 'neighborhood updates';

    /**
     * Whether or not this neighborhood has a postcard
     *
     * @return boolean
     */
    public function has_postcard() {
        return (bool) $this->get_postcard();
    }

    /**
     * Get this neighborhood's postcard
     *
     * @return obj An attachment object
     */
    public function get_postcard() {
        return Attachment::get( $this->get_postcard_id() ) ?: false;
    }

    /**
     * Get this neighborhood's postcard ID
     *
     * @return int|false
     */
    public function get_postcard_id() {
        return (int) $this->get_field( 'postcard' );
    }

    /**
     * Get this neighborhood's postcard URL
     *
     * @param  string $size
     * @param  array  $args
     * @return string
     */
    public function get_postcard_url( $size = 'full', $args = [] ) {
        $attachment = $this->get_postcard();
        if ( $attachment ) {
            return $attachment->get_url( $size, $args );
        } else {
            return '';
        }
    }

    /**
     * Get the HTML for the postcard
     *
     * @param  string $size
     * @param  array  $args
     * @return string
     */
    public function get_postcard_html( $size = 'full', $args = [] ) {
        $attachment = $this->get_postcard();
        if ( $attachment && method_exists( $attachment, 'get_html' ) ) {
            return $attachment->get_html( $size, $args );
        } else {
            return '';
        }
    }
}
