<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use \Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;

class Cron_Management {

    private static $default_notify_channel = PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL;

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Cron_Management;
            self::$instance->setup_actions();
        }
        return self::$instance;
    }

    private function setup_actions() {
        add_action( 'pedestal_cron_notify_newsletter_subscriber_count', [ $this, 'notify_newsletter_subscriber_count' ] );
        add_action( 'wp', [ $this, 'action_wp_cron_notify_newsletter_subscriber_count' ] );
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
     *
     * Wrapper for Subscriptions->notify_newsletter_subscriber_count()
     *
     * @uses \Pedestal\Subscriptions::notify_newsletter_subscriber_count()
     */
    public function notify_newsletter_subscriber_count() {
        Pedestal()->subscriptions->notify_newsletter_subscriber_count();
    }
}
