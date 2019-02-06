<?php

/**
 * Let's nail down some concepts
 *  - Each site has it's own list of contacts, all subscribers belong to a list
 *  - Contacts can be part of groups and we can send a campaign to a group of subscribers
 *  - Groups can be categorized (a requirement of MailChimp)
 */
namespace Pedestal\Objects;

use Pedestal\Utils\Utils;

class MailChimp {

    /**
     * Default sender info
     *
     * @var array
     */
    protected $sender_info = [];

    /**
     * Cache for all GET requests sent to the MailChimp API
     *
     * @var array
     */
    protected $get_request_cache = [];

    /**
     * Cache of the site's main list
     *
     * @var object|false
     */
    protected $site_list = false;

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup();
        }
        return $instance;
    }

    /**
     * Check that we have the proper API credentials set up
     */
    public function setup() {
        if ( ! defined( 'MAILCHIMP_API_KEY' ) ) {
            wp_die( 'MailChimp API key is missing. See <a href="https://us2.admin.mailchimp.com/account/api/">https://us2.admin.mailchimp.com/account/api/</a>' );
        }
        if ( ! defined( 'MAILCHIMP_API_ENDPOINT' ) ) {
            define( 'MAILCHIMP_API_ENDPOINT', 'https://' . $this->get_datacenter() . '.api.mailchimp.com/3.0' );
        }

        $this->sender_info = [
            'company'  => PEDESTAL_BLOG_NAME,
            'address1' => PEDESTAL_STREET_ADDRESS,
            'address2' => '',
            'city'     => PEDESTAL_CITY_NAME,
            'state'    => PEDESTAL_STATE,
            'zip'      => PEDESTAL_ZIPCODE,
            'country'  => 'US',
            'phone'    => '',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Requests
    |--------------------------------------------------------------------------
    */

    /**
     * Base method for all HTTP requests to the API
     *
     * @param  string $method       HTTP method to be performed
     * @param  string $endpoint     The API endpoint to send the request to
     * @param  array  $body_args    Request body arguments to be sent to the API
     * @param  array  $request_args Optional arguments to modify the request headers
     * @return object               A standardized response body
     */
    private function request( $method = 'GET', $endpoint = '/', $body_args = [], $request_args = [] ) {
        $default_request_args = [
            'method'  => strtoupper( $method ),
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'anystring:' . MAILCHIMP_API_KEY ),
            ],
        ];
        if ( ! empty( $body_args ) ) {
            $default_request_args['body'] = json_encode( $body_args );
        }
        $request_args = wp_parse_args( $request_args, $default_request_args );
        $url          = MAILCHIMP_API_ENDPOINT . $endpoint;
        $response     = wp_remote_request( $url, $request_args );
        $response     = Utils::handle_api_request_response( $response, 'json' );
        if ( $response['body'] ) {
            // Trim _links key from response
            if ( is_object( $response['body'] ) && isset( $response['body']->_links ) ) {
                unset( $response['body']->_links );
            }
            return $response['body'];
        }
        return $response;
    }

    /**
     * Perform a GET request to the MailChimp API
     *
     * NOTE: These requests are internally cached during a pageload
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/guides/get-started-with-mailchimp-api-3/#http-methods
     * @param  string $endpoint     The API endpoint to send the request to
     * @param  array  $args         Query string arguments added on to the endpoint
     * @param  array  $request_args Optional arguments to modify the request headers
     * @return object               A standardized response body
     */
    private function get_request( $endpoint = '/', $args = [], $request_args = [] ) {
        // Keep the order consistent for caching
        if ( ! empty( $args ) ) {
            ksort( $args );
            // If no callback is provided to array_filter() then
            // all the values of the array which are equal to FALSE
            // will be removed, such as an empty string or a NULL value.
            // MailChimp doesn't like empty query vars.
            $args     = array_filter( $args );
            $endpoint = add_query_arg( $args, $endpoint );
        }

        $cache_args = [
            'endpoint'     => $endpoint,
            'request_args' => ksort( $request_args ),
        ];
        $cache_key  = md5( json_encode( $cache_args ) );
        if ( ! empty( $this->get_request_cache[ $cache_key ] ) ) {
            return $this->get_request_cache[ $cache_key ];
        }

        $body_args                             = [];
        $resp                                  = $this->request( 'GET', $endpoint, $body_args, $request_args );
        $this->get_request_cache[ $cache_key ] = $resp;
        return $resp;
    }

    /**
     * Perform a POST request to the MailChimp API
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/guides/get-started-with-mailchimp-api-3/#http-methods
     * @param  string $endpoint     The API endpoint to send the request to
     * @param  array  $args         Body arguments included in the request
     * @param  array  $request_args Optional arguments to modify the request headers
     * @return object               A standardized response body
     */
    private function post_request( $endpoint = '/', $args = [], $request_args = [] ) {
        return $this->request( 'POST', $endpoint, $args, $request_args );
    }

    /**
     * Perform a PUT request to the MailChimp API
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/guides/get-started-with-mailchimp-api-3/#http-methods
     * @param  string $endpoint     The API endpoint to send the request to
     * @param  array  $args         Body arguments included in the request
     * @param  array  $request_args Optional arguments to modify the request headers
     * @return object               A standardized response body
     */
    private function put_request( $endpoint = '/', $args = [], $request_args = [] ) {
        return $this->request( 'PUT', $endpoint, $args, $request_args );
    }

    /**
     * Perform a DELETE request to the MailChimp API
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/guides/get-started-with-mailchimp-api-3/#http-methods
     * @param  string $endpoint     The API endpoint to send the request to
     * @param  array  $args         Body arguments included in the request
     * @param  array  $request_args Optional arguments to modify the request headers
     * @return object               A standardized response body
     */
    private function delete_request( $endpoint = '/', $args = [], $request_args = [] ) {
        return $this->request( 'DELETE', $endpoint, $args, $request_args );
    }

    /**
     * Perform a PATCH request to the MailChimp API
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/guides/get-started-with-mailchimp-api-3/#http-methods
     * @param  string $endpoint     The API endpoint to send the request to
     * @param  array  $args         Body arguments included in the request
     * @param  array  $request_args Optional arguments to modify the request headers
     * @return object               A standardized response body
     */
    private function patch_request( $endpoint = '/', $args = [], $request_args = [] ) {
        return $this->request( 'PATCH', $endpoint, $args, $request_args );
    }

    /*
    |--------------------------------------------------------------------------
    | Lists
    |--------------------------------------------------------------------------
    */

    /**
     * Get a single list from the API
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/
     * @param  string $list_id    List name or ID of the list to fetch
     * @param  array  $query_args Optional query args to be appended to the endpoint
     * @return object             An individual list object
     */
    public function get_list( $list_id = '', $query_args = [] ) {
        $list_id  = $this->sanitize_list_id( $list_id );
        $response = $this->get_request( "/lists/$list_id", $query_args );

        // Uh oh! Response is not found!
        if ( is_object( $response ) && isset( $response->status ) && '404' == $response->status ) {
            return false;
        }

        if ( isset( $response->lists[0] ) ) {
            return $response->lists[0];
        }
        return $response;
    }

    /**
     * Get several list at once
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/
     * @param  array  $args Arguments for filtering down the lists
     * @return array       Array of list objects
     */
    public function get_lists( $args = [] ) {
        $default_args = [
            'count'          => 100,
            'email'          => '',
            'fields'         => '',
            'exclude_fields' => '',
        ];
        $args         = wp_parse_args( $args, $default_args );
        return $this->get_request( '/lists', $args );
    }

    /**
     * Get this site's specific list
     *
     * Most of the time we will be dealing with the site's main list
     * If a site list doesn't exist one will be created
     *
     * @return object List object
     */
    public function get_site_list() {
        $option_name = 'mailchimp_site_list';
        $list        = get_option( $option_name );
        if ( $list ) {
            return $list;
        }
        $list = $this->get_list( PEDESTAL_BLOG_NAME );
        if ( ! $list ) {
            $args = [
                'name' => PEDESTAL_BLOG_NAME,
            ];
            $list = $this->add_list( $args );
        }
        $autoload = false;
        add_option( $option_name, $list, '', $autoload );
        return $list;
    }

    /**
     * Get the List ID of the site's main list
     *
     * @return string|false The site's main list id or false if not found
     */
    public function get_site_list_id() {
        $list = $this->get_site_list();
        if ( isset( $list->id ) ) {
            return $list->id;
        }
        return false;
    }

    /**
     * Get a list_id value from a list name, list id, or blank input value
     *
     * @param  string $maybe_list_id Name, ID, or blank value to get a list ID for
     * @return string|false          List ID or false if not found
     */
    public function sanitize_list_id( $maybe_list_id = '' ) {
        if ( ! $maybe_list_id ) {
            return $this->get_site_list_id();
        }
        $lists = $this->get_lists();
        foreach ( $lists->lists as $list ) {
            if ( ! $list || ! isset( $list->id ) ) {
                continue;
            }
            if ( $maybe_list_id == $list->id || $maybe_list_id == $list->name ) {
                return $list->id;
            }
        }

        return $maybe_list_id;
    }

    /**
     * Add a list to MailChimp
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/reference/lists/
     * @param array $args List arguments
     */
    public function add_list( $args = [] ) {
        $default_args = [
            'name'                => '',
            'contact'             => $this->sender_info,
            'permission_reminder' => 'You signed up for updates at ' . get_site_url(),
            'use_archive_bar'     => false,
            'email_type_option'   => false,
            'campaign_defaults'   => [
                'from_name'  => PEDESTAL_BLOG_NAME,
                'from_email' => PEDESTAL_EMAIL_NEWS,
                'subject'    => 'Update',
                'language'   => 'English',
            ],
            'visibility'          => 'pub',
        ];
        $args         = wp_parse_args( $args, $default_args );
        if ( empty( $args['name'] ) ) {
            return false;
        }
        $endpoint = '/lists';
        return $this->post_request( $endpoint, $args );
    }

    /**
     * Edit a list
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/reference/lists/
     * @param  string $list_id ID or name of list to edit
     * @param  array  $args    Arguments to update the list
     * @return object          HTTP response
     */
    public function edit_list( $list_id = '', $args = [] ) {
        $list_id = $this->sanitize_list_id( $list_id );
        return $this->patch_request( "/lists/$list_id", $args );
    }

    /**
     * Delete a list
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/reference/lists/
     * @param  string $list_id ID or name of list to delete
     * @return object          HTTP response
     */
    public function delete_list( $list_id = '' ) {
        if ( empty( $list_id ) ) {
            return false;
        }
        $list_id = $this->sanitize_list_id( $list_id );
        return $this->delete_request( "/lists/$list_id" );
    }

    /*
    |--------------------------------------------------------------------------
    | Contacts
    |--------------------------------------------------------------------------
    */

    /**
     * Generate MD5 hash of a lowercase version of the email address
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
     * @param  string $email Email address to be hashed
     * @return string        Hash
     */
    public function get_email_hash( $email = '' ) {
        $email = sanitize_email( $email );
        return md5( strtolower( $email ) );
    }

    /**
     * Subscribe a contact to a list
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
     * @param  string $email   Email address of the subscriber to add to the list
     * @param  array  $args    Arguments about the subscriber
     * @param  string $list_id List to add the subscriber to
     * @return object          HTTP response
     */
    public function subscribe_contact( $email = '', $args = [], $list_id = '' ) {
        if ( ! $email ) {
            return false;
        }
        $email   = sanitize_email( $email );
        $list_id = $this->sanitize_list_id( $list_id );
        if ( empty( $list_id ) ) {
            return false;
        }
        $member_hash  = $this->get_email_hash( $email );
        $endpoint     = "/lists/$list_id/members/$member_hash";
        $default_args = [
            'email_address' => $email,
            'email_type'    => 'html',
            'status'        => 'subscribed',
            'status_if_new' => 'subscribed',
            'ip_signup'     => $_SERVER['REMOTE_ADDR'],
            'interests'     => [],
        ];
        $args         = wp_parse_args( $args, $default_args );
        return $this->put_request( $endpoint, $args );
    }

    /**
     * Unsubscribe a contact from a list
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
     * @param  string $email   Email address of the subscriber to add to the list
     * @param  string $list_id List to add the subscriber to
     * @return object          HTTP response
     */
    public function unsubscribe_contact_from_list( $email = '', $list_id = '' ) {
        if ( ! $email ) {
            return false;
        }
        $email   = sanitize_email( $email );
        $list_id = $this->sanitize_list_id( $list_id );
        if ( empty( $list_id ) ) {
            return false;
        }
        $member_hash = $this->get_email_hash( $email );
        $endpoint    = "/lists/$list_id/members/$member_hash";
        return $this->delete_request( $endpoint );
    }

    /**
     * Get a contact from a list
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
     * @param  string $email   Email address of the subscriber to add to the list
     * @param  string $list_id List to add the subscriber to
     * @return object          HTTP response
     */
    public function get_contact( $email = '', $list_id = '' ) {
        if ( ! $email ) {
            return false;
        }
        $email   = sanitize_email( $email );
        $list_id = $this->sanitize_list_id( $list_id );
        if ( empty( $list_id ) ) {
            return false;
        }
        $member_hash = $this->get_email_hash( $email );
        $endpoint    = "/lists/$list_id/members/$member_hash";
        return $this->get_request( $endpoint );
    }

    /**
     * Get one or more contacts from a list
     *
     * @see https://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
     * @param  array  $args    Arguments to filter the list of members returned
     * @param  string $list_id List to get contacts from
     * @return object          HTTP response
     */
    public function get_list_contacts( $args = [], $list_id = '' ) {
        if ( ! is_array( $args ) || empty( $args ) ) {
            return false;
        }
        $list_id = $this->sanitize_list_id( $list_id );
        if ( empty( $list_id ) ) {
            return false;
        }
        $endpoint = "/lists/$list_id/members";
        return $this->get_request( $endpoint, $args );
    }

    /**
     * Base method for adding or removing a contact from a group within a list
     *
     * @see https://rudrastyh.com/mailchimp-api/interest-groups.html
     * @param  string $email   Email address of the subscriber to add to the group
     * @param  array  $args    Arguments about the groups
     * @return object          HTTP response
     */
    public function modify_contact_groups( $email = '', $args = [] ) {
        if ( ! $email ) {
            return false;
        }
        $default_args = [
            'groups'         => [],
            'group_category' => '',
            'list_id'        => '',
            'signup_source'  => '',
            'merge_fields'   => [],
            'add_to_groups'  => true, // Setting to false unsubscribes contact from group
        ];
        $args         = wp_parse_args( $args, $default_args );
        if ( ! is_array( $args['groups'] ) ) {
            $args['groups'] = [ $args['groups'] ];
        }
        if ( ! is_array( $args['merge_fields'] ) ) {
            $args['merge_fields'] = [];
        }
        $interests = [];
        foreach ( $args['groups'] as $group ) {
            $group = $this->get_group( $group, $args['group_category'], $args['list_id'] );
            if ( ! $this->is_valid_group( $group ) ) {
                continue;
            }
            $interests[ $group->id ] = $args['add_to_groups'];

            // get_group() will handle sanitizing the list_id so let's use that value
            if ( isset( $group->list_id ) ) {
                $list_id = $group->list_id;
            }
        }

        if ( empty( $interests ) ) {
            return false;
        }
        $subscribe_args = [
            'interests' => $interests,

        ];
        if ( ! empty( $args['signup_source'] ) ) {
            $args['merge_fields']['SIGNUP'] = $args['signup_source'];
        }
        if ( ! empty( $args['merge_fields'] ) ) {
            $subscribe_args['merge_fields'] = $args['merge_fields'];
        }
        return $this->subscribe_contact( $email, $subscribe_args, $list_id );
    }

    /**
     * Convenience method around modify_contact_groups() to add a contact to a group
     *
     * @param  string $email   Email address of the subscriber to add to the group
     * @param  array  $args    Arguments about the groups
     * @return object          HTTP response
     */
    public function add_contact_to_groups( $email = '', $args = [] ) {
        $default_args          = [
            'groups'         => [],
            'group_category' => '',
            'list_id'        => '',
            'signup_source'  => '',
        ];
        $args                  = wp_parse_args( $args, $default_args );
        $args['add_to_groups'] = true;
        return $this->modify_contact_groups( $email, $args );
    }

    /**
     * Convenience method around modify_contact_groups() to remove a contact to a group
     *
     * @param  string $email   Email address of the subscriber to remove from the group
     * @param  array  $args    Arguments about the groups
     * @return object          HTTP response
     */
    public function remove_contact_from_groups( $email = '', $args = [] ) {
        $default_args          = [
            'groups'         => [],
            'group_category' => '',
            'list_id'        => '',
        ];
        $args                  = wp_parse_args( $args, $default_args );
        $args['add_to_groups'] = false;
        return $this->modify_contact_groups( $email, $args );
    }

    /*
    |--------------------------------------------------------------------------
    | Group Categories
    |--------------------------------------------------------------------------
    |
    | All groups need to belong to a parent group category
    |
    */

    /**
     * Add a group category
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/
     * @param string $title   Title of the group category
     * @param array  $args    Arguments for the group category
     * @param string $list_id Optional list id
     * @return object|false   Group category object on success, false on failure
     */
    public function add_group_category( $title = '', $args = [], $list_id = '' ) {
        if ( ! $title ) {
            return false;
        }
        $list_id      = $this->sanitize_list_id( $list_id );
        $default_args = [
            'title' => $title,
            'type'  => 'checkboxes',
        ];
        $args         = wp_parse_args( $args, $default_args );
        $endpoint     = "/lists/$list_id/interest-categories";
        $resp         = $this->post_request( $endpoint, $args );
        if ( ! is_object( $resp ) ) {
            return false;
        }
        return $resp;
    }

    /**
     * Get a group category
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/
     * @param  string $group_category Name or ID of group category to get
     * @param  string $list_id        Optional list ID
     * @return object|false           Group object or false if not found
     */
    public function get_group_category( $group_category = '', $list_id = '' ) {
        $groups = $this->get_group_categories( $list_id );
        if ( empty( $groups ) || empty( $group_category ) ) {
            return false;
        }
        foreach ( $groups as $group ) {
            if ( $group_category == $group->id || $group_category == $group->title ) {
                return $group;
            }
        }
        return false;
    }

    /**
     * Get all group categories for a given list
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/
     * @param  string $list_id Optional list ID to get group categories
     * @return array           Array of Group Category objects
     */
    public function get_group_categories( $list_id = '' ) {
        $list_id = $this->sanitize_list_id( $list_id );

        $endpoint = "/lists/$list_id/interest-categories";
        $resp     = $this->get_request( $endpoint );
        $output   = [];
        if ( ! is_object( $resp ) || ! isset( $resp->categories ) || ! is_array( $resp->categories ) ) {
            return $output;
        }
        foreach ( $resp->categories as $cat ) {
            $output[] = $cat;
        }
        return $output;
    }

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    /**
     * Get all groups for a given group cateory and list
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/interests/
     * @param  string $group_category ID or name of group category to get all groups from
     * @param  string $list_id        Optional list id
     * @return array                 Array of group objects
     */
    public function get_groups( $group_category = '', $list_id = '' ) {
        $output  = [];
        $list_id = $this->sanitize_list_id( $list_id );
        if ( ! $list_id ) {
            return $output;
        }
        $group_category = $this->get_group_category( $group_category, $list_id );
        if ( ! $group_category || ! is_object( $group_category ) || empty( $group_category->id ) ) {
            return $output;
        }
        $group_category_id = $group_category->id;
        $endpoint          = "/lists/$list_id/interest-categories/$group_category_id/interests";
        $args              = [
            'count' => 60,
        ];
        $resp              = $this->get_request( $endpoint, $args );
        if ( ! is_object( $resp ) || ! isset( $resp->interests ) ) {
            return $output;
        }
        foreach ( $resp->interests as $interest ) {
            if ( is_object( $interest ) && isset( $interest->{'_links'} ) ) {
                unset( $interest->{'_links'} );
            }
            $output[] = $interest;
        }
        return $output;
    }

    /**
     * Get all of the groups for a list across group categories
     *
     * NOTE: We add the category_title property ourselves since MailChimp doesn't include it
     *
     * @param  string $list_id Optional list ID
     * @return array          Flat array of group objects
     */
    public function get_all_groups( $list_id = '' ) {
        $list_id    = $this->sanitize_list_id( $list_id );
        $categories = $this->get_group_categories( $list_id );
        $output     = [];
        foreach ( $categories as $cat ) {
            if ( ! is_object( $cat ) || ! isset( $cat->id ) ) {
                continue;
            }
            $groups = $this->get_groups( $cat->id, $list_id );
            foreach ( $groups as $group ) {
                $group->category_title = $cat->title;
            }
            $output = array_merge( $output, $groups );
        }
        // Remove the _links element
        foreach ( $output as $item ) {
            if ( isset( $item->_links ) ) {
                unset( $item->_links );
            }
        }
        return $output;
    }

    /**
     * Get a single group by name or id
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/interests/
     * @param  string $group_id       Name or ID of group to get
     * @param  string $group_category Group category the group is a part of
     * @param  string $list_id        Optional list ID
     * @return object|false           Group object if found otherwise false
     */
    public function get_group( $group_id = '', $group_category = '', $list_id = '' ) {
        $groups = $this->get_groups( $group_category, $list_id );
        foreach ( $groups as $group ) {
            if ( $group->id == $group_id ) {
                return $group;
            }
            if ( $group->name == $group_id ) {
                return $group;
            }
        }
        return false;
    }

    /**
     * Determines if a $group is valid or not
     *
     * @param  object  $group The group object ot validate
     * @return boolean        Whether the group is valid or not
     */
    public function is_valid_group( $group ) {
        if (
            ! is_object( $group )
            || ! isset( $group->list_id )
            || ! isset( $group->category_id )
            || ! isset( $group->id )
        ) {
            return false;
        }
        return true;
    }

    /**
     * Get the subscriber count for a given group
     *
     * @param  string $group          Name or ID of group to get
     * @param  string $group_category Group category the group is a part of
     * @param  string $list_id        Optional list ID
     * @return int|false              The count or false on failure
     */
    public function get_group_subscriber_count( $group = '', $group_category = '', $list_id = '' ) {
        $group = $this->get_group( $group, $group_category, $list_id );
        if ( ! is_object( $group ) || ! isset( $group->subscriber_count ) ) {
            return false;
        }
        return absint( $group->subscriber_count );
    }

    /**
     * Add a group to a given group category and list
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/interests/
     * @param  string $name           Name of the group to add
     * @param  string $group_category Name or ID of the group category the new group should be a part of
     * @param  string $list_id        Optional list id
     * @return object|false           HTTP response if successful otherwise false
     */
    public function add_group( $name = '', $group_category = '', $list_id = '' ) {
        if ( ! $name ) {
            return false;
        }

        $list_id = $this->sanitize_list_id( $list_id );
        if ( ! $list_id ) {
            return false;
        }

        $group_category = $this->get_group_category( $group_category, $list_id );
        if ( ! $group_category || ! is_object( $group_category ) || empty( $group_category->id ) ) {
            return false;
        }
        $group_category_id = $group_category->id;

        $endpoint = "/lists/$list_id/interest-categories/$group_category_id/interests";
        $args     = [
            'name' => $name,
        ];
        $resp     = $this->post_request( $endpoint, $args );
        if ( ! is_object( $resp ) ) {
            return false;
        }
        return $resp;
    }

    /**
     * Edit a given group's name
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/interests/
     * @param  string $name           New name of group
     * @param  string $group          Old name or ID of group to be renamed
     * @param  string $group_category Group category the group belongs to
     * @param  string $list_id        Optional list id
     * @return object|false           HTTP response if successful otherwise false
     */
    public function edit_group_name( $name = '', $group = '', $group_category = '', $list_id = '' ) {
        if ( ! $name ) {
            return false;
        }
        $group = $this->get_group( $group, $group_category, $list_id );
        if ( ! $this->is_valid_group( $group ) ) {
            return false;
        }

        $list_id     = $group->list_id;
        $category_id = $group->category_id;
        $group_id    = $group->id;

        $endpoint = "/lists/$list_id/interest-categories/$category_id/interests/$group_id";
        $args     = [
            'name' => $name,
        ];
        $resp     = $this->patch_request( $endpoint, $args );
        if ( ! is_object( $resp ) ) {
            return false;
        }
        return $resp;
    }

    /**
     * Delete a group from a group category
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/lists/interest-categories/interests/
     * @param  string $group          Name or ID of group to be deleted
     * @param  string $group_category Group category the group belongs to
     * @param  string $list_id        Optional list id
     * @return object|false           HTTP response if successful or false
     */
    public function delete_group( $group = '', $group_category = '', $list_id = '' ) {
        $group = $this->get_group( $group, $group_category, $list_id );
        if ( ! $this->is_valid_group( $group ) ) {
            return false;
        }

        $list_id     = $group->list_id;
        $category_id = $group->category_id;
        $group_id    = $group->id;

        $endpoint = "/lists/$list_id/interest-categories/$category_id/interests/$group_id";
        $resp     = $this->delete_request( $endpoint );
        return $resp;
    }

    /*
    |--------------------------------------------------------------------------
    | Campaign Folders
    |--------------------------------------------------------------------------
    */

    /**
     * Get all campaign folders
     *
     * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaign-folders/
     * @param  array  $args Args to modify the response
     * @return object       Campaign folders
     */
    public function get_campaign_folders( $args = [] ) {
        $defaults = [
            'count'  => 10,
            'offset' => 0,
        ];
        $args     = wp_parse_args( $args, $defaults );
        $endpoint = '/campaign-folders';
        $output   = $this->get_request( $endpoint, $args );

        // Remove the _links items
        if ( ! empty( $output->folders ) ) {
            foreach ( $output->folders as $key => $folder ) {
                if ( isset( $folder->_links ) ) {
                    unset( $output->folders[ $key ]->_links );
                }
            }
        }

        return $output;
    }

    /**
     * Get the details of a specific folder
     *
     * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaign-folders/
     * @param  string $folder_id ID of the folder
     * @return object            Folder details
     */
    public function get_campaign_folder( $folder_id ) {
        $endpoint = "/campaign-folders/$folder_id/";
        return $this->get_request( $endpoint );
    }

    /**
     * Get the id of a campaign folder given a name or create it if not found
     *
     * @param  string  $name                Name of the folder to search for
     * @param  boolean $create_if_not_found Whether to create a new folder if one doesn't already exist
     * @return string|false                 id of the campaign folder or false if fail
     */
    public function get_campaign_folder_id( $name, $create_if_not_found = true ) {
        $folders = $this->get_campaign_folders( [
            'count' => 99,
        ] );
        if ( ! empty( $folders->folders ) ) {
            foreach ( $folders->folders as $folder ) {
                if ( $name == $folder->name ) {
                    return $folder->id;
                }
            }
        }

        // Folder doesn't exist so maybe create it?
        if ( $create_if_not_found ) {
            $folder = $this->create_campaign_folder( $name );
            if ( ! empty( $folder->id ) ) {
                return $folder->id;
            }
        }

        return false;
    }

    /**
     * Create a campaign folder
     *
     * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaign-folders/
     * @param  string $name  Name of the folder
     * @return object        Folder details
     */
    public function create_campaign_folder( $name ) {
        $args     = [
            'name' => $name,
        ];
        $endpoint = '/campaign-folders';
        return $this->post_request( $endpoint, $args );
    }

    /**
     * Rename a campaign folder
     *
     * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaign-folders/
     * @param  string $folder_id ID of the folder
     * @param  string $new_name  New name for the folder
     * @return object            Folder details
     */
    public function rename_campaign_folder( $folder_id, $new_name ) {
        $args     = [
            'name' => $new_name,
        ];
        $endpoint = "/campaign-folders/$folder_id/";
        return $this->patch_request( $endpoint, $args );
    }

    /**
     * Delete a specific folder
     *
     * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaign-folders/
     * @param  string $folder_id ID of the folder to delete
     * @return string|object     Message if successful, object if there was a problem
     */
    public function delete_campaign_folder( $folder_id ) {
        $endpoint = "/campaign-folders/$folder_id/";
        return $this->delete_request( $endpoint );
    }

    /*
    |--------------------------------------------------------------------------
    | Campaigns
    |--------------------------------------------------------------------------
    */

    /**
     * Get campaigns
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/
     * @param  array  $args Campaign args
     * @return array        Array of campaign objects
     */
    public function get_campaigns( $args = [] ) {
        $defaults = [
            'list_id' => $this->get_site_list_id(),
            'status'  => 'sent',
        ];
        $args     = wp_parse_args( $args, $defaults );
        $endpoint = '/campaigns';
        $output   = $this->get_request( $endpoint, $args );
        if ( isset( $output->campaigns ) ) {
            return $output->campaigns;
        }
        return [];
    }

    /**
     * Get campaigns sent to a certain group
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/
     * @param  string $group          Group name or ID
     * @param  string $group_category Category the group belongs to
     * @param  array  $args           Args to send to get_campaigns()
     * @return array                  Array of campaign objects
     */
    public function get_campaigns_by_group( $group = '', $group_category = '', $args = [] ) {
        $campaigns = [];
        $group     = $this->get_group( $group, $group_category );
        if ( ! $group || ! isset( $group->id ) ) {
            return $campaigns;
        }
        $all_campaigns = $this->get_campaigns( $args );
        foreach ( $all_campaigns as $campaign ) {
            $in_group   = false;
            $conditions = $campaign->recipients->segment_opts->conditions;
            foreach ( $conditions as $condition ) {
                if ( is_array( $condition->value ) && in_array( $group->id, $condition->value ) ) {
                    $in_group = true;
                }
            }
            if ( $in_group ) {
                // Remove the _link element
                if ( isset( $campaign->{'_links'} ) ) {
                    unset( $campaign->{'_links'} );
                }
                $campaigns[] = $campaign;
            }
        }
        return $campaigns;
    }

    /**
     * Create a campaign
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/
     * @param  array  $args Campaign args
     * @return object       HTTP response
     */
    public function create_campaign( $args = [] ) {
        $endpoint = '/campaigns';
        return $this->post_request( $endpoint, $args );
    }

    /**
     * Delete a campaign
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/
     * @param  string $campaign_id ID of campaign to delete
     * @return object|false        HTTP response or false
     */
    public function delete_campaign( $campaign_id = '' ) {
        if ( empty( $campaign_id ) ) {
            return false;
        }
        $endpoint = "/campaigns/$campaign_id";
        return $this->delete_request( $endpoint );
    }

    /**
     * Create and send a campaign
     *
     * @param  array  $args Arguments for sending a campaign
     * @return string The id of the campaign sent
     */
    public function send_campaign( $args = [] ) {
        $defaults = [
            'type'             => 'regular',
            'message'          => '',
            'folder_name'      => 'Newsletters, daily',
            'list_id'          => '',
            'group_category'   => '',
            'groups'           => [],
            'subject_line'     => '',
            'preview_text'     => '',
            'title'            => '',
            'from_name'        => PEDESTAL_BLOG_NAME,
            'reply_to'         => PEDESTAL_EMAIL_CONTACT,
            'use_conversation' => false,
            'to_name'          => '',
            'authenticate'     => true,
            'auto_footer'      => false,
            'opens'            => true,
            'html_clicks'      => true,
            'text_clicks'      => true,
        ];
        $args     = wp_parse_args( $args, $defaults );

        // Sanitize/process values
        if ( empty( $args['message'] ) ) {
            return false;
        }

        $list_id = $this->sanitize_list_id( $args['list_id'] );
        if ( empty( $list_id ) ) {
            return false;
        }

        if ( ! is_array( $args['groups'] ) ) {
            $args['groups'] = [ $args['groups'] ];
        }

        $segment_opts = [];
        if ( ! empty( $args['groups'] ) && ! empty( $args['group_category'] ) ) {
            $group_ids = [];
            foreach ( $args['groups'] as $group ) {
                $group = $this->get_group( $group, $args['group_category'], $args['list_id'] );
                if ( ! $this->is_valid_group( $group ) ) {
                    continue;
                }

                $group_ids[] = $group->id;

                if ( isset( $group->category_id ) ) {
                    //
                    $group_category_id = $group->category_id;
                }

                if ( isset( $group->list_id ) ) {
                    $args['list_id'] = $group->list_id;
                }
            }

            if ( ! empty( $group_ids ) ) {
                $segment_opts = [
                    'match'      => 'any', // or 'all'
                    'conditions' => [
                        [
                            'condition_type' => 'Interests',
                            'op'             => 'interestcontains',
                            'field'          => 'interests-' . $group_category_id, // See https://stackoverflow.com/a/35810125/1119655
                            'value'          => $group_ids,
                        ],
                    ],
                ];
            }
        }

        // Turn our flat list of arguments into the specific grouping MailChimp expects
        $campaign_args = [
            'type'       => $args['type'],
            'recipients' => [
                'list_id' => $list_id,
            ],
            'settings'   => [
                'subject_line'     => $args['subject_line'],
                'preview_text'     => $args['preview_text'],
                'title'            => $args['title'],
                'from_name'        => $args['from_name'],
                'reply_to'         => $args['reply_to'],
                'use_conversation' => $args['use_conversation'],
                'to_name'          => $args['to_name'],
                'authenticate'     => $args['authenticate'],
                'auto_footer'      => $args['auto_footer'],
            ],
            // 'variate_settings' => [],
            'tracking'   => [
                'opens'       => $args['opens'],
                'html_clicks' => $args['html_clicks'],
                'text_clicks' => $args['text_clicks'],
            ],
        ];

        if ( ! empty( $args['folder_name'] ) ) {
            $folder_id = $this->get_campaign_folder_id( $args['folder_name'] );
            if ( $folder_id ) {
                $campaign_args['settings']['folder_id'] = $folder_id;
            }
        }

        if ( empty( $segment_opts ) ) {
            // No segment arguments were set and we don't want to send
            // the campaign to the entire list
            return false;
        }
        $campaign_args['recipients']['segment_opts'] = $segment_opts;
        $campaign                                    = $this->create_campaign( $campaign_args );
        if ( ! is_object( $campaign ) || ! isset( $campaign->id ) ) {
            // Something went wrong!
            return false;
        }
        $campaign_id      = $campaign->id;
        $message_args     = [
            'html' => $args['message'],
        ];
        $endpoint         = "/campaigns/$campaign_id/content";
        $campaign_message = $this->put_request( $endpoint, $message_args );

        // Send the campaign
        $endpoint = "/campaigns/$campaign_id/actions/send";
        $sent     = $this->post_request( $endpoint );
        return $campaign_id;
    }

    /**
     * Get a report of link clicks for a given campaign
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/reports/click-details/
     * @param  string $campaign_id Id of the campaign to get link stats for
     * @param  array  $args        Args to modify the response
     * @return array               Array of link objects
     */
    public function get_campaign_link_clicks( $campaign_id = '', $args = [] ) {
        $endpoint = "/reports/$campaign_id/click-details";
        $defaults = [
            'count' => 25,
        ];
        $args     = wp_parse_args( $args, $defaults );
        $data     = $this->get_request( $endpoint, $args );
        if ( isset( $data->urls_clicked ) ) {
            return $data->urls_clicked;
        }
        return [];
    }

    /**
     * Get a report of unsubscribes for a given campaign
     *
     * @see http://developer.mailchimp.com/documentation/mailchimp/reference/reports/unsubscribed/
     * @param  string $campaign_id Id of the campaign to get unsubscribe stats for
     * @param  array  $args        Args to modify the response
     * @return array               Array of subscriber objects
     */
    public function get_campaign_unsubscribes( $campaign_id = '', $args = [] ) {
        $endpoint = "/reports/$campaign_id/unsubscribed";
        $defaults = [
            'count' => 100,
        ];
        $args     = wp_parse_args( $args, $defaults );
        $data     = $this->get_request( $endpoint, $args );
        if ( isset( $data->unsubscribes ) ) {
            return $data->unsubscribes;
        }
        return [];
    }

    /**
     * Get the datacenter value from the MailChimp API key
     *
     * @return string The datacenter ID
     */
    public function get_datacenter() {
        $datacenter = explode( '-', MAILCHIMP_API_KEY );
        return $datacenter[1];
    }

    /**
     * Get a MailChimp admin URL
     *
     * @param  string $path Optional relative path to be appended to the end of the admin url
     * @return string       MailChimp admin url
     */
    public function get_admin_url( $path = '/' ) {
        $url = 'https://' . $this->get_datacenter() . '.admin.mailchimp.com/';
        if ( $path && is_string( $path ) ) {
            $url .= ltrim( $path, '/' );
        }
        return $url;
    }
}
