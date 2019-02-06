<?php

namespace Pedestal\Posts\Slots;

use \Pedestal\Utils\Utils;

use Pedestal\Posts\Post;

class Placement extends Post {

    protected static $post_type = '_slot_item_placement';

    /**
     * Get the end date for the Placement
     *
     * @return string Date in YYYY-MM-DD format
     */
    public function get_date_end() {
        return $this->get_meta( 'date_end' );
    }

    /**
     * Get the start date for the Placement
     *
     * @return string Date in YYYY-MM-DD format
     */
    public function get_date_start() {
        return $this->get_meta( 'date_start' );
    }

    /**
     * Get the days the slot item should display
     *
     * @return array Returns array of numerical days of the week if succssful,
     *     or an empty array if failed
     */
    public function get_date_subrange_days() {
        $item_days_nums = [];
        $item_days      = $this->get_meta( 'date_subrange_days', false );

        if ( empty( $item_days ) ) {
            return [];
        }

        if ( ! is_array( $item_days ) ) {
            $item_days = [ $item_days ];
        }

        foreach ( $item_days as $item_day ) {
            $item_days_nums[] = array_keys( Utils::get_days_of_week(), $item_day );
        }

        return Utils::array_flatten( $item_days_nums );
    }

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

    /**
     * Get the ID of the Placement's parent post
     *
     * @return int|false
     */
    public function get_parent_id() {
        return wp_get_post_parent_id( $this->get_id() );
    }
}
