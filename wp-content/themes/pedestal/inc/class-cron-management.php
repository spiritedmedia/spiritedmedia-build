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
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into WordPress via actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_maybe_register_cron_events' ] );
    }

    /**
     * Hook into WordPress via filters
     */
    private function setup_filters() {
        add_filter( 'pedestal_cron_events', [ $this, 'filter_pedestal_cron_events' ] );
        add_filter( 'cron_schedules', function( $schedules = [] ) {
            if ( ! isset( $schedules['weekly'] ) ) {
                $schedules['weekly'] = [
                    'interval' => 1 * WEEK_IN_SECONDS,
                    'display'  => 'Every Week',
                ];
            }
            return $schedules;
        }, 10, 1 );
    }

    /**
     * Allow other classes to hook in and register cron jobs easier
     * via the pedestal_cron_events filter
     *
     * Note: This method needs to be run on the 'init' action
     * to give other classes time to hook in to the filter
     */
    public function action_init_maybe_register_cron_events() {
        /**
         * A collection of cron events we wish to register in one go.
         *
         * @param array  $cron_events Cron events that we wish to register
         */
        $cron_events = apply_filters( 'pedestal_cron_events', [] );
        if ( ! is_array( $cron_events ) || empty( $cron_events ) ) {
            return;
        }
        foreach ( $cron_events as $hook => $event ) {
            $hook       = sanitize_title( $hook );
            $hook       = 'pedestal_' . $hook;
            $timestamp  = $event['timestamp'];
            $recurrence = $event['recurrence'];
            $callback   = $event['callback'];

            add_action( $hook, $callback );
            add_action( 'wp', function() use ( $timestamp, $recurrence, $hook ) {
                if ( ! wp_next_scheduled( $hook ) ) {
                    wp_schedule_event( $timestamp, $recurrence, $hook );
                }
            } );
        }
    }

    /**
     * Setup cron events that don't live anywhere else
     *
     * @param  array  $events Cron events we want to register
     * @return array          Cron events we want to register
     */
    public function filter_pedestal_cron_events( $events = [] ) {
        $events['refresh_subscriber_count'] = [
            'timestamp'  => intval( current_time( 'U' ) ),
            'recurrence' => 'hourly',
            'callback'   => [ $this, 'handle_refresh_subscriber_count' ],
        ];
        return $events;
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
