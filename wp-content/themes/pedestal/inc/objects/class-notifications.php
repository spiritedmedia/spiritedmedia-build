<?php

namespace Pedestal\Objects;

class Notifications {

    private $client;

    private $defaults = [
        'username'       => PEDESTAL_SLACK_BOT_NAME,
        'icon_emoji'     => PEDESTAL_SLACK_BOT_EMOJI,
        'channel'        => PEDESTAL_SLACK_CHANNEL_BOTS_PRODUCT,
        'recipient'      => '',
        'link_names'     => true,
        'allow_markdown' => true,
    ];

    /**
     * Send a message to the specified channel or user
     *
     * @param string $msg       The message body
     * @param array  $args      Settings to override defaults
     */
    public function send( $msg, $args ) {
        // Prevent test environments from firing off to Slack
        if ( defined( 'WP_ENV' ) && 'development' == WP_ENV ) {
            // If you really need to test this locally, comment out the following statement
            return;
        }
        // Prepare the data / payload to be posted to Slack
        $data = [];
        $payload = wp_parse_args( $args, $this->defaults );
        $payload['text'] = $msg;
        $data['payload'] = json_encode( $payload );

        $this->post( $data );
    }

    /**
     * Handle POST request to Slack API
     *
     * @param  array $data API data
     * @uses wp_remote_post()
     *
     * @return array|WP_Error       Array of results including HTTP headers or WP_Error if the request failed
     */
    private function post( $data ) {
        return wp_remote_post( PEDESTAL_SLACK_WEBHOOK_ENDPOINT, [
            'method'      => 'POST',
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => false,
            'headers'     => [],
            'body'        => $data,
            'cookies'     => [],
        ] );
    }
}
