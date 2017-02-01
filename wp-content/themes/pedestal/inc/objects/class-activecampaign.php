<?php

namespace Pedestal\Objects;

/**
 * A way to interact with ActiveCampaign
 *
 * A couple of ActiveCampaign concepts to guide you:
 *  - Contacts are associated with one or more Lists
 *  - Messages are associated with one or more Campaigns
 *  - Campaigns get sent to Lists
 *
 * Two constants need to be added to wp-config.php
 * See https://spiritedmedia.activehosted.com/admin/main.php?action=settings#tab_api
 * define( 'ACTIVECAMPAIGN_URL', 'https://spiritedmedia.api-us1.com' );
 * define( 'ACTIVECAMPAIGN_API_KEY', '<the-api-key-goes here>' );
 */
class ActiveCampaign {

    /**
     * Default sender info
     *
     * @var array
     */
    protected $sender_info = [];

    /**
     * Check that we have the proper API credentials set up
     */
    public function __construct() {
        if ( ! defined( 'ACTIVECAMPAIGN_URL' ) || ! defined( 'ACTIVECAMPAIGN_API_KEY' ) ) {
            wp_die( 'ActiveCampaign API credentials are missing. See <a href="https://spiritedmedia.activehosted.com/admin/main.php?action=settings#tab_api">https://spiritedmedia.activehosted.com/admin/main.php?action=settings#tab_api</a>' );
        }

        $this->sender_info = [
            'sender_name'     => PEDESTAL_BLOG_NAME,
            'sender_addr1'    => PEDESTAL_STREET_ADDRESS,
            'sender_addr2'    => '',
            'sender_city'     => PEDESTAL_CITY_NAME,
            'sender_zip'      => PEDESTAL_ZIPCODE,
            'sender_country'  => 'United States',
            'sender_url'      => get_site_url(),
            'sender_reminder' => '',
        ];

        if ( ! empty( PEDESTAL_BUILDING_NAME ) ) {
            $this->sender_info['sender_addr1'] = PEDESTAL_BUILDING_NAME;
            $this->sender_info['sender_addr2'] = PEDESTAL_STREET_ADDRESS;
        }
    }

    /**
     * Perform a remote GET request to ActiveCampaign's API
     * @param  array $args       Parameters to append to the request URL
     * @param  string $endpoint  Optional endpoint that might need to be changed
     * @return array             A standardized response
     */
    private function get_request( $args = [], $endpoint = '/admin/api.php' ) {
        $default_args = [
            'api_key'    => ACTIVECAMPAIGN_API_KEY,
            'api_output' => 'json',
        ];
        $args = wp_parse_args( $args, $default_args );
        $url = ACTIVECAMPAIGN_URL . $endpoint;
        $request_url = add_query_arg( $args, $url );
        $response = wp_remote_get( $request_url, [] );
        return $this->handle_request_response( $response, $args['api_output'] );
    }

    /**
     * Perform a remote POST request to ActiveCampaign's API
     * @param  array $query_args  Parameters to append to the request URL
     * @param  array $body_args   Arguments to pass in the body of the request
     * @param  string $endpoint   Optional endpoint that might need to be changed
     * @return array              A standardized response
     */
    private function post_request( $query_args = [], $body_args = [], $endpoint = '/admin/api.php' ) {
        $default_query_args = [
            'api_key'    => ACTIVECAMPAIGN_API_KEY,
            'api_output' => 'json',
        ];
        $query_args = wp_parse_args( $query_args, $default_query_args );

        $default_body_args = [];
        $body_args = wp_parse_args( $body_args, $default_body_args );
        $url = ACTIVECAMPAIGN_URL . $endpoint;
        $request_url = add_query_arg( $query_args, $url );
        $args = [
            'headers' => [],
            'body'    => $body_args,
        ];
        $response = wp_remote_post( $request_url, $args );
        return $this->handle_request_response( $response, $query_args['api_output'] );
    }

    /**
     * Standardizes the response from remote requests to ActiveCampaign's API
     * @param  array|WP_Error $response  Response from wp_remote_*
     * @param  string $expected_format   Expected format of the response body (xml, json, or serialize)
     * @return array                     Details from the response
     */
    private function handle_request_response( $response = [], $expected_format = 'json' ) {
        if ( is_wp_error( $response ) ) {
            return [
                'code'    => 0,
                'body'    => $response->get_error_message(),
                'success' => false,
            ];
        }

        if (
               ! isset( $response['response'] )
            || ! isset( $response['response']['code'] )
            || ! isset( $response['body'] )
        ) {
            return [
                'code'    => 0,
                'body'    => 'No response code or response body set',
                'success' => false,
            ];
        }
        $response_code = $response['response']['code'];
        $response_body = $response['body'];
        $success = false;
        if ( 200 == $response_code ) {
            $success = true;
        }
        switch ( strtolower( $expected_format ) ) {
            case 'json':
                $response_body = json_decode( $response_body );
                break;
            case 'serialize':
                $response_body = unserialize( $response_body );
                break;
        }
        if ( ! is_object( $response_body ) ) {
            // Everything returned by ActiveCampaign's API happens to be an object
            return [
                'code'    => 0,
                'body'    => 'Response body is not an object',
                'success' => false,
            ];

        }
        if ( $success ) {
            $result = [
                'code'    => $response_code,
                'body'    => $response_body,
                'success' => $success,
            ];
            return $result;
        }
        return $response;
    }

    /**
     * Add a contact to ActiveCampaign
     * @param array $fields  Details of the contact to add
     * @see   http://www.activecampaign.com/api/example.php?call=contact_add
     * @return object        Object representing the contact
     */
    public function add_contact( $fields = [] ) {
        if ( ! isset( $fields['email'] ) || empty( $fields['email'] ) ) {
            return false;
        }
        $default_fields = [
            'email'      => '',
            'first_name' => '',
            'last_name'  => '',
            'phone'      => '',
            'orgname'    => '',
            'tags'       => '',
        ];
        $fields = wp_parse_args( $fields, $default_fields );
        $query_args = [
            'api_action' => 'contact_add',
        ];
        $response = $this->post_request( $query_args, $fields );
        $payload = $response['body'];
        if ( isset( $payload->{'0'} ) ) {
            // Return an object representing the contact
            return $payload->{'0'};
        }

        return $payload;
    }

    /**
     * Add multiple contacts in one call
     * @param array $contacts   An array of contact details
     * @see   http://www.activecampaign.com/api/example.php?call=contact_add
     * @return array $contacts  An array of contact objects
     */
    public function add_contacts( $contacts = [] ) {
        $output = [];
        if ( ! is_array( $contacts ) ) {
            return $output;
        }
        foreach ( $contacts as $contact ) {
            if ( empty( $contact['email'] ) ) {
                continue;
            }
            $added = $this->add_contact( $contact );
            $output[ $contact['email'] ] = $added;
        }

        return $output;
    }

    /**
     * Add a contact to one or more lists
     * @param  string $email    Email address of the contact to subscribe
     * @param  array $list_ids  One or more list IDs
     * @return boolean          true or response payload
     */
    public function subscribe_contact( $email = '', $list_ids = [] ) {
        // If no email is found, then bail!
        if ( ! $email ) {
            return false;
        }

        $list_ids = $this->sanitize_list_ids( $list_ids );

        $body_args = [];
        foreach ( $list_ids as $list_id ) {
            $list_key = 'p[' . $list_id . ']';
            $body_args[ $list_key ] = $list_id;

            $status_key = 'status[' . $list_id . ']';
            $status = 1; // 1 = Active/subscribed, 0 = inactive/unsubscribed
            $body_args[ $status_key ] = $status;
        }

        if ( ! $body_args ) {
            return false;
        }

        $body_args['email'] = $email;

        $query_args = [
            'api_action' => 'contact_sync',
        ];
        $response = $this->post_request( $query_args, $body_args );
        $payload = $response['body'];
        if ( 1 === $payload->result_code ) {
            return true;
        }

        return $payload;
    }

    /**
     * Unsubscribe a contact from one or more lists
     * @param  string $email    Email address of the contact to subscribe
     * @param  array $list_ids  One or more list IDs
     * @see    http://www.activecampaign.com/api/example.php?call=contact_edit
     * @return boolean          true or response payload
     */
    public function unsubscribe_contact( $email = '', $list_ids = [] ) {
        $contact = $this->get_contact( $email );
        // If no contact is found, then bail!
        if ( ! $contact || ! isset( $contact->email ) || ! isset( $contact->id ) ) {
            return false;
        }

        $list_ids = $this->sanitize_list_ids( $list_ids );

        $args = [];
        foreach ( $list_ids as $list_id ) {
            $list_key = 'p[' . $list_id . ']';
            $args[ $list_key ] = $list_id;

            $status_key = 'status[' . $list_id . ']';
            $args[ $status_key ] = 2; // 2 = Unsubscribe
        }

        if ( ! $args ) {
            return false;
        }

        $args['email'] = $contact->email;
        $args['id'] = $contact->id;

        $query_args = [
            'api_action' => 'contact_edit',
            'overwrite'  => 0, // only update included post parameters, don't overwrite
        ];
        $response = $this->post_request( $query_args, $args );
        $payload = $response['body'];
        if ( 1 === $payload->result_code ) {
            return true;
        }

        return $payload;
    }

    /**
     * Get details about a contact
     * @param  mixed $value  Some type of identifier to get the contact
     * @return object|false  Details about the contact
     */
    public function get_contact( $value = 0 ) {
        // Looks like we received a user object already, pass it back.
        if ( is_object( $value ) && isset( $value->id ) && isset( $value->email ) ) {
            return $value;
        }

        $args = [
            'email'      => $value,
            'api_action' => 'contact_view_email',
        ];
        if ( is_numeric( $value ) ) {
            $args['id'] = $id;
            $args['api_action'] = 'contact_view';
        }
        $response = $this->get_request( $args );
        $payload = $response['body'];

        if ( 0 === $payload->result_code ) {
            return false;
        }
        // Remove some extra properties we don't need
        unset( $payload->result_code );
        unset( $payload->result_message );
        unset( $payload->result_output );
        return $payload;
    }

    /**
     * Get the lists the contact is subscribed to
     * @param  mixed $contact_token  An identifier to get a contact from
     * @param  string $filter        Whether to show all, subscribed, or unsubscribed
     * @return object|false          A collection of list objects or false on failure
     */
    public function get_contact_lists( $contact_token = '', $filter = 'all' ) {
        $contact = $this->get_contact( $contact_token );
        if ( ! $contact || ! isset( $contact->lists ) ) {
            return false;
        }

        $filter = strtolower( $filter );
        $allowed_filters = [ 'all', 'subscribed', 'unsubscribed' ];
        if ( ! in_array( $filter, $allowed_filters ) ) {
            return false;
        }

        $allowed_statuses = [ '1', '2' ];
        if ( 'subscribed' === $filter ) {
            $allowed_statuses = [ '1' ];
        }
        if ( 'unsubscribed' === $filter ) {
            $allowed_statuses = [ '2' ];
        }
        $output = [];
        foreach ( $contact->lists as $key => $list ) {
            if ( in_array( $list->status, $allowed_statuses ) ) {
                $output[ $key ] = $list;
            }
        }
        return (object) $output;
    }

    /**
     * Get details about lists
     * @param  array $args  Array of arguments
     * @see    http://www.activecampaign.com/api/example.php?call=list_list
     * @return object       List details
     */
    public function get_lists( $args = [] ) {
        $default_args = [
            'ids'           => 'all',
            'global_fields' => 0,
            'full'          => 0,
        ];
        $args = wp_parse_args( $args, $default_args );
        if ( is_array( $args['ids'] ) ) {
            $ids = array_map( 'trim', $args['ids'] );
            $args['ids'] = implode( ',', $ids );
        }
        $args['api_action'] = 'list_list';
        $response = $this->get_request( $args );
        $payload = $response['body'];

        // Remove some extra properties we don't need
        unset( $payload->result_code );
        unset( $payload->result_message );
        unset( $payload->result_output );
        return $payload;
    }

    /**
     * Given one or more list identifiers make sure we have an array of list ids
     * @param  mixed $list_ids  List ids or names
     * @return array            Sanitized list ids
     */
    public function sanitize_list_ids( $list_ids ) {
        // Make sure $list_ids is an array
        if ( ! is_array( $list_ids ) ) {
            $list_ids = [ $list_ids ];
        }
        // Weed out any list names
        foreach ( $list_ids as $index => $maybe_list_id ) {
            // If $maybe_list_id is numeric then we know it's not a list name
            if ( is_numeric( $maybe_list_id ) ) {
                continue;
            }
            // Looks like $maybe_list_id is actually the name of a list
            // Let's fetch the list by name and see if we can get an ID
            $new_list = $this->get_list( $maybe_list_id );
            if ( ! $new_list || ! isset( $new_list->id ) ) {
                // No idea what this list is so let's remove it
                unset( $lists[ $index ] );
                continue;
            }
            // We have a proper list_id now. Hooray!
            $list_ids[ $index ] = intval( $new_list->id );
        }

        return $list_ids;
    }

    /**
     * Get a list object by name
     * @param  string $list_name  The name of the list to get
     * @return object|false       An object representing the list
     */
    public function get_list_by_name( $list_name = '' ) {
        $args = [
            'filters[name]' => $list_name,
        ];
        $lists = $this->get_lists( $args );
        if ( ! is_object( $lists ) || ! isset( $lists->{'0'} ) ) {
            return false;
        }
        $the_list = $lists->{'0'};
        return $the_list;
    }

    /**
     * Get a list object
     * @param  mixed $id     ID or Name of list to get
     * @return object|false  An object representing the list
     */
    public function get_list( $id ) {
        if ( is_numeric( $id ) ) {
            $args = [
                'ids' => $id,
            ];
            $lists = $this->get_lists( $args );
            if ( ! is_object( $lists ) || ! isset( $lists->{'0'} ) ) {
                return false;
            }
            return $lists->{'0'};
        }

        return $this->get_list_by_name( $id );
    }

    /**
     * Edit an existing list
     *
     * @see http://www.activecampaign.com/api/example.php?call=list_edit
     * @param  int|string $id        Numeric list ID
     * @param  array      $body_args Options to edit
     * @return object An object from the API response
     */
    public function edit_list( $id, $body_args = [] ) {
        if ( ! is_numeric( $id ) ) {
            return false;
        }
        $default_body_args = [
            'id'                    => $id,
            'name'                  => '',
            'subscription_notify'   => '',
            'unsubscription_notify' => '',
            'to_name'               => 'Recipient',
            'carboncopy'            => '',
        ];
        $default_body_args += $this->sender_info;
        $body_args = wp_parse_args( $body_args, $default_body_args );
        $query_args = [
            'api_action' => 'list_edit',
        ];
        $response = $this->post_request( $query_args, $body_args );
        $payload = $response['body'];
        return $payload;
    }

    /**
     * Create a new list
     * @param array $body_args  Options for the new list
     * @see   http://www.activecampaign.com/api/example.php?call=list_add
     * @return object           An object from the API response
     */
    public function add_list( $body_args = [] ) {
        $default_body_args = [
            'name'                  => '',
            'subscription_notify'   => '',
            'unsubscription_notify' => '',
            'to_name'               => 'Recipient',
            'require_name'          => '0',
            'private'               => '1',
            'carboncopy'            => '',
            'stringid'              => '',
        ];
        $default_body_args += $this->sender_info;
        $body_args = wp_parse_args( $body_args, $default_body_args );
        if ( $body_args['name'] && ! $body_args['stringid'] ) {
            $body_args['stringid'] = sanitize_title( $body_args['name'] );
        }
        $query_args = [
            'api_action' => 'list_add',
        ];
        $response = $this->post_request( $query_args, $body_args );
        $payload = $response['body'];
        return $payload;
    }

    /**
     * Delete a list
     * @param  integer|string $list_id An identifer for the list to be deleted
     * @see    http://www.activecampaign.com/api/example.php?call=list_delete
     * @return object                  Details about the API request
     */
    public function delete_list( $list_id = 0 ) {
        $list_ids = $this->sanitize_list_ids( $list_id );
        $list_ids_str = implode( ',', $list_ids );
        $args = [
            'api_action' => 'list_delete',
            'id'         => intval( $list_ids_str ),
        ];
        $response = $this->get_request( $args );
        $payload = $response['body'];
        return $payload;

    }

    /**
     * Add a message to ActiveCampaign
     * @param array $body_args  Message options
     * @see   http://www.activecampaign.com/api/example.php?call=message_add
     * @return object           Details about the API request
     */
    public function add_message( $body_args = [] ) {
        $query_args = [
            'api_action' => 'message_add',
        ];
        $default_body_args = [
            'format'          => 'html',
            'subject'         => '',
            'fromemail'       => PEDESTAL_EMAIL_NEWS,
            'fromname'        => PEDESTAL_BLOG_NAME,
            'reply2'          => PEDESTAL_EMAIL_CONTACT,
            'priority'        => 3,
            'charset'         => 'utf-8',
            'encoding'        => 'quoted-printable',
            'htmlconstructor' => 'editor',
            'html'            => '',
        ];
        $body_args = wp_parse_args( $body_args, $default_body_args );
        if ( ! $body_args['html'] || ! $body_args['subject'] ) {
            return false;
        }
        $response = $this->post_request( $query_args, $body_args );
        $payload = $response['body'];
        return $payload;
    }

    /**
     * Delete one or more messages from ActiveCampaign
     * @param  integer $message_id ID of the message to delete
     * @see    http://www.activecampaign.com/api/example.php?call=message_delete_list
     * @return object              Details from the API request
     */
    public function delete_message( $message_id = 0 ) {
        if ( ! $message_id || empty( $message_id ) ) {
            return;
        }

        if ( is_array( $message_id ) ) {
            array_map( 'trim', $message_id );
            $campaign_id = implode( ',', $message_id );
        }

        $args = [
            'api_action' => 'message_delete_list',
            'ids' => $message_id,
        ];
        $response = $this->get_request( $args );
        $payload = $response['body'];
        return $payload;
    }

    /**
     * Send a campaign to a given list of contacts
     * Adds a message, verifies the list, sends the email out
     * @param  array $args   Options for sending the campaign
     * @return object|false  Response from the API request
     */
    public function send_campaign( $args = [] ) {
        $default_args = [
            'name'    => current_time( 'F j, Y g:i a' ),
            'html'    => '',
            'subject' => '',
            'status'  => 1,
            'list'    => '',
        ];
        $args = wp_parse_args( $args, $default_args );
        if ( ! $args['html'] || ! $args['subject'] ) {
            return false;
        }
        $list = $this->get_list( $args['list'] );
        if ( ! $list || ! isset( $list->id ) ) {
            return false;
        }

        $message_body_args = [
            'html'                 => $args['html'],
            'subject'              => $args['subject'],
            'p[' . $list->id . ']' => $list->id,
        ];
        $message_response = $this->add_message( $message_body_args );
        if ( ! $message_response ) {
            return false;
        }
        $message_id = $message_response->id;
        // See http://www.activecampaign.com/api/example.php?call=campaign_create
        $body_args = [
            'type'                   => 'single',
            'name'                   => $args['name'],
            'status'                 => $args['status'],
            'public'                 => 0,
            'tracklinks'             => 'all',
            'htmlunsub'              => 0,
            'textunsub'              => 1, // Text Unsubscribe link?
            'm[' . $message_id . ']' => 100,
            'p[' . $list->id . ']'   => $list->id, // Which list will we send the campaign to?
        ];

        if ( isset( $args['send_date'] ) ) {
            $body_args['sdate'] = $args['send_date'];
        }

        $query_args = [
            'api_action' => 'campaign_create',
        ];
        $response = $this->post_request( $query_args, $body_args );
        $payload = $response['body'];
        $payload->messageid = $message_id;
        return $payload;
    }

    /**
     * Delete one or more campaigns from ActiveCampaign
     * @param  integer $campaign_id ID or IDs of campaigns to delete
     * @see    http://www.activecampaign.com/api/example.php?call=campaign_delete
     * @return object  Response from API request
     */
    public function delete_campaign( $campaign_id = 0 ) {
        if ( ! $campaign_id || empty( $campaign_id ) ) {
            return;
        }

        if ( is_array( $campaign_id ) ) {
            array_map( 'trim', $campaign_id );
            $campaign_id = implode( ',', $campaign_id );
        }

        $args = [
            'api_action' => 'campaign_delete_list',
            'ids'        => $campaign_id,
        ];
        $response = $this->get_request( $args );
        $payload = $response['body'];
        return $payload;
    }
}
