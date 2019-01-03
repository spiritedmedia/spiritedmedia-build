<?php

namespace Pedestal\Audience;

use Pedestal\Objects\MailChimp;
use Pedestal\Email\Email_Groups;

class Audience {

    private $version = 4;

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into WordPress through various actions
     */
    public function setup_actions() {
        add_action( 'wp_ajax_get_contact_data', [ $this, 'action_wp_ajax_nopriv_get_contact_data' ] );
        add_action( 'wp_ajax_nopriv_get_contact_data', [ $this, 'action_wp_ajax_nopriv_get_contact_data' ] );
    }

    /**
     * Hook into WordPress through various filters
     */
    public function setup_filters() {
        add_filter( 'body_class', [ $this, 'filter_body_class' ] );
    }

    /**
     * Handle AJAX request to get contact data for frontend
     */
    public function action_wp_ajax_nopriv_get_contact_data() {
        $referer = strtolower( wp_get_referer() );
        if ( strpos( $referer, get_site_url() ) !== 0 ) {
            wp_send_json_error();
        }
        $id = sanitize_text_field( $_POST['contactID'] );
        if ( empty( $id ) ) {
            error_log( 'BAD contactID ' . $_POST['contactID'] );
            wp_send_json_error();
        }
        $data = $this->get_contact_data( $id );
        if ( empty( $data['mc_id'] ) ) {
            wp_send_json_error();
        }

        wp_send_json_success( [
            'version' => $this->version,
            'updated' => date( 'c' ),
            'data'    => $data,
        ] );
        die();
    }

    /**
     * Filter the body classes
     *
     * @param array $classes
     * @return array
     */
    public function filter_body_class( $classes ) {
        // Allow target audiences to be set from a query parameter
        // ?target-audience=foo --> is-target-audience--foo
        if ( ! empty( $_GET['target-audience'] ) ) {
            $target_audience = sanitize_title( $_GET['target-audience'] );
            // Disable the cookie script from taking action and overriding what we set here
            $classes[] = 'is-target-audience--disabled';
            $classes[] = 'is-target-audience--' . $target_audience;
        } else {
            $classes[] = 'is-target-audience--unidentified';
        }
        return $classes;
    }

    /**
     * Request contact data from MailChimp and normalize
     *
     * @param  string $id MailChimp Unique Email Identifier or Email Address
     * @return array      Contact details
     */
    public function get_contact_data( $id = '' ) {
        $id = trim( $id );
        if ( empty( $id ) ) {
            return false;
        }
        $mc = MailChimp::get_instance();
        $contact = false;
        // If ID is an email, use a different API
        if ( strpos( $id, '@' ) ) {
            $contact = $mc->get_contact( $id );
        } else {
            $args = [
                'unique_email_id' => $id,
            ];
            $data = $mc->get_list_contacts( $args );
            if ( ! empty( $data->members[0] ) ) {
                $contact = $data->members[0];
                if ( isset( $contact->_links ) ) {
                    unset( $contact->_links );
                }
            }
        }

        $output = [
            'mc_id'                      => '',    // An identifier for the address across all of MailChimp
            'since'                      => '',    // The date and time the subscribe confirmed their opt-in status
            'subscribed_to_list'         => false, // Contact’s current status
            'newsletter_subscriber'      => false, // Are they subscribed to the Daily Newsletter group?
            'breaking_news_subscriber'   => false, // Are they subscribed to the Breaking News group?
            'rating'                     => 1,     // Star rating for this member, between 1 and 5
            'current_member'             => false, // Status of contact's membership
            'member_level'               => 0,     // Current level of membership
            'recurring_member'           => false, // Is contact a recurring member?
            'suggested_recurring_amount' => 0,     // Suggested amount for recurring membership
            'no_promote'                 => false, // ???
            'major_donor'                => false, // Has the contact donated $500 or more?
            'member_expiration'          => 0,     // When does their membership expire?
            'donate_7'                   => false, // Has contact donated in the past 7 days?
            'donate_14'                  => false, // Has contact donated in the past 14 days?
            'donate_30'                  => false, // Has contact donated in the past 30 days?
            'donate_365'                 => false, // Has contact donated in the past 365 days?
        ];
        if ( ! $contact ) {
            return $output;
        }

        // If the response back from Mailchimp is a 404 then bail
        if ( ! empty( $contact->status ) && '404' == $contact->status ) {
            return $output;
        }

        /*
            TODO
            It would be nice to have a mapping of this data somewhere
            Maybe I'll make one.

            The settings page for MERGE TAGS helps: https://us13.admin.mailchimp.com/lists/settings/merge-tags?id=286125
        */
        $output['mc_id'] = $contact->unique_email_id;
        // TODO Figure out what format of time stamp we want to send to JavaScript
        $output['since'] = $contact->timestamp_opt;
        if ( 'subscribed' == $contact->status ) {
            $output['subscribed_to_list'] = true;
        }
        $output['rating'] = intval( $contact->member_rating );

        $merge_fields = $contact->merge_fields;
        $output['current_member'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                'BPCURMEM',
                'TICURMEM',
                'DENCURMEM',
            ]
        );
        $output['member_level'] = (int) $this->get_multiprop_value(
            $merge_fields,
            [
                'BPMEMLVL',
                'TIMEMLVL',
                'DENMEMLVL',
            ],
            0
        );
        $output['recurring_member'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                'BPRECRMEM',
                'TIRECRMEM',
                'DENRECRMEM',
            ]
        );
        $output['suggested_recurring_amount'] = (int) $this->get_multiprop_value(
            $merge_fields,
            [
                'BPRECRAMT',
                'TINRECRAMT',
                'DENRECRAMT',
            ],
            0
        );
        $output['no_promote'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                'NOPROMOTE',
            ]
        );
        $output['major_donor'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                '500DONOR',
            ]
        );
        $output['member_expiration'] = $this->get_multiprop_value(
            $merge_fields,
            [
                'BPEXPDATE',
                'TIEXPDATE',
                'DENEXPDATE',
            ],
            0
        );
        $output['donate_7'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                'BPDONA7',
                'TIDONA7',
                'DENDONA7',
            ]
        );
        $output['donate_14'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                'BPDONA14',
                'TIDONA14',
                'DENDONA14',
            ]
        );
        $output['donate_30'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                'BPDONA30',
                'TIDONA30',
                'DENDONA30',
            ]
        );
        $output['donate_365'] = $this->get_multiprop_bool_value(
            $merge_fields,
            [
                'BPDONA365',
                'TIDONA365',
                'DENDONA365',
            ]
        );

        $email_groups = Email_Groups::get_instance();

        $newsletter_groups = $email_groups->get_groups( 'Newsletters' );
        $newsletter_groups = $newsletter_groups->groups;

        $story_groups = $email_groups->get_groups( 'Stories' );
        $story_groups = $story_groups->groups;

        $the_groups = array_merge( $newsletter_groups, $story_groups );
        foreach ( $contact->interests as $id => $val ) {
            if ( $val ) {
                foreach ( $the_groups as $group ) {
                    if ( $id == $group->id ) {
                        switch ( $group->name ) {
                            case 'Daily Newsletter':
                                $output['newsletter_subscriber'] = true;
                                break;

                            case 'Breaking News':
                                $output['breaking_news_subscriber'] = true;
                                break;
                        }
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Helper method for getting a single value of one or more properties of an object.
     *
     * We loop over the properties looking for one that is set on the given object.
     * If found, the value of that property is returned.
     * Otherwise $default_value is returned if none of the properties are found.
     *
     * @param  object  $obj           Object with properties to check
     * @param  array   $properties    One or more properties to check
     * @param  boolean $default_value Default value to return if all properties not found
     * @return mixed                  Value of found property or default value
     */
    private function get_multiprop_value( $obj, $properties = [], $default_value = false ) {
        if ( ! is_object( $obj ) ) {
            return false;
        }
        if ( ! is_array( $properties ) ) {
            $properties = [ $properties ];
        }
        foreach ( $properties as $prop ) {
            if ( isset( $obj->{$prop} ) ) {
                return $obj->{$prop};
            }
        }
        return $default_value;
    }

    /**
     * Helper method for ensuring booleans are properly returned from get_multiprop_value()
     *
     * @param  object  $obj           Object with properties to check
     * @param  array   $properties    One or more properties to check
     * @param  boolean $default_value Default value to return if all properties not found
     * @return mixed                  Value of found property or default value
     */
    private function get_multiprop_bool_value( $obj, $properties = [], $default_value = false ) {
        $val = $this->get_multiprop_value( $obj, $properties, $default_value );
        if ( is_string( $val ) ) {
            $val = strtolower( $val );
            return $val === 'true' ? true : false;
        }
        return $val;
    }
}
