<?php

namespace Pedestal\Objects;

class Source {

    protected $term;

    public function __construct( $term ) {
        $this->term = $term;
    }

    /**
     * Get the name for the source
     */
    public function get_name() {
        return $this->term->name;
    }

    /**
     * Get the permalink for the source
     */
    public function get_permalink() {
        return get_term_link( $this->term );
    }
}
