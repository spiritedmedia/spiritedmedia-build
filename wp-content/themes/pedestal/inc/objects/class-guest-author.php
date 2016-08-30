<?php

namespace Pedestal\Objects;

class Guest_Author extends Author {

    public function __construct( $guest_author ) {

        $this->guest_author = $guest_author;

    }

    /**
     * Get the type
     * @return string
     */
    public function get_type() {
        return 'guest_author';
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
     * Get the first name for a user
     *
     * @return string
     */
    public function get_first_name() {
        return $this->guest_author->first_name;
    }

    /**
     * Get the last name for a user
     *
     * @return string
     */
    public function get_last_name() {
        return $this->guest_author->last_name;
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
     * Get the user's permalink
     *
     * @return string
     */
    public function get_permalink() {
        return get_author_posts_url( $this->get_id(), $this->get_field( 'user_nicename' ) );
    }

    /**
     * Get the avatar for the guest author
     *
     * Priority:
     *
     * 1. Uploaded image
     * 2. Gravatar
     * 3. Site logo
     *
     * @param array|int $size
     * @return string|HTML
     */
    public function get_avatar( $size ) {
        $thumbnail_id = $this->get_meta( '_thumbnail_id' );

        if ( $thumbnail_id && $attachment = Attachment::get_by_post_id( (int) $thumbnail_id ) ) {
            if ( ! is_array( $size ) ) {
                $size = [ $size, $size ];
            }
            return $attachment->get_html( $size, [
                'class' => 'avatar',
                'width' => $size[0],
                'height' => $size[1],
            ] );
        } else {
            return '<i class="icon icon-logo"></i>';
        }
    }

    /**
     * Get the description for the user
     *
     * @return string
     */
    public function get_description() {
        return $this->guest_author->description;
    }

    /**
     * Get a user's field
     *
     * @param string $key
     * @return mixed
     */
    protected function get_field( $key ) {
        return $this->guest_author->$key;
    }

    /**
     * Get a meta value for a guest author post
     *
     * @param string
     * @return mixed
     */
    protected function get_meta( $key ) {
        return get_post_meta( $this->get_id(), $key, true );
    }

    /**
     * Set a meta value for a guest author post
     *
     * @param string $key
     * @param mixed $value
     */
    protected function set_meta( $key, $value ) {
        update_post_meta( $this->get_id(), $key, $value );
    }
}
