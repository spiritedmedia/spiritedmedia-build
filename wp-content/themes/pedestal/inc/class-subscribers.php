<?php

namespace Pedestal;

use Pedestal\Objects\MailChimp;
use Pedestal\Email\Email_Groups;

class Subscribers {

    private $version = 1;

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Hook into WordPress through various actions
     */
    public function setup_actions() {
        add_action( 'wp_ajax_get_subscriber_data', [ $this, 'action_wp_ajax_nopriv_get_subscriber_data' ] );
        add_action( 'wp_ajax_nopriv_get_subscriber_data', [ $this, 'action_wp_ajax_nopriv_get_subscriber_data' ] );
    }

    /**
     * Handle AJAX request to get subscriber data for frontend
     */
    public function action_wp_ajax_nopriv_get_subscriber_data() {
        $referer = strtolower( wp_get_referer() );
        if ( strpos( $referer, get_site_url() ) !== 0 ) {
            wp_send_json_error();
        }
        $id = sanitize_text_field( $_POST['subscriberID'] );
        $data = $this->get_subscriber_data( $id );
        if ( empty( $data ) ) {
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
     * Request subscriber data from MailChimp and normalize
     *
     * @param  string $id MailChimp Unique Email Identifier or Email Address
     * @return array      Subscriber details
     */
    public function get_subscriber_data( $id = '' ) {
        $id = trim( $id );
        $mc = MailChimp::get_instance();
        $subscriber = false;
        // If ID is an email, use a different API
        if ( strpos( $id, '@' ) ) {
            $subscriber = $mc->get_contact( $id );
        } else {
            $args = [
                'unique_email_id' => $id,
            ];
            $data = $mc->get_list_contacts( $args );
            if ( ! empty( $data->members[0] ) ) {
                $subscriber = $data->members[0];
                if ( isset( $subscriber->_links ) ) {
                    unset( $subscriber->_links );
                }
            }
        }

        $output = [
            'mc_id'                      => '',    // An identifier for the address across all of MailChimp
            'since'                      => '',    // The date and time the subscribe confirmed their opt-in status
            'subscribed_to_list'         => false, // Subscriber’s current status
            'newsletter_subscriber'      => false, // Are they subscribed to the Daily Newsletter group?
            'breaking_news_subscriber'   => false, // Are they subscribed to the Breaking News group?
            'rating'                     => 1,     // Star rating for this member, between 1 and 5
            'current_member'             => false, // Status of subscriber's membership
            'member_level'               => 0,     // Current level of membership
            'recurring_member'           => false, // Is subscriber a recurring member?
            'suggested_recurring_amount' => 0,     // Suggested amount for recurring membership
            'no_promote'                 => false, // ???
            'major_donor'                => false, // Has the subscriber donated $500 or more?
            'member_expiration'          => 0,     // When does their membership expire?
            'donate_7'                   => false, // Has subscriber donated in the past 7 days?
            'donate_14'                  => false, // Has subscriber donated in the past 14 days?
            'donate_30'                  => false, // Has subscriber donated in the past 30 days?
            'donate_365'                 => false, // Has subscriber donated in the past 365 days?
        ];
        if ( ! $subscriber ) {
            return $output;
        }

        /*
            TODO
            It would be nice to have a mapping of this data somewhere
            Maybe I'll make one.

            The settings page for MERGE TAGS helps: https://us13.admin.mailchimp.com/lists/settings/merge-tags?id=286125
        */
        $output['mc_id'] = $subscriber->unique_email_id;
        // TODO Figure out what format of time stamp we want to send to JavaScript
        $output['since'] = $subscriber->timestamp_opt;
        if ( 'subscribed' == $subscriber->status ) {
            $output['subscribed_to_list'] = true;
        }
        $output['rating'] = intval( $subscriber->member_rating );

        $merge_fields = $subscriber->merge_fields;
        $output['current_member'] = (bool) $this->get_multiprop_value(
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
        $output['recurring_member'] = (bool) $this->get_multiprop_value(
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
                'TIRECRAMT',
                'DENRECRAMT',
            ],
            0
        );
        $output['no_promote'] = (bool) $this->get_multiprop_value(
            $merge_fields,
            [
                'NOPROMOTE',
            ]
        );
        $output['major_donor'] = (bool) $this->get_multiprop_value(
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
        $output['donate_7'] = (bool) $this->get_multiprop_value(
            $merge_fields,
            [
                'BPDONA7',
                'TIDONA7',
                'DENDONA7',
            ]
        );
        $output['donate_14'] = (bool) $this->get_multiprop_value(
            $merge_fields,
            [
                'BPDONA14',
                'TIDONA14',
                'DENDONA14',
            ]
        );
        $output['donate_30'] = (bool) $this->get_multiprop_value(
            $merge_fields,
            [
                'BPDONA30',
                'TIDONA30',
                'DENDONA30',
            ]
        );
        $output['donate_365'] = (bool) $this->get_multiprop_value(
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
        foreach ( $subscriber->interests as $id => $val ) {
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
     * Helper method for getting the value of one or more properties of an object
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
}
