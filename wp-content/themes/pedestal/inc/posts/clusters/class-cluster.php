<?php

namespace Pedestal\Posts\Clusters;

use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Email\{
    Email_Groups,
    Follow_Updates
};
use Pedestal\Utils\Utils;
use Pedestal\Objects\MailChimp;

abstract class Cluster extends Post {

    /**
     * Email type for public display
     *
     * @var string
     */
    protected $email_type = 'cluster updates';

    /**
     * Cached Pedestal Post objects
     *
     * @var array Pedestal Post objects
     */
    private $cached_posts = [];

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
        $atts                  = parent::get_data_atts();
        $new_atts              = [
            'cluster' => '',
        ];
        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Get all connected posts
     *
     * Note that due to the use of `Types::get_cluster_connection_types()` this
     * can be a performance-heavy query.
     *
     * @param  array $args
     * @return array Pedestal Post objects
     */
    public function get_connected( $args = [] ) {
        $args = wp_parse_args( $args, [
            'connected_type' => Types::get_cluster_connection_types(),
            'posts_per_page' => 500,
        ] );
        return $this->get_posts( $args );
    }

    /**
     * Get the cluster posts
     *
     * Defaults to entities connected to this cluster.
     *
     * @param  array  $args WP_Query args
     * @return array        Pedestal Post objects
     */
    public function get_posts( $args = [] ) {
        $query     = $this->get_posts_query( $args );
        $args_hash = md5( serialize( $query->query_vars ) );
        if ( ! empty( $this->cached_posts[ $args_hash ] ) ) {
            return $this->cached_posts[ $args_hash ];
        }
        $ped_posts                        = Post::get_posts_from_query( $query );
        $this->cached_posts[ $args_hash ] = $ped_posts;
        return $this->cached_posts[ $args_hash ];
    }

    /**
     * Helper for getting a WP_Query object
     *
     * @param  array  $args  Args to modify the query
     * @return WP_Query      WP_Query object
     */
    public function get_posts_query( $args = [] ) {
        $args                    = wp_parse_args( $args, [
            'post_type'      => Types::get_entity_post_types(),
            'connected_type' => $this->get_cluster_entity_connection_type(),
            'post_status'    => 'publish',
            'posts_per_page' => 20,
        ] );
        $paged                   = get_query_var( 'paged' );
        $args['paged']           = $paged ? $paged : 1;
        $args['connected_items'] = $this->post;
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
     * Get the number of subscribers following this cluster
     *
     * @return int
     */
    public function get_subscriber_count() {
        $email_groups   = Email_Groups::get_instance();
        $group_category = $this->get_mailchimp_group_category();
        return $email_groups->get_subscriber_count( $this->get_title(), $group_category );
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
        $key        = 'pedestal_cluster_unsent_entities_count_' . $this->get_id();
        $expiration = Utils::get_fuzzy_expire_time( HOUR_IN_SECONDS / 2 );
        $count      = get_transient( $key );

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
                'after'  => $last_date,
                'column' => 'post_date_gmt',
            ];
        }

        $entities = $this->get_posts( $args );
        $count    = count( $entities );

        // Store this as post meta to make sortable admin columns possible
        $this->set_meta( 'unsent_entities_count', $count );
        set_transient( $key, $count, $expiration );

        if ( $args['count_only'] ) {
            return $count;
        }
        return $entities;
    }

    /**
     * Get MailChimp group data for this cluster
     *
     * @return Object|false MailChimp Group object or false if not found
     */
    public function get_mailchimp_group() {
        if ( ! Types::is_followable_post_type( $this->get_post_type() ) ) {
            return false;
        }
        $email_groups = Email_Groups::get_instance();
        return $email_groups->get_group( $this->get_title(), $this->get_mailchimp_group_category() );
    }

    /**
     * Get a MailChimp group id for this cluster
     *
     * @return string|false MailChimp group id or false if not found
     */
    public function get_mailchimp_group_id() {
        $group = $this->get_mailchimp_group();
        if ( is_object( $group ) && isset( $group->id ) ) {
            return $group->id;
        }
        return false;
    }

    /**
     * Get the MailChimp group category name for this cluster
     * (plural name of the post type)
     *
     * @return string The MailChimp group category name
     */
    public function get_mailchimp_group_category() {
        return $this->get_type_name_plural();
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
     * @param array Existing context to filter
     * @return array Twig context
     */
    public function get_context( $context = [] ) {
        $context         = parent::get_context( $context );
        $context['slug'] = $this->get_slug();
        return $context;
    }
}
