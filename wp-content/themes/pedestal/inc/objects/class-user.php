<?php

namespace Pedestal\Objects;

use function Pedestal\Pedestal;

use Pedestal\Utils\Utils;
use Pedestal\Icons;
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
     * Get the user's avatar
     *
     * @param array|int $size
     * @param array     $img_atts Optional image HTML attributes
     * @param boolean   $fallback [true] Display the fallback icon?
     * @return string|false Image HTML or logo icon SVG or false if fallback disabled
     */
    public function get_avatar( $size, $img_atts = [], $fallback = true ) {
        $image = $this->get_user_image();

        if ( ! Types::is_attachment( $image ) ) {
            return $fallback ? Icons::get_logo( 'logo-icon' ) : false;
        }

        $img = $image->get_html( $size, $img_atts );

        $output  = '<div class="c-avatar">';
        $output .= '<div class="c-avatar__img">';
        $output .= $img;
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * Get the user's uploaded image
     *
     * @return \Pedestal\Posts\Attachment|false
     */
    public function get_user_image() {
        global $wpdb;
        $meta_name = $wpdb->prefix . 'user_img';
        return Attachment::get( $this->get_meta( $meta_name ) );
    }

    /**
     * Get the user's title position
     *
     * @return string User title field, or public role, or empty string
     */
    public function get_title() {
        $title_field = $this->get_meta( 'user_title' );
        if ( $title_field ) {
            return $title_field;
        }
        $role = $this->get_public_role();
        return $role['label'] ?? '';
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
     * Get the Instagram username for the user
     *
     * @return string
     */
    public function get_instagram_username() {
        return ltrim( $this->get_meta( 'instagram_username' ), '@' );
    }

    /**
     * Get the phone number for the user
     *
     * The value of `phone_number` should include the 3-digit area code and the
     * 7-digit number. The value of `phone_number` can include punctuation or
     * omit it but that doesn't matter because the number will be formatted when
     * calling this method.
     *
     * @link https://stackoverflow.com/a/10741461
     * @param boolean $parens [false] Format with parentheses around the area code?
     * @return string Phone number in `123-456-7890` or `(123) 456-7890` format
     */
    public function get_phone_number( $parens = false ) {
        $number  = $this->get_meta( 'phone_number' );
        $replace = $parens ? '($1) $2-$3' : '$1-$2-$3';
        return preg_replace( '~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', $replace, $number );
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
        $url  = $base . '/' . $name . '/';
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
     * Get the user's Pedestal Post objects
     *
     * By default includes articles only.
     *
     * @param  array  $args WP_Query args
     * @return array Pedestal Post objects
     */
    public function get_posts( $args = [] ) {
        $query = $this->get_posts_query( $args );
        return Post::get_posts_from_query( $query );
    }

    /**
     * Helper for getting a WP_Query object
     *
     * @param  array  $args  Args to modify the query
     * @return WP_Query      WP_Query object
     */
    public function get_posts_query( $args = [] ) {
        $defaults      = [
            'author_name' => $this->get_user_login(),
            'post_type'   => Types::get_original_post_types(),
        ];
        $args          = wp_parse_args( $args, $defaults );
        $args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
        return new \WP_Query( $args );
    }

    /**
     * Get the user's public role
     *
     * We don't want to expose whether someone is Administrator or Editor.
     *
     * This method is close to being deprecated as it currently only provides a
     * public role for Featured Contributors, a deprecated role and editorial
     * series.
     *
     * @return array Array of name and label of public role
     */
    public function get_public_role() {
        $public_role = [
            'name'  => '',
            'label' => '',
        ];
        $role        = $this->get_primary_role();
        if ( 'feat_contributor' == $role ) {
            $public_role['name']  = 'contributor';
            $public_role['label'] = 'Featured Contributor';
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

    /**
     * Setup the metaboxes closed by default for this user
     *
     * These metaboxes are currently collapsed by default:
     *
     * - Footnotes
     * - Authors
     */
    public function setup_default_collapsed_metaboxes() {
        $collapsed_metabox_ids = [
            'fm_meta_box_footnotes',
            'coauthorsdiv',
        ];
        foreach ( get_post_types() as $post_type ) {
            $option_name = 'closedpostboxes_' . $post_type;
            $global      = true;
            update_user_option( $this->get_id(), $option_name, $collapsed_metabox_ids, $global );
        }
    }

    /**
     * Get data about this user formatted as a Schema.org Person
     *
     * @link https://schema.org/Person
     *
     * @return array
     */
    public function get_schema_data() {
        $image     = $this->get_user_image();
        $image_url = '';
        if ( Types::is_attachment( $image ) ) {
            $image_url = $image->get_url( 'thumbnail' );
        }
        $data = [
            '@type' => 'Person',
            'name'  => $this->get_display_name(),
            'email' => $this->get_public_email(),
            'image' => $image_url,
            'url'   => $this->get_permalink(),
        ];
        array_walk( $data, function( &$v ) {
            $v = Utils::sanitize_string_for_json( $v );
        } );
        return $data;
    }

    /**
     * Check if the argument is a valid User object
     *
     * @param mixed $maybe_user
     * @return boolean
     */
    public static function is_user( $maybe_user ) {
        if ( is_object( $maybe_user ) && is_a( $maybe_user, __CLASS__ ) ) {
            return true;
        }
        return false;
    }
}
