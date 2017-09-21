<?php

namespace Pedestal\Objects;

use function Pedestal\Pedestal;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\User_Management;
use Pedestal\Posts\{
    Attachment,
    Post
};
use Pedestal\Posts\Clusters\{
    Cluster,
    Story
};

/**
 * Base User class
 */
class User extends Author {

    protected static $errors;

    private $user;

    public function __construct( $user ) {

        if ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', $user );
        } elseif ( is_string( $user ) && is_email( $user ) ) {
            $user = get_user_by( 'email', $user );
        } elseif ( is_string( $user ) ) {
            $user = get_user_by( 'login', $user );
        }

        $this->user = $user;

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
     * Get the avatar for the Author
     *
     * @param array|int $size
     * @return string|HTML|bool
     */
    public function get_avatar( $size ) {
        $img = $this->get_image_html( $size );
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
        $title_field = $this->get_meta( 'user_title' );
        if ( $title_field ) {
            return $title_field;
        }
        return $this->get_public_role_label();
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
        global $wpdb;
        $meta_name = $wpdb->prefix . 'user_img';
        $id = $this->get_meta( $meta_name );
        if ( ! $id ) {
            $meta_name = 'user_img';
            $id = $this->get_meta( $meta_name );
        }
        // FM Media fields are saved as strings, so type must be converted
        $id = absint( $id );
        $attachment = Attachment::get_by_post_id( $id );
        if ( $attachment instanceof Attachment ) {
            return $attachment->get_html( $size );
        }
        return false;
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
        $url = $base . '/' . $name . '/';
        return home_url( $url );
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
     * Get the user's stream object
     *
     * Default includes articles only.
     *
     * @param  array  $args WP_Query args
     * @return array
     */
    public function get_stream( $args = [] ) {
        $query = $this->get_stream_query( $args );
        return Post::get_posts_from_query( $query );
    }

    /**
     * Helper for getting a WP_Query object
     *
     * @param  array  $args  Args to modify the query
     * @return WP_Query      WP_Query object
     */
    public function get_stream_query( $args = [] ) {
        $defaults = [
            'author_name' => $this->get_user_login(),
            'post_type'   => Types::get_original_post_types(),
        ];
        $args = wp_parse_args( $args, $defaults );
        $args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
        return new \WP_Query( $args );
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
        if ( empty( $roles ) ) {
            return '';
        }
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

        $wpdb->update( $wpdb->users,
            [
                $key => $value,
            ], [
                'ID' => $this->get_id(),
            ]
        );
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
