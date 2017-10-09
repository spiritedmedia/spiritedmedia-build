<?php

namespace Pedestal\Posts\Clusters;

use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Email\{
    Email,
    Email_Lists
};
use Pedestal\Utils\Utils;

abstract class Cluster extends Post {

    /**
     * Email type for public display
     *
     * @var string
     */
    protected $email_type = 'cluster updates';

    /**
     * Cached stream
     *
     * @var Stream
     */
    private $cached_stream = [];

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
     * @param array $args
     * @return array
     */
    public function get_entities( $args = [] ) {
        $args = wp_parse_args( $args, [
            'post_type'      => Types::get_entity_post_types(),
            'connected_type' => $this->get_cluster_entity_connection_type(),
        ] );
        return $this->get_stream( $args );
    }

    /**
     * Get a WP_Query object for the entities connected to this cluster
     *
     * @param  array  $args  Args for modifying the query
     * @return WP_Query      WP_Query object
     */
    public function get_entities_query( $args = [] ) {
        $args = wp_parse_args( $args, [
            'post_type'      => Types::get_entity_post_types(),
            'connected_type' => $this->get_cluster_entity_connection_type(),
        ] );
        return $this->get_stream_query( $args );
    }

    /**
     * Get all connected posts
     *
     * @param  array $args
     * @return array Stream
     */
    public function get_connected( $args = [] ) {
        $args = wp_parse_args( $args, [
            'connected_type' => Types::get_cluster_connection_types(),
            'posts_per_page' => 500,
        ] );
        return $this->get_stream( $args );
    }

    /**
     * Get the cluster stream object
     *
     * @param  array  $args WP_Query args
     * @return Stream
     */
    public function get_stream( $args = [] ) {
        $query = $this->get_stream_query( $args );
        $args_hash = md5( serialize( $query->query_vars ) );
        if ( ! empty( $this->cached_stream[ $args_hash ] ) ) {
            return $this->cached_stream[ $args_hash ];
        }
        $ped_posts = Post::get_posts_from_query( $query );
        $this->cached_stream[ $args_hash ] = $ped_posts;
        return $this->cached_stream[ $args_hash ];
    }

    /**
     * Helper for getting a WP_Query object
     *
     * @param  array  $args  Args to modify the query
     * @return WP_Query      WP_Query object
     */
    public function get_stream_query( $args = [] ) {
        $defaults = [
            'post_type'      => Types::get_post_types(),
            'post_status'    => 'publish',
            'posts_per_page' => 20,
        ];
        $args = wp_parse_args( $args, $defaults );

        $paged = get_query_var( 'paged' );
        $args['paged'] = $paged ? $paged : 1;
        $args['connected_items'] = $this->post;
        $args['connected_type'] = Types::get_cluster_connection_types();
        return new \WP_Query( $args );
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
     *
     * @param bool $force  Whether to make an API request to ActiveCampaign to
     * get the List ID and update the cached follower count in post meta
     * @return int
     */
    public function get_following_users_count( $force = false ) {
        $list_id = $this->get_meta( 'activecampaign-list-id', true );
        if ( 0 !== $list_id || $force ) {
            $list_id = Email_Lists::get_list_ids_from_cluster( $this->get_id() );
        }
        $count = Email::get_subscriber_count( $list_id, $force );
        return $count ?: '-';
    }

    /**
     * Get the name of the entity connection type by post type
     *
     * @uses $this->get_cluster_connection_type()
     *
     * @param  object    $post The post object or post type string to check. Defaults to the current post object.
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
            $post = self::get( $this->get_id() );
        }
        return Types::get_connection_type( $rel, $post );
    }

    /**
     * Get the time of the last email notification
     *
     * @param string $format Time format
     * @return mixed
     */
    public function get_last_email_notification_date( $format = 'U' ) {
        $last_date = $this->get_meta( 'last_email_notification_date' );
        if ( $last_date ) {
            return date( $format, strtotime( date( 'Y-m-d H:i:s', $last_date ) ) );
        }
        return false;
    }

    /**
     * Set the time of the last email notification
     *
     * @param bool $time Use a specific time. Defaults to current time.
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
     * @param array $args
     * @return array
     */
    public function get_unsent_entities( $args = [] ) {
        $key = 'pedestal_cluster_unsent_entities_count_' . $this->get_id();
        $expiration = Utils::get_fuzzy_expire_time( HOUR_IN_SECONDS / 2 );
        $count = get_transient( $key );

        $args = wp_parse_args( $args, [
            'posts_per_page' => 99,
            'count_only'     => false,
            'force'          => false,
        ] );

        // The transient returns false if not found but it is also possible the transient has a value of ''
        if ( false !== $count && $args['count_only'] && ! $args['force'] ) {
            if ( empty( $count ) ) {
                $count = 0;
            }
            return $count;
        }

        $last_date = $this->get_last_email_notification_date( 'Y-m-d H:i:s' );
        if ( $last_date ) {
            $args['date_query'] = [
                'after'         => $last_date,
                'column'        => 'post_date_gmt',
            ];
        }

        $entities = $this->get_entities( $args );
        $count = count( $entities );

        // Store this as post meta to make sortable admin columns possible
        $this->set_meta( 'unsent_entities_count', $count );
        set_transient( $key, $count, $expiration );

        if ( $args['count_only'] ) {
            return $count;
        }
        return $entities;
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

    /**
     * Get the Twig context for this post
     *
     * @return array Twig context
     */
    public function get_context() {
        $context = parent::get_context();
        $context['slug'] = $this->get_slug();
        $context['overline_follow'] = true;
        return $context;
    }
}
