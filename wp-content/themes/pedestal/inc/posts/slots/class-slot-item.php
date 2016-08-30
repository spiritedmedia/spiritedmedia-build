<?php

namespace Pedestal\Posts\Slots;

use \Pedestal\Utils\Utils;

use Pedestal\Posts\Post;

use Pedestal\Posts\Attachment;

use Pedestal\Posts\Slots\Slots;

class Slot_Item extends Post {

    protected static $post_type = 'pedestal_slot_item';

    /**
     * Get the child Placement post IDs
     *
     * @return array Array of Placement IDs
     */
    public function get_placement_post_ids() {
        $query = new \WP_Query( [
            'post_type'              => '_slot_item_placement',
            'post_status'            => 'publish',
            'post_parent'            => $this->get_id(),
            'posts_per_page'         => 500,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'fields'                 => 'ids',
        ] );
        return $query->posts;
    }

    /**
     * Get the default date for the Placement
     *
     * @return int Unix timestamp date
     */
    public function get_placement_default_date() {
        return (int) $this->get_placement_defaults_field( 'date' );
    }

    /**
     * Get the items for a given Placement Type
     *
     * @return string Empty string or post ID
     */
    public function get_placement_default_post_id() {
        return $this->get_placement_defaults_scope_field(
            $this->get_placement_default_selected_post_field_name()
        );
    }

    /**
     * Get the expected name of the post select field
     *
     * @return string
     */
    public function get_placement_default_selected_post_field_name() {
        return 'select_' . Utils::remove_name_prefix( $this->get_placement_default_type() );
    }

    /**
     * Get the default Slot Item Placement Type
     *
     * @return string Name of a Placement Type
     */
    public function get_placement_default_type() {
        return $this->get_placement_defaults_field( 'type' );
    }

    /**
     * Get the sponsorship image URL from upload
     *
     * @return string URL
     */
    public function get_sponsorship_img_url() {
        $upload = $this->get_sponsorship_field( 'upload' );
        if ( empty( $upload ) || ! is_numeric( $upload ) ) {
            return false;
        }
        $img = Attachment::get_by_post_id( (int) $upload );
        return $img->get_url();
    }

    /**
     * Get the label for the sponsorship slot item
     *
     * @return string
     */
    public function get_sponsorship_label() {
        return esc_html__( $this->get_sponsorship_field( 'label' ), 'pedestal' );
    }

    /**
     * Get the sponsorship URL
     *
     * @return string URL
     */
    public function get_sponsorship_url() {
        return $this->get_sponsorship_field( 'url' );
    }

    /**
     * Get the Slot Placement Rules repeating field
     *
     * @return array|bool Array on success, false on fail
     */
    public function get_placement_rules() {
        $repeater = $this->get_meta( 'slot_item_placement_rules' );
        if ( ! is_array( $repeater ) ) {
            return false;
        }
        return $repeater;
    }

    /**
     * Get a child field of the Slot Placement Defaults field group
     *
     * @param  string $field Field key
     * @return mixed
     */
    public function get_placement_defaults_field( $key ) {
        return $this->get_fm_field( 'slot_item_placement_defaults', $key );
    }

    /**
     * Get a child field of the Sponsorship slot item type
     *
     * @param  string $field Field key
     * @return mixed
     */
    public function get_sponsorship_field( $key ) {
        return $this->get_fm_field( 'slot_item_type', 'sponsorship', $key );
    }

    /**
     * Get the slot item type's slug
     *
     * @return string
     */
    public function get_slot_item_type_slug() {
        $term = $this->get_slot_item_type();
        if ( empty( $term ) || ! is_object( $term ) ) {
            return '';
        }
        return $term->slug;
    }

    /**
     * Get the type of slot item
     *
     * @return obj|bool WP_Term if successful or false if fail
     */
    public function get_slot_item_type() {
        $type_id = $this->get_fm_field( 'slot_item_type', 'type' );
        if ( empty( $type_id ) || ! is_numeric( $type_id ) ) {
            return false;
        }
        return get_term( $type_id, 'pedestal_slot_item_type' );
    }
}
