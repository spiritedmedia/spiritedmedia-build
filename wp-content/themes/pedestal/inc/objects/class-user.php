<?php

namespace Pedestal\Objects;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\User_Management;

use Pedestal\Posts\Post;

use Pedestal\Objects\Stream;

use Pedestal\Posts\Attachment;

use Pedestal\Posts\Clusters\Cluster;

use Pedestal\Posts\Clusters\Story;

/**
 * Base User class
 */
class User extends Author {

    private $user;

    public function __construct( $user ) {

        if ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', $user );
        } else if ( is_string( $user ) && is_email( $user ) ) {
            $user = get_user_by( 'email', $user );
        } else if ( is_string( $user ) ) {
            $user = get_user_by( 'login', $user );
        }

        $this->user = $user;

    }

    /**
     * Get or create a user given their email address
     *
     * @param  string $email_address Email address for the user
     * @param  array  $user_data     User data
     * @return User
     */
    public static function get_or_create_user( $email_address, $user_data = [] ) {
        if ( get_user_by( 'email', $email_address ) ) {
            $user = get_user_by( 'email', $email_address );
        } else {

            $user_data_defaults = [
                'user_login' => md5( PEDESTAL_USER_HASH_PHRASE . $email_address ),
                'role'       => 'subscriber',
                'user_pass'  => wp_generate_password(),
            ];
            $user_data = wp_parse_args( $user_data, $user_data_defaults );
            $user_data['user_email'] = $email_address;

            $user_id = wp_insert_user( $user_data );
            if ( is_wp_error( $user_id ) ) {
                return $user_id;
            }
            $user = get_user_by( 'id', $user_id );

        }
        return new User( $user );
    }

    /**
     * Get the ID for the user
     *
     * @return int
     */
    public function get_id() {
        return $this->get_field( 'ID' );
    }

    /**
     * Get the display name for a user
     *
     * @return string
     */
    public function get_display_name() {
        return $this->get_field( 'display_name' );
    }

    /**
     * Set the display name for the user
     *
     * @param string $display_name
     */
    public function set_display_name( $display_name ) {
        $this->set_field( 'display_name', $display_name );
    }

    /**
     * Get the first name for a user
     *
     * @return string
     */
    public function get_first_name() {
        return $this->get_meta( 'first_name' );
    }

    /**
     * Set the first name for the user
     *
     * @param string $first_name
     */
    public function set_first_name( $first_name ) {
        $this->set_meta( 'first_name', $first_name );
    }

    /**
     * Get the last name for a user
     *
     * @return string
     */
    public function get_last_name() {
        return $this->get_meta( 'last_name' );
    }

    /**
     * Set the last name for the user
     *
     * @param string $last_name
     */
    public function set_last_name( $last_name ) {
        $this->set_meta( 'last_name', $last_name );
    }

    /**
     * Get the user login value for the user
     *
     * @return string
     */
    public function get_user_login() {
        return $this->get_field( 'user_login' );
    }

    /**
     * Get the email address for the user
     *
     * @return string
     */
    public function get_email() {
        return $this->get_field( 'user_email' );
    }

    /**
     * Set the email address for the user
     *
     * @param string $email
     */
    public function set_email( $email ) {
        $this->set_field( 'user_email', $email );
    }

    /**
     * Check whether the user has an avatar
     *
     * @return boolean
     */
    public function has_avatar() {
        if ( $this->get_email() ) {
            if ( $this->get_image_html( 1 ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the avatar for the Author
     *
     * @param array|int $size
     * @return string|HTML|bool
     */
    public function get_avatar( $size ) {

        if ( is_string( $size ) ) {
            $img = $this->get_image_html( $size );
        } elseif ( is_int( $size ) ) {
            // If an integer size is specified, we can't get a WordPress size,
            // so just get the default full size image
            $img = $this->get_image_html();
        }

        $role = $this->get_public_role();
        $role_class = 'c-avatar--' . $role['name'];
        $output = '<div class="c-avatar  ' . $role_class . '">';

        if ( $img ) {
            $output .= '<div class="c-avatar__img">';
            $output .= $img;
        } else {
             // Do nothing else because the fallback image is handled with CSS
             // SVG/PNG background images
             $output .= '<div class="c-avatar__img  c-avatar__img--fallback">';
        }

        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * Get the user's title position
     *
     * @return string
     */
    public function get_title() {
        if ( $title_field = $this->get_meta( 'user_title' ) ) {
            return $title_field;
        } else {
            return $this->get_public_role_label();
        }
    }

    /**
     * Get the user's extended bio
     *
     * This is used on the single author page.
     *
     * @return string
     */
    public function get_extended_bio() {
        return $this->get_meta( 'user_bio_extended' );
    }

    /**
     * Get the user's short bio
     *
     * This is used primarily at the bottom of articles.
     *
     * @return string
     */
    public function get_short_bio() {
        return $this->get_meta( 'user_bio_short' );
    }

    /**
     * Get the user image
     *
     * @return html
     */
    public function get_image_html( $size = 'full' ) {
        // FM Media fields are saved as strings, so type must be converted
        $id = (int) $this->get_meta( 'user_img' );
        if ( $attachment = Attachment::get_by_post_id( $id ) ) {
            return $attachment->get_html( $size );
        } else {
            return false;
        }
    }

    /**
     * Get the User's Parsely data
     *
     * @return string JSON-LD Parsely data
     */
    public function get_parsely_data() {
        $parsely = new \Pedestal\Objects\Parsely( [
            'scope' => 'user',
            'id'    => $this->get_id(),
        ] );
        return $parsely->get_data();
    }

    /**
     * Get the User's public-facing email
     *
     * If the User's public email address needs to be different than the address
     * registered to their account, then this field can be used to override.
     *
     * @return string Public email if available, account email if not
     */
    public function get_public_email() {
        $public_email = $this->get_meta( 'public_email' );
        if ( $public_email ) {
            return $public_email;
        } else {
            return $this->get_email();
        }
    }

    /**
     * Get the Twitter username for the user
     *
     * @return string
     */
    public function get_twitter_username() {
        return ltrim( $this->get_meta( 'twitter_username' ), '@' );
    }

    /**
     * Get the Facebook profile URL for the user
     *
     * @return string
     */
    public function get_facebook_profile_url() {
        return $this->get_meta( 'facebook_profile' );
    }

    /**
     * Get the user's role for use in the permalink
     *
     * @return string
     */
    public function get_permalink_role() {
        return User_Management::get_role_url_base( $this->get_primary_role() );
    }

    /**
     * Get the user's permalink
     *
     * @TODO Would probably be better to have filtered `author_link` but that
     *     wasn't going too well so... this will do for now
     *
     * @return string
     */
    public function get_permalink() {
        $base = $this->get_permalink_role();

        if ( 'feat_contributor' == $this->get_primary_role() ) {
            $name = $this->get_user_login();
        } else {
            $name = $this->get_display_name();
        }

        $name = sanitize_title( $name );
        $url = $base . '/' . $name;
        return home_url( $url );
    }

    /**
     * Get the user's registration date
     *
     * @return string
     */
    public function get_registered_date() {
        return $this->get_field( 'user_registered' );
    }

    /**
     * Get the description for the user
     *
     * @return string
     */
    public function get_description() {
        return $this->get_meta( 'description' );
    }

    /**
     * Set the description for the user
     *
     * @param string
     */
    public function set_description( $description ) {
        $this->set_meta( 'description', $description );
    }

    /**
     * Get the pagination data for the user's stream
     *
     * @return array Pagination data
     */
    public function get_stream_pagination() {
        $stream = $this->get_stream();
        return $stream::get_pagination( $stream->get_query() );
    }

    /**
     * Get the user entities array
     *
     * Default includes articles only.
     *
     * @param  array  $args WP_Query args
     * @return array        Entities
     */
    public function get_entities( $args = [] ) {
        $stream = $this->get_stream();
        return $stream->get_stream();
    }

    /**
     * Get the user's stream object
     *
     * Default includes articles only.
     *
     * @param  array  $args WP_Query args
     * @return array
     */
    public function get_stream( $args = [] ) {
        $defaults = [
            'author_name' => $this->get_user_login(),
            'post_type'   => Types::get_editorial_post_types(),
        ];
        $args = wp_parse_args( $args, $defaults );
        return new Stream( $args );
    }

    /**
     * Get the clusters the user is following
     *
     * @param  array      $cluster_types An array of cluster post types to
     *     get. Defaults to all.
     * @return array|bool                 Returns array of clusters the user
     *     is following. Returns false if $cluster_types is not an array
     */
    public function get_following_clusters( $cluster_types = [] ) {

        if ( empty( $cluster_types ) ) {
            $cluster_types = Types::get_cluster_post_types();
        } else if ( ! is_array( $cluster_types ) ) {
            return false;
        }

        $connected_types = [];

        foreach ( $cluster_types as $cluster_type ) {
            $connected_types[] = Post::get_user_connection_type( $cluster_type );
        }

        $args = [
            'post_type'         => $cluster_types,
            'post_status'       => 'publish',
            'posts_per_page'    => 100,
            'connected_type'    => $connected_types,
            'connected_items'   => $this->user,
        ];

        $query = new \WP_Query( $args );
        $clusters = [];
        foreach ( $query->posts as $post ) {
            $cluster_class = Types::get_post_type_class( $post->post_type );
            $clusters[] = new $cluster_class( $post );
        }
        return $clusters;
    }

    /**
     * Whether or not this user is following an cluster
     *
     * @param  obj  $cluster The cluster object to check
     * @return bool
     */
    public function is_following_cluster( $cluster ) {

        $cluster_type = Post::get_post_type( $cluster );
        $cluster_class = Types::get_post_type_class( $cluster_type );
        $connected_type = $cluster->get_cluster_user_connection_type( $cluster_type );

        $args = [
            'post_type'         => $cluster_type,
            'post_status'       => 'publish',
            'post__in'          => [ $cluster->get_id() ],
            'posts_per_page'    => 1,
            'connected_type'    => $connected_type,
            'connected_items'   => $this->user,
        ];

        $query = new \WP_Query( $args );
        if ( ! empty( $query->posts ) ) {
            $following = new $cluster_class( $query->posts[0] );
        } else {
            $following = false;
        }
        return $following;
    }

    /**
     * Create a connection between user and cluster they want to follow
     *
     * @param Cluster
     * @return bool
     */
    public function follow_cluster( $cluster ) {
        $type = $cluster->get_cluster_user_connection_type();
        return p2p_type( $type )->connect( $cluster->get_id(), $this->get_id() );
    }

    /**
     * Remove the connection between user and cluster they want to unfollow
     *
     * @param Cluster
     * @return bool
     */
    public function unfollow_cluster( $cluster ) {
        $type = $cluster->get_cluster_user_connection_type();
        return p2p_type( $type )->disconnect( $cluster->get_id(), $this->get_id() );
    }

    /**
     * Check whether a user is subscribed to the daily newsletter and breaking news
     *
     * @return bool
     */
    public function is_subscribed_daily_newsletter() {
        $terms = wp_get_object_terms( $this->get_id(), 'pedestal_subscriptions', [ 'fields' => 'slugs' ] );
        return in_array( 'daily-newsletter', $terms );
    }

    /**
     * Subscribe the user to the daily newsletter and breaking news
     */
    public function subscribe_daily_newsletter() {
        wp_set_object_terms( $this->get_id(), [ 'daily-newsletter' ], 'pedestal_subscriptions', true );
        $this->set_subscribe_daily_newsletter_date( time() );
    }

    /**
     * Unsubscribe the user from the daily newsletter and breaking news
     */
    public function unsubscribe_daily_newsletter() {
        wp_set_object_terms( $this->get_id(), [], 'pedestal_subscriptions' );
        $this->unset_subscribe_daily_newsletter_date();
    }

    /**
     * Set the time the user subscribed to the Daily Newsletter and Breaking News emails
     *
     * @param int $time Timestamp
     */
    public function set_subscribe_daily_newsletter_date( $time ) {
        return $this->set_meta( 'subscribed_daily_newsletter', $time );
    }

    /**
     * Unset the time the user subscribed to the Daily Newsletter and Breaking News emails
     *
     * Rather than delete the meta field entirely, we set the time to `0` in
     * order to report on user unsubscriptions.
     *
     * @return boolean
     */
    public function unset_subscribe_daily_newsletter_date() {
        return $this->set_meta( 'subscribed_daily_newsletter', 0 );
    }

    /**
     * Get the user's public role label
     *
     * @return string
     */
    public function get_public_role_label() {
        $role = $this->get_public_role();
        return $role['label'];
    }

    /**
     * Get the user's public role
     *
     * We don't want to expose whether someone is Administrator or Editor, so
     * instead we just call them staff.
     *
     * @return array Array of name and label of public role
     */
    public function get_public_role() {
        $public_role = [];
        $role = $this->get_primary_role();

        switch ( $role ) {
            case 'feat_contributor':
                $public_role['name'] = 'contributor';
                $public_role['label'] = 'Featured Contributor';
                break;

            default:
                // Default to staff role because we don't have a special
                // treatment set up for everybody else that's not a FC
                $public_role['name'] = 'staff';
                $public_role['label'] = 'Staff Writer';
                break;
        }

        return $public_role;
    }

    /**
     * Get the user's primary role
     *
     * @return string
     */
    public function get_primary_role() {
        $roles = $this->get_roles();
        return $roles[0];
    }

    /**
     * Get all of the user's roles
     *
     * @return array
     */
    private function get_roles() {
        return $this->user->roles;
    }

    /**
     * Get a user's field
     *
     * @param string $key
     * @return mixed
     */
    protected function get_field( $key ) {
        return $this->user->$key;
    }

    /**
     * Set a field for a user
     *
     * @param string $key
     * @param mixed $value
     */
    protected function set_field( $key, $value ) {
        global $wpdb;

        $wpdb->update( $wpdb->users, [ $key => $value ], [ 'ID' => $this->get_id() ] );
        clean_user_cache( $this->get_id() );

        $this->user = get_user_by( 'id', $this->get_id() );
    }

    /**
     * Get a meta value for a user
     *
     * @param string
     * @return mixed
     */
    protected function get_meta( $key ) {
        return get_user_meta( $this->get_id(), $key, true );
    }

    /**
     * Delete a user meta pair
     *
     * @param  string $key   Metadata name.
     * @param  string $value Metadata value. Optional.
     *
     * @return boolean
     */
    protected function delete_meta( $key, $value = '' ) {
        return delete_user_meta( $this->get_id(), $key, $value );
    }

    /**
     * Set a meta value for a user
     *
     * @param string $key
     * @param mixed $value
     */
    protected function set_meta( $key, $value ) {
        update_user_meta( $this->get_id(), $key, $value );
    }
}
