<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use Pedestal\Email\{
    Email,
    Newsletter_Emails
};
use Pedestal\Utils\Utils;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Objects\Notifications;
use Pedestal\Posts\Post;

class Cron_Management {

    /**
     * Recurring events
     *
     * The key determines the name of the action/event as well as the handler
     * method. The value determines the recurrence frequency.
     *
     * @var array
     */
    private $events = [
        'notify_newsletter_subscriber_count' => 'daily',
        'refresh_subscriber_count'   => 'hourly',
    ];

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
     * Hook into WordPress via actions
     */
    private function setup_actions() {
        foreach ( $this->events as $event => $recurrence ) {
            // Only run some events on live environment
            if ( 'live' !== PEDESTAL_ENV && 'notify_newsletter_subscriber_count' == $event ) {
                continue;
            }

            $prefixed_event = 'pedestal_cron_' . $event;

            // Schedule cron jobs
            add_action( 'wp', function() use ( $prefixed_event, $recurrence ) {
                if ( ! wp_next_scheduled( $prefixed_event ) ) {
                    wp_schedule_event( Utils::get_time(), $recurrence, $prefixed_event );
                }
            } );

            // Handle events
            add_action( $prefixed_event, [ $this, 'handle_' . $event ] );
        }
    }

    /**
     * Notify Slack of the current newsletter subscriber count
     */
    public function handle_notify_newsletter_subscriber_count( $notification_args = [] ) {
        $newsletter = Newsletter_Emails::get_instance();
        $count = $newsletter->get_daily_newsletter_subscriber_count();

        if ( empty( $count ) ) {
            return;
        }

        $notification_args = wp_parse_args( $notification_args, [
            'channel' => PEDESTAL_SLACK_CHANNEL_NEWSLETTER,
        ] );

        $msg = sprintf( 'There are currently %d email addresses subscribed to the Daily Newsletter.', $count );
        $notifier = new Notifications;
        $notifier->send( $msg, $notification_args );
    }

    /**
     * Refresh stored subscriber count for email lists
     *
     * Refreshes the 25 least recently updated stories and the newsletter +
     * breaking news lists.
     */
    public function handle_refresh_subscriber_count() {
        $query = new \WP_Query( [
            'meta_key'       => 'subscriber_count_last_updated',
            'order'          => 'ASC',
            'orderby'        => 'meta_value_num',
            'post_type'      => 'pedestal_story',
            'posts_per_page' => 25,
        ] );
        Email::refresh_subscriber_counts( $query );
    }
}
