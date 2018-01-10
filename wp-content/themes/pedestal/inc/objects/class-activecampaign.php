<?php

namespace Pedestal\Objects;

use Pedestal\Utils\Utils;

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
     * The option key for storing the address ID specific to this site
     */
    private $address_option_key = 'activecampaign-address-id';

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
     * Get the name of the option where the address option is stored
     *
     * @return string The option name
     */
    public function get_address_option_key() {
        return $this->address_option_key;
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
        $response = wp_remote_get( $request_url, [
            'timeout' => 15,
        ] );
        return Utils::handle_api_request_response( $response, $args['api_output'] );
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
        return Utils::handle_api_request_response( $response, $query_args['api_output'] );
    }

    /**
     * Add a contact to ActiveCampaign
     * @see   http://www.activecampaign.com/api/example.php?call=contact_add
     * @param array $fields  Details of the contact to add
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
     * @see   http://www.activecampaign.com/api/example.php?call=contact_add
     * @param array $contacts   An array of contact details
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
     * @see    http://www.activecampaign.com/api/example.php?call=contact_edit
     * @param  string $email    Email address of the contact to subscribe
     * @param  array $list_ids  One or more list IDs
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
        $payload = $this->cleanup_response( $payload );
        return $payload;
    }

    /**
     * Get multiple contacts
     * @see    http://www.activecampaign.com/api/example.php?call=contact_list
     * @param  array $args  Array of arguments
     * @return object       Contact details
     */
    public function get_contacts( $args = [] ) {
        $default_args = [
            'full' => 0,
        ];
        $args = wp_parse_args( $args, $default_args );
        if ( isset( $args['ids'] ) && is_array( $args['ids'] ) ) {
            $ids = array_map( 'trim', $args['ids'] );
            $args['ids'] = implode( ',', $ids );
        }
        $args['api_action'] = 'contact_list';
        $response = $this->get_request( $args );
        $payload = $this->cleanup_response( $response['body'] );
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
     * @see    http://www.activecampaign.com/api/example.php?call=list_list
     * @param  array $args  Array of arguments
     * @return object       List details
     */
    public function get_lists( $args = [] ) {
        $default_args = [
            'ids'           => 'all',
            'global_fields' => 0,
            'full'          => 0,
        ];
        $args = wp_parse_args( $args, $default_args );
        if ( isset( $args['ids'] ) && is_array( $args['ids'] ) ) {
            $ids = array_map( 'trim', $args['ids'] );
            $args['ids'] = implode( ',', $ids );
        }
        $args['api_action'] = 'list_list';
        $response = $this->get_request( $args );
        $payload = $this->cleanup_response( $response['body'] );
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
     * @see   http://www.activecampaign.com/api/example.php?call=list_add
     * @param array $body_args  Options for the new list
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
     * @see    http://www.activecampaign.com/api/example.php?call=list_delete
     * @param  integer|string $list_id An identifer for the list to be deleted
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
     * @see   http://www.activecampaign.com/api/example.php?call=message_add
     * @param array $body_args  Message options
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
     * @see    http://www.activecampaign.com/api/example.php?call=message_delete_list
     * @param  integer $message_id ID of the message to delete
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
     * Get details about a campaign
     * @see http://www.activecampaign.com/api/example.php?call=campaign_list
     * @param array $args  Arguments about camapigns to fetch
     * @return object      Details about the API request
     */
    public function get_campaign( $args = [] ) {
        $default_args = [
            'ids'  => '',
            'full' => 1,
        ];
        $args = wp_parse_args( $args, $default_args );
        if ( is_array( $args['ids'] ) ) {
            $ids = array_map( 'trim', $args['ids'] );
            $args['ids'] = implode( ',', $ids );
        }
        $args['api_action'] = 'campaign_list';
        $response = $this->get_request( $args );
        $payload = $this->cleanup_response( $response['body'] );
        return $payload;
    }

    /**
     * Get several campaigns from one or more lists between a date range.
     *
     * The only way to do this in ActiveCampaign is to fetch a bunch of campaigns and filter them as needed.
     *
     * Default is campaigns that were sent in the last 7 days.
     *
     * @param  array  $args Arguments to filter the results
     * @return array        Array of campaign objects
     */
    public function get_camapigns( $args = [] ) {
        $default_args = [
            'lists'          => [ 0 ], // One or more list IDs or names
            'filter_by_name' => '',
            'start_date'     => strtotime( 'now' ),
            'end_date'       => strtotime( '7daysago' ),
            'max_pages'      => 10, // The maximum number of requests to ActiveCampaign to perform
        ];
        $args = wp_parse_args( $args, $default_args );

        // Make sure we have an array of IDs, not names
        $args['list_ids'] = $this->sanitize_list_ids( $args['lists'] );
        $args['max_pages'] = intval( $args['max_pages'] );
        if ( ! is_numeric( $args['start_date'] ) ) {
            $args['start_date'] = strtotime( $args['start_date'] );
        }
        if ( ! is_numeric( $args['end_date'] ) ) {
            $args['end_date'] = strtotime( $args['end_date'] );
        }
        $start_date = intval( $args['start_date'] );
        $end_date = intval( $args['end_date'] );

        $campaign_args = [
            'ids' => 'all',
            'filters[sdate_since_datetime]' => date( 'Y-m-d H:i:s', $end_date ),
            'page' => 1,
        ];

        if ( ! empty( $args['filter_by_name'] ) ) {
            // Perform a LIKE search on the campaign names to filter and reduce the number of campaigns to sift through
            $campaign_args['filters[name]'] = $args['filter_by_name'];
        }

        $keep_looping = true;
        $messages = [];
        while ( $keep_looping ) {
            $campaigns = $this->get_campaign( $campaign_args );
            foreach ( $campaigns as $campaign ) {
                $campaign_list_ids = [];
                foreach ( $campaign->lists as $item ) {
                    $campaign_list_ids[] = intval( $item->id );
                }

                // If the campaign ID isn't in the list of list IDs then skip it
                $matching_list_ids = array_intersect( $args['list_ids'], $campaign_list_ids );
                if ( empty( $matching_list_ids ) ) {
                    continue;
                }

                // If the camapign sent date is after the given start date then skip it
                $campaign_sent_date = strtotime( $campaign->sdate );
                if ( $campaign_sent_date > $start_date ) {
                    continue;
                }

                // Get all of the message IDs for this campaign
                $campaign_message_ids = [];
                foreach ( $campaign->messages as $item ) {
                    $campaign_message_ids[] = intval( $item->id );
                }

                $messages[] = (object) [
                    'id'             => $campaign->id,
                    'list_ids'       => $campaign_list_ids,
                    'message_ids'    => $campaign_message_ids,
                    'name'           => $campaign->name,
                    'unsubscribes'   => intval( $campaign->unsubscribes ),
                    'sent_to'        => intval( $campaign->total_amt ),
                    'opens'          => intval( $campaign->opens ),
                    'unique_opens'   => intval( $campaign->uniqueopens ),
                    'clicks'         => intval( $campaign->linkclicks ),
                    'unique_clicks'  => intval( $campaign->uniquelinkclicks ),
                    'sent_date'      => $campaign->sdate,
                    'last_open_date' => $campaign->ldate,
                ];
                if ( $campaign_sent_date < $end_date ) {
                    $keep_looping = false;
                }
            }
            $campaign_args['page']++;
            if ( $campaign_args['page'] > $args['max_pages'] ) {
                $keep_looping = false;
            }
        }
        return $messages;
    }

    /**
     * Send a campaign to a given list of contacts
     * Adds a message, verifies the list, sends the email out
     * @param  array $args   Options for sending the campaign
     * @return object|false  Response from the API request
     */
    public function send_campaign( $args = [] ) {
        $default_args = [
            'name'     => current_time( 'F j, Y g:i a' ),
            'messages' => [],
            'status'   => 1,
            'list'     => '',
        ];
        $args = wp_parse_args( $args, $default_args );
        if ( ! $args['messages'] || empty( $args['messages'] ) ) {
            return false;
        }
        $list = $this->get_list( $args['list'] );
        if ( ! $list || ! isset( $list->id ) ) {
            return false;
        }

        $message_ids = [];
        foreach ( $args['messages'] as $message ) {
            $message_body_args = [
                'html'                 => $message['html'],
                'subject'              => $message['subject'],
                'p[' . $list->id . ']' => $list->id,
            ];
            $message_response = $this->add_message( $message_body_args );
            if ( ! $message_response ) {
                continue;
            }
            $message_ids[] = $message_response->id;
        }
        // Without any message IDs we can't send the campaign
        if ( empty( $message_ids ) ) {
            return false;
        }

        // See http://www.activecampaign.com/api/example.php?call=campaign_create
        $body_args = [
            'type'                   => 'single',
            'name'                   => $args['name'],
            'status'                 => $args['status'],
            'public'                 => 0,
            'tracklinks'             => 'all',
            'htmlunsub'              => 0,
            'textunsub'              => 1, // Text Unsubscribe link?
            'p[' . $list->id . ']'   => $list->id, // Which list will we send the campaign to?
            'addressid'              => $this->get_address_id(),
        ];

        // Add messages to the camapign
        foreach ( $message_ids as $message_id ) {
            $key = 'm[' . $message_id . ']';
            $body_args[ $key ] = 100;
        }

        // Setup split testing arguments if more than 1 message in campaign
        if ( 1 < count( $message_ids ) ) {
            $body_args['type'] = 'split';
            $body_args['split_type'] = 'even'; // Send each message to even number of contacts
            $body_args['split_content'] = 1;
            // $body_args['status'] = 0; // Split test campaigns need to be set to draft and sent from ActiveCampaign
        }

        if ( isset( $args['send_date'] ) ) {
            $body_args['sdate'] = $args['send_date'];
        }

        $query_args = [
            'api_action' => 'campaign_create',
        ];
        $response = $this->post_request( $query_args, $body_args );
        $payload = $response['body'];
        $payload->message_ids = $message_ids;
        return $payload;
    }

    /**
     * Delete one or more campaigns from ActiveCampaign
     * @see    http://www.activecampaign.com/api/example.php?call=campaign_delete
     * @param  integer $campaign_id ID or IDs of campaigns to delete
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

    /**
     * Get details about addresses
     * @see    http://www.activecampaign.com/api/example.php?call=address_list
     * @param  array $args  Array of arguments
     * @return object       Address details
     */
    public function get_addresses( $args = [] ) {
        // @TODO The API returns 20 addresses per page so at some point in the
        // future we'll need to account for this if we store more than 20 addresses

        $default_args = [
            'sort'           => 'id',
            'sort_direction' => 'ASC',
            'page'           => '',
        ];
        $args = wp_parse_args( $args, $default_args );
        $args['api_action'] = 'address_list';
        $response = $this->get_request( $args );
        $payload = $this->cleanup_response( $response['body'] );
        return $payload;
    }

    /**
     * Get the ActiveCampaign address ID from an option in the database
     * or fall back to an API request
     *
     * @return integer  ID of the address or 0 if not found
     */
    public function get_address_id() {
        // Check if the ID was previously saved
        $id = get_option( $this->get_address_option_key() );
        if ( $id ) {
            return $id;
        }

        // Check the API for the address ID
        $addresses = $this->get_addresses();
        foreach ( $addresses as $address ) {
            if (
                ! is_object( $address ) ||
                ! isset( $address->zip ) ||
                ! isset( $address->id )
            ) {
                continue;
            }
            // If the zip code matches then we're good!
            if ( PEDESTAL_ZIPCODE == $address->zip ) {
                $id = absint( $address->id );
                update_option( $this->get_address_option_key(), $id );
                return $id;
            }
        }

        // Exhausted all options, 0 tells ActiveCampaign to use the default address
        return 0;
    }

    /**
     * Get a list of stats for links in a message
     *
     * @see http://www.activecampaign.com/api/example.php?call=campaign_report_link_list
     * @param  integer $campaign_id The id of the campaign that the message was part of
     * @param  integer $message_id  The id of the message to get links for
     * @return Array                Array of objects including URL, unique clicks, total clicks
     */
    public function get_links_report( $campaign_id = 0, $message_id = 0 ) {
        if ( ! $campaign_id || ! $message_id ) {
            return [];
        }
        $output = [];
        $args = [
            'campaignid' => $campaign_id,
            'messageid' => $message_id,
        ];
        $args['api_action'] = 'campaign_report_link_list';
        $response = $this->get_request( $args );
        $links = $this->cleanup_response( $response['body'] );
        foreach ( $links as $link ) {
            $output[] = (object) [
                'url'           => $link->link,
                'unique_clicks' => intval( $link->a_unique ),
                'total_clicks'  => intval( $link->a_total ),
            ];
        }
        $output = Utils::sort_obj_array_by_prop( $output, 'unique_clicks' );
        return array_reverse( $output );
    }

    /**
     * Remove unwanted properties from an ActiveCampaign response object
     *
     * @param Object $payload ActiveCampaign response object to be cleaned up
     * @return Object   The cleaned up object
     */
    public function cleanup_response( $payload = '' ) {
        $properties_to_remove = [
            'result_code',
            'result_message',
            'result_output',
        ];
        if ( is_object( $payload ) ) {
            foreach ( $properties_to_remove as $prop ) {
                unset( $payload->$prop );
            }
        }
        return $payload;
    }

    /**
     * Remove '- <blog name>' from list name which we do to make them unique within ActiveCampaign
     *
     * @param  string $haystack  String to scrub
     * @return string            Scrubbed list name
     */
    public function scrub_list_name( $haystack = '' ) {
        $needle = '- ' . PEDESTAL_BLOG_NAME;
        $parts = explode( $needle, $haystack );
        return trim( $parts[0] );
    }
}
