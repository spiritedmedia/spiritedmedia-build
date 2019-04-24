<?php
namespace Pedestal\Email;
use Pedestal\Objects\MailChimp;

class Email_Preferences {

    /**
     * Slug of the page where this should be displaed
     *
     * @var string
     */
    private $post_name = 'email-preferences';

    /**
     * Instance of the MailChimp class
     *
     * @var object
     */
    private static $mc;

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            static::$mc = MailChimp::get_instance();
        }
        return $instance;
    }

    /**
     * Hook into WordPress via actions
     */
    public function setup_actions() {
        add_action( 'template_redirect', [ $this, 'action_template_redirect' ], 10, 2 );
    }

    /**
     * Make sure the /email-preferences/ page has been created, create it if it doesn't exist.
     *
     * @return void Bail if the page exists or we're not requesting /email-prefernces/
     */
    public function action_template_redirect() {
        $post_name = get_query_var( 'name' );

        if ( ! is_404() || empty( $post_name ) || $post_name != $this->post_name ) {
            return;
        }

        $new_page_args = [
            'post_name'   => $this->post_name,
            'post_title'  => 'Email Preferences',
            'post_type'   => 'page',
            'post_status' => 'publish',
        ];

        $post_id = wp_insert_post( $new_page_args );
        if ( $post_id ) {
            // Now that the page has been created redirect back to it
            $redirect_url = get_permalink( $post_id );
            wp_safe_redirect( $redirect_url );
            die();
        }
    }

    /**
     * Get an email address for a given unique email id
     *
     * @param  string $unique_email_id ID provided by Mailchimp
     * @return string|false            Email address of contact or false if not found
     */
    public static function get_email_by_unique_email_id( $unique_email_id ) {
        $contact = static::$mc->get_contact_by_unique_email_id( $unique_email_id );
        if ( empty( $contact->email_address ) ) {
            return false;
        }
        return $contact->email_address;
    }

    /**
     * Get a list of all groups and whether the contact is subscribed or not
     *
     * @param  string $email Email address of contact to get groups for
     * @return array|false   Array of group objects or false if contact is not found
     */
    public static function get_contact_groups( $email ) {
        $contact = static::$mc->get_contact($email );
        if ( ! $contact ) {
            return false;
        }

        $raw_groups = static::$mc->get_all_groups();
        $groups     = [];
        foreach ( $raw_groups as $group ) {
            if ( ! is_object( $group ) ) {
                continue;
            }
            $group->subscribed = null;
            if ( isset( $contact->interests->{ $group->id } ) ) {
                $group->subscribed = boolval( $contact->interests->{ $group->id } );
            }
            $groups[ $group->category_title ][] = $group;
        }
        return $groups;
    }

    /**
     * Save the modified preferences for a contact back to Mailchimp
     *
     * @param  string  $email                  Email address of the contact
     * @param  array   $groups_to_subscribe_to List of groups to associate with contact
     * @return boolean                         Whether the preferences were saved or not
     */
    public static function save_preferences( $email = '', $groups_to_subscribe_to = [] ) {
        $all_groups    = static::$mc->get_all_groups();
        $all_group_ids = wp_list_pluck( $all_groups, 'id' );

        $groups_to_save = [];
        foreach ( $all_group_ids as $id ) {
            $val = false;
            if ( in_array( $id, $groups_to_subscribe_to ) ) {
                $val = true;
            }
            $groups_to_save[ $id ] = $val;
        }
        $args   = [
            'interests' => $groups_to_save,
        ];
        $result = static::$mc->subscribe_contact( $email, $args );
        return is_object( $result );
    }
}
