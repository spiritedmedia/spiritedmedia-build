<?php

namespace Pedestal\Posts\Slots;

use \Pedestal\Utils\Utils;

use Pedestal\Posts\Post;

class Placement extends Post {

    protected static $post_type = '_slot_item_placement';

    /**
     * Get the ID of the post selected by the Placement
     *
     * @return string Post ID if selected or empty string
     */
    public function get_selected_post_id() {
        return $this->get_meta( $this->get_selected_post_field_name() );
    }

    /**
     * Get the expected name of the post select field
     *
     * @return string
     */
    public function get_selected_post_field_name() {
        return 'select_' . Utils::remove_name_prefix( $this->get_placement_type() );
    }

    /**
     * Get the type of the Placement
     *
     * @return mixed
     */
    public function get_placement_type() {
        return $this->get_meta( 'type' );
    }
}
