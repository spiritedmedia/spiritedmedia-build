<?php

namespace Pedestal\Objects;

use \Maknz\Slack\Client;

class Notifications {

    private $client;

    private $defaults = [
        'username'       => PEDESTAL_SLACK_BOT_NAME,
        'icon'           => PEDESTAL_SLACK_BOT_EMOJI,
        'channel'        => PEDESTAL_SLACK_CHANNEL_BOTS_PRODUCT,
        'recipient'      => '',
        'link_names'     => true,
        'allow_markdown' => true,
    ];

    private $team_endpoints = [
        'billypenn' => PEDESTAL_SLACK_WEBHOOK_ENDPOINT,
    ];

    public function __construct( $team = 'billypenn' ) {
        $this->client = $this->setup_client( $team );
    }

    /**
     * Setup the Slack client for the specified team
     *
     * @param  string $team Team subdomain
     * @return Client
     */
    private function setup_client( $team ) {
        $endpoint = $this->get_team_endpoint( $team );
        return new Client( $endpoint, $this->defaults );
    }

    /**
     * Get the Slack endpoint based on the subdomain
     *
     * @param  string $team Team subdomain
     * @return string       Team incoming webhook endpoint
     */
    private function get_team_endpoint( $team ) {
        return $this->team_endpoints[ $team ];
    }

    /**
     * Send a message to the specified channel or user
     *
     * @param string $msg       The message body
     * @param array  $args      Settings to override defaults
     */
    public function send( $msg, $args ) {
        $args = wp_parse_args( $args, $this->defaults );
        $recipient = ( ! empty( $args['recipient'] ) ) ? $args['recipient'] : $args['channel'];
        if ( ! empty( $recipient ) ) {
            $this->client->to( $recipient )->send( $msg );
        }
    }
}
