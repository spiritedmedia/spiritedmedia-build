<?php

namespace Pedestal\Posts\Clusters;

use DateTime;
use \Pedestal\Utils\Utils;

use Pedestal\Posts\Entities\Embed;

/**
 * Person
 *
 * @link http://schema.org/Person
 */
class Person extends Cluster {

    protected static $post_type = 'pedestal_person';

    protected $email_type = 'person updates';

    /**
     * Get the Person's name prefix
     *
     * @link http://schema.org/honorificPrefix
     *
     * @return string
     */
    public function get_name_prefix() {
        return $this->get_person_name_field( 'prefix' );
    }

    /**
     * Get the Person's first name
     *
     * @link http://schema.org/honorificSuffix
     *
     * @return string
     */
    public function get_first_name() {
        return $this->get_person_name_field( 'first' );
    }

    /**
     * Get the Person's middle name
     *
     * @link http://schema.org/additionalName
     *
     * @return string
     */
    public function get_middle_name() {
        return $this->get_person_name_field( 'middle' );
    }

    /**
     * Get the Person's nickname
     *
     * @link http://schema.org/additionalName
     *
     * @return string
     */
    public function get_nickname() {
        return $this->get_person_name_field( 'nickname' );
    }

    /**
     * Get the Person's last name
     *
     * @link http://schema.org/familyName
     *
     * @return string
     */
    public function get_last_name() {
        return $this->get_person_name_field( 'last' );
    }

    /**
     * Get the Person's name suffix
     *
     * @link http://schema.org/givenName
     *
     * @return string
     */
    public function get_name_suffix() {
        return $this->get_person_name_field( 'suffix' );
    }

    /**
     * Get the Person's bio
     *
     * Simple wrapper for `Post->get_description()`
     *
     * @link http://schema.org/description
     *
     * @return string
     */
    public function get_bio() {
        return $this->get_description();
    }

    /**
     * Get the Person's URL
     *
     * @link http://schema.org/url
     *
     * @return string
     */
    public function get_url() {
        return $this->get_person_details_field( 'url' );
    }

    /**
     * Get the Person's "known for" field
     *
     * @link http://schema.org/jobTitle
     *
     * @return string
     */
    public function get_known_for() {
        $known_for = $this->get_person_details_field( 'known_for' );
        if ( empty( $known_for ) ) {
            return $this->get_person_details_field( 'role' );
        }
        return $known_for;
    }

    /**
     * Get the Person's age
     *
     * @return string
     */
    public function get_age() {
        $age = $this->get_person_details_field( 'age' );
        if ( ! empty( $dob = $this->get_person_details_field( 'dob' ) ) ) {
            $dob = new DateTime( date( 'Y-m-d', $dob ) );
            $today = new DateTime( 'today' );
            $age = $dob->diff( $today )->y;
        }
        return $age;
    }

    /**
     * Get the Person's Twitter handle
     *
     * @return string
     */
    public function get_twitter_handle() {
        return Embed::get_twitter_username_from_url( $this->get_twitter_profile_url() );
    }

    /**
     * Get the Person's Twitter profile URL
     *
     * @return string URL
     */
    public function get_twitter_profile_url() {
        return $this->get_person_social_field( 'twitter' );
    }

    /**
     * Get the Person's Instagram handle
     * @return string
     */
    public function get_instagram_handle() {
        $url = $this->get_instagram_url();
        if ( ! $url ) {
            return;
        }
        $handle = str_ireplace( 'https://www.instagram.com/', '', $url );
        // Strip whatspace characters and / from both ends of the string
        $handle = trim( $handle, " \t\n\r\0\x0B\/" );
        return $handle;
    }

    /**
     * Get the Person's Instagram URL
     * @return string URL
     */
    public function get_instagram_url() {
        return $this->get_person_social_field( 'instagram' );
    }

    /**
     * Get the Person's LinkedIn URL
     *
     * @return string
     */
    public function get_linkedin_url() {
        return $this->get_person_social_field( 'linkedin' );
    }

    /**
     * Get the Person's short name
     *
     * Short name is a combination of the `first_name` and `last_name` fields.
     *
     * @return string
     */
    public function get_short_name( $middle_initial = true ) {
        $name = '';
        $name .= $this->get_first_name() . ' ';
        if ( $middle_initial && $middle = $this->get_middle_name() ) {
            $name .= $middle[0] . '. ';
        }
        $name .= $this->get_last_name();
        return $name;
    }

    /**
     * Get the Person's full name
     *
     * Full name is a combination of all the available name fields.
     *
     * @param bool $sortable Display last name first?
     * @return string
     */
    public function get_full_name( $sortable = false ) {
        $name = '';
        $prefix = $this->get_name_prefix();
        $middle = $this->get_middle_name();
        $nickname = $this->get_nickname();
        $suffix = $this->get_name_suffix();

        if ( $prefix ) {
            $name .= $prefix . ' ';
        }

        if ( $sortable ) {
            $name .= $this->get_last_name() . ', ';
        } else {
            $name .= $this->get_first_name() . ' ';
        }

        if ( $middle && ! $sortable ) {
            $name .= $middle . ' ';
        }

        if ( $nickname && ! $sortable ) {
            $name .= '“' . $nickname . '” ';
        }

        if ( $sortable ) {
            $name .= $this->get_first_name() . $middle;
        } else {
            $name .= $this->get_last_name();
        }

        if ( $suffix ) {
            $name .= ' ' . $suffix;
        }

        return $name;
    }

    /**
     * Set the Person's title from full name
     *
     * @return string
     */
    public function set_person_title() {
        $this->set_title( $this->get_full_name() );
    }

    /**
     * Get a Person name field
     *
     * @param string $field Field key to get
     */
    public function get_person_name_field( $field ) {
        return $this->get_meta( 'person_name_' . $field );
    }

    /**
     * Get a Person details field
     *
     * @param string $field Field key to get
     */
    public function get_person_details_field( $field ) {
        return $this->get_meta( 'person_details_' . $field );
    }

    /**
     * Get a Person social media field
     *
     * @param  string $field Field key to get
     * @return string        Social media profile URL
     */
    public function get_person_social_field( $field ) {
        return $this->get_meta( 'person_social_' . $field );
    }
}
