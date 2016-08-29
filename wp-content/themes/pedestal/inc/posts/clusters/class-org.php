<?php

namespace Pedestal\Posts\Clusters;

use \Pedestal\Utils\Utils;

/**
 * Organization
 *
 * @link http://schema.org/Organization
 */
class Org extends Cluster {

    protected static $post_type = 'pedestal_org';

    protected $email_type = 'organization updates';

    /**
     * Get the Org's URL
     *
     * @link http://schema.org/url
     *
     * @return string
     */
    public function get_url() {
        return $this->get_org_details_field( 'url' );
    }

    /**
     * Get the Org's full name
     *
     * @return string
     */
    public function get_full_name() {
        return $this->get_org_details_field( 'full_name' );
    }

    /**
     * Get the Org's number of employees
     *
     * @link http://schema.org/numberOfEmployees
     *
     * @return string
     */
    public function get_num_employees() {
        return $this->get_org_details_field( 'num_employees' );
    }

    /**
     * Get the Org's founding date
     *
     * @link http://schema.org/foundingDate
     *
     * @return string
     */
    public function get_founding_date() {
        return $this->get_org_details_field( 'founding_date' );
    }

    /**
     * Get an Org details field
     *
     * @param array $field Field key to get
     */
    public function get_org_details_field( $field ) {
        return $this->get_meta( 'org_details_' . $field );
    }
}
