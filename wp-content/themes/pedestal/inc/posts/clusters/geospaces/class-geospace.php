<?php

namespace Pedestal\Posts\Clusters\Geospaces;

use \Pedestal\Registrations\Post_Types\Cluster_Types;

use Pedestal\Posts\Clusters\Cluster;

abstract class Geospace extends Cluster {

    /**
     * Get the Geospace's full name
     *
     * @return string
     */
    public function get_full_name() {
        return $this->get_place_details_field( 'full_name' );
    }

    /**
     * Get the Geospace's URL
     *
     * @link http://schema.org/url
     *
     * @return string
     */
    public function get_url() {
        return $this->get_place_details_field( 'url' );
    }

    /**
     * Get the URL to a map of the Geospace
     *
     * @link http://schema.org/hasMap
     *
     * @return string
     */
    public function get_map_url() {
        return $this->get_place_details_field( 'map_url' );
    }

    /**
     * Get a Geospace details field
     *
     * @param array $field Field key to get
     */
    public function get_geospace_details_field( $field ) {
        return $this->get_meta( 'geospace_details_' . $field );
    }


    /**
     * Get a connection relationship's label
     *
     * @return string
     */
    public function get_connection_rel_label() {
        $rel = $this->get_connection_rel();
        switch ( $rel ) {
            case 'contained_in':
                $label = 'contained in';
                break;
            default:
                $label = $rel;
                break;
        }
        return $label;
    }

    /**
     * Get the relationship between two Geospaces from the connection ID
     *
     * @return string The name of the Geospace relationship
     */
    public function get_connection_rel() {
        $p2p_data = $this->get_p2p_data();
        if ( ! empty( $p2p_data ) ) {
            return p2p_get_meta( $p2p_data['connection_id'], 'rel', true );
        }
        return false;
    }

    /**
     * Get Geospaces this Geospace connects to actively
     *
     * These connected Geospaces are managed actively by this Geospace.
     *
     * @return array Array of Geospaces objects
     */
    public function get_connected_geospaces_active() {
        return $this->get_connected_geospaces( 'from' );
    }

    /**
     * Get Geospaces this Geospace is connected to passively
     *
     * These connected Geospaces are managed from another Geospace in the other
     * direction.
     *
     * @return array Array of Geospaces objects
     */
    public function get_connected_geospaces_passive() {
        return $this->get_connected_geospaces( 'to' );
    }

    /**
     * Get a stream array of the connected Geospaces based on direction
     *
     * @param  string $dir Direction
     * @return array       Array of post objects
     */
    private function get_connected_geospaces( $dir ) {
        return $this->get_connected_geospaces_stream( $dir )->get_stream();
    }

    /**
     * Get the Geospaces connected in the specified direction as a Stream object
     *
     * @param  string $dir Connection direction, either 'to' or 'from'
     * @return Stream
     */
    private function get_connected_geospaces_stream( $dir ) {
        $post_type = get_post_type( $this->get_id() );
        $sanitized_labels = Cluster_Types::get_sanitized_post_type_labels( $post_type );
        $connection_type = $sanitized_labels['name'] . '_to_' . $sanitized_labels['name'];
        return new \Pedestal\Objects\Stream( [
            'post_type'           => $post_type,
            'posts_per_page'      => -1,
            'connected_type'      => $connection_type,
            'connected_items'     => $this->get_id(),
            'connected_direction' => $dir,
            'orderby'             => 'title',
            'order'               => 'asc',
        ] );
    }
}
