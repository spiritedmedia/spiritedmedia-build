<?php

namespace Pedestal\Posts\Clusters\Geospaces;

use Pedestal\Posts\Clusters\Cluster;

class Place extends Geospace {

    protected static $post_type = 'pedestal_place';

    protected $email_type = 'place updates';

    /**
     * Get the Place's primary street address
     *
     * @link http://schema.org/streetAddress
     *
     * @return string
     */
    public function get_street_address_primary() {
        return $this->get_place_address_field( 'street_01' );
    }

    /**
     * Get the Place's secondary street address
     *
     * E.G. Unit number, suite number
     *
     * @link http://schema.org/streetAddress
     *
     * @return string
     */
    public function get_street_address_secondary() {
        return $this->get_place_address_field( 'street_02' );
    }

    /**
     * Get the Place's post office box number
     *
     * @link http://schema.org/postalCode
     *
     * @return string
     */
    public function get_post_office_box_number() {
        return $this->get_place_address_field( 'po_box' );
    }

    /**
     * Get the Place's postal code
     *
     * @link http://schema.org/postalCode
     *
     * @return string
     */
    public function get_postal_code() {
        return $this->get_place_address_field( 'postal_code' );
    }

    /**
     * Get a Place address field
     *
     * @param array $field Field key to get
     */
    public function get_place_address_field( $field ) {
        return $this->get_meta( 'place_address_' . $field );
    }

    /**
     * Get a Place details field
     *
     * @param array $field Field key to get
     */
    public function get_place_details_field( $field ) {
        return $this->get_meta( 'place_details_' . $field );
    }
}
