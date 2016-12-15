<?php

namespace Pedestal\Posts\Clusters;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Subscriptions;

use Pedestal\Posts\Post;

use Pedestal\Posts\Attachment;

use Pedestal\Objects\Stream;

abstract class Cluster extends Post {

    protected $email_type = 'cluster updates';

    /**
     * Get CSS classes
     *
     * @return array
     */
    public function get_css_classes() {
        $classes = parent::get_css_classes();
        if ( $this->get_content() ) {
            $classes[] = 'has-content';
        }
        return $classes;
    }

    /**
     * Set up the Cluster's HTML data attributes
     */
    protected function set_data_atts() {
        parent::set_data_atts();
        $atts = parent::get_data_atts();
        $new_atts = [
            'cluster' => '',
        ];
        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Get the entities associated with an Cluster
     *
     * @param array
     * @param string $connected_type The name of the connection type to get entities for
     * @return array
     */
    public function get_entities( $args = [] ) {
        $stream = $this->get_stream( $args );
        return $stream->get_stream();
    }

    /**
     * Get the cluster stream's pagination
     *
     * @return array Pagination HTML
     */
    public function get_pagination() {
        $stream = $this->get_stream();
        return $stream::get_pagination( $stream->get_query() );
    }

    /**
     * Check if the stream is on its first page
     *
     * @return boolean
     */
    public function is_stream_first_page() {
        return $this->get_stream()->is_first_page();
    }

    /**
     * Check if the stream is on its last page
     *
     * @return boolean
     */
    public function is_stream_last_page() {
        return $this->get_stream()->is_last_page();
    }

    /**
     * Get the cluster stream object
     *
     * @param  array  $args WP_Query args
     * @return Stream
     */
    public function get_stream( $args = [] ) {
        $defaults = [
            'post_type'      => Types::get_entity_post_types(),
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'connected_type' => $this->get_cluster_entity_connection_type(),
        ];
        $args = wp_parse_args( $args, $defaults );

        $paged = get_query_var( 'paged' );
        $args['paged'] = $paged ? $paged : 1;
        $args['connected_items'] = $this->post;

        return new Stream( $args );
    }

    /**
     * Get the cluster's description
     *
     * @return string
     */
    public function get_description() {
        return $this->get_meta( 'description' );
    }

    /**
     * Get the number of users following this cluster
     * @param boolean $force  Whether to make a request to ActiveCampaign to get the List ID
     * @return int
     */
    public function get_following_users_count( $force = false ) {
        $list_id = $this->get_meta( 'activecampaign-list-id', true );
        if ( ! $list_id && $force ) {
            $list_id = Subscriptions::get_list_ids_from_cluster( $this->get_id() );
        }

        if ( $list_id ) {
            return Subscriptions::get_subscriber_count( $list_id );
        }
        return '-';
    }

    /**
     * Get the users following this cluster
     *
     * @return array
     */
    public function get_following_users( $args = [] ) {
        $defaults = [
            'connected_items' => $this->post,
            'connected_type'  => $this->get_cluster_user_connection_type(),
        ];
        $args = array_merge( $defaults, $args );

        return get_users( $args );
    }

    /**
     * Get the name of the user connection type by post type
     *
     * @uses $this->get_cluster_connection_type()
     *
     * @param  obj    $post The post object or post type string to check. Defaults to the current post object.
     * @return string       The name of the user connection type
     */
    public function get_cluster_user_connection_type( $post = null ) {
        return $this->get_cluster_connection_type( 'user', $post );
    }

    /**
     * Get the name of the entity connection type by post type
     *
     * @uses $this->get_cluster_connection_type()
     *
     * @param  obj    $post The post object or post type string to check. Defaults to the current post object.
     * @return string       The name of the entity connection type
     */
    public function get_cluster_entity_connection_type( $post = null ) {
        return $this->get_cluster_connection_type( 'entity', $post );
    }

    /**
     * Get the name of the connection type by post type.
     *
     * @uses Pedestal\Registrations\Post_Types\Types::get_connection_type()
     *
     * @param  string $rel  The relationship to return. Can be one of either 'entity' or 'user'.
     * @param  mixed  $post The post object or post type string to check. Defaults to the current post object.
     * @return string       The name of the user connection type
     */
    public function get_cluster_connection_type( $rel, $post = null ) {
        if ( empty( $post ) ) {
            $post = self::get_by_post_id( $this->get_id() );
        }
        return Types::get_connection_type( $rel, $post );
    }

    /**
     * Get the time of the last email notification
     *
     * @return mixed
     */
    public function get_last_email_notification_date( $format = 'U' ) {
        if ( $last_date = $this->get_meta( 'last_email_notification_date' ) ) {
            return date( $format, strtotime( date( 'Y-m-d H:i:s', $last_date ) ) );
        } else {
            return false;
        }
    }

    /**
     * Set the time of the last email notification
     */
    public function set_last_email_notification_date( $time = false ) {
        if ( ! $time ) {
            $time = time();
        }
        $this->set_meta( 'last_email_notification_date', $time );
    }

    /**
     * Get the entities since last email notification
     *
     * @return array
     */
    public function get_entities_since_last_email_notification() {
        $args = [ 'posts_per_page' => 30 ];
        if ( $last_date = $this->get_last_email_notification_date( 'Y-m-d H:i:s' ) ) {
            $args['date_query'] = [
                'after'         => $last_date,
                'column'        => 'post_date_gmt',
            ];
        }
        return $this->get_entities( $args );
    }

    /**
     * Get the ActiveCampaign list name for this Cluster
     *
     * @return string
     */
    public function get_activecampaign_list_name() {
        return $this->get_title() . ' - ' . PEDESTAL_BLOG_NAME . ' - ' . $this->get_type_name();
    }

    /**
     * Get the email type string
     *
     * @return string The contents of `$email_type`
     */
    public function get_email_type() {
        return $this->email_type;
    }
}
