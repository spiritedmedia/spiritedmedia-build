<?php

namespace Pedestal\Objects;

class Notifications {
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
        $defaults = [
            'username'       => PEDESTAL_SLACK_BOT_NAME,
            'icon_emoji'     => PEDESTAL_SLACK_BOT_EMOJI,
            'channel'        => PEDESTAL_SLACK_CHANNEL_BOTS_PRODUCT,
            'recipient'      => '',
            'link_names'     => true,
            'allow_markdown' => true,
        ];
        $payload = wp_parse_args( $args, $defaults );
        $payload['text'] = $msg;
        $data['payload'] = json_encode( $payload );

        wp_remote_post( PEDESTAL_SLACK_WEBHOOK_ENDPOINT, [
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
