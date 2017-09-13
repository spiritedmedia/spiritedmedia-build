<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use \Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;
use Pedestal\Email\Newsletter_Emails;
use Pedestal\Objects\Notifications;

class Cron_Management {

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
        add_action( 'wp', [ $this, 'action_wp_cron_notify_newsletter_subscriber_count' ] );
        add_action( 'pedestal_cron_notify_newsletter_subscriber_count', [ $this, 'notify_newsletter_subscriber_count' ] );
    }

    /**
     * Schedule cron job for newsletter subscriber count notification
     *
     * @uses \Pedestal\Utils\Utils::get_time()
     */
    public function action_wp_cron_notify_newsletter_subscriber_count() {
        if ( ! wp_next_scheduled( 'pedestal_cron_notify_newsletter_subscriber_count' ) ) {
            wp_schedule_event( Utils::get_time(), 'daily', 'pedestal_cron_notify_newsletter_subscriber_count' );
        }
    }

    /**
     * Notify Slack of the current newsletter subscriber count
     */
    public function notify_newsletter_subscriber_count( $notification_args = [] ) {
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
}
