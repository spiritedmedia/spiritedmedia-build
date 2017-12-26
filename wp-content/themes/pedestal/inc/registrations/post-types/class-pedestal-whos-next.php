<?php

namespace Pedestal\Registrations\Post_Types;

use Pedestal\Posts\Entities\Originals\Whos_Next;

class Pedestal_Whos_Next {
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

    }
}
