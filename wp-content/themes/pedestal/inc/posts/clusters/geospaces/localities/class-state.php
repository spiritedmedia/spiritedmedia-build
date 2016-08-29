<?php

namespace Pedestal\Posts\Clusters\Geospaces\Localities;

use \Pedestal\Utils\Utils;

/**
 * State
 */
class State extends Locality {

    protected $email_type = 'state updates';

    /**
     * Get the State's abbreviation
     *
     * @param array $field Field key to get
     */
    public function get_abbr() {
        return $this->get_meta( 'state_details_abbr' );
    }
}
