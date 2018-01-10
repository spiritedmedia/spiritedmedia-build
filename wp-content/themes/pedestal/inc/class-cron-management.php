<?php

namespace Pedestal;

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
            $hook       = 'pedestal_' . sanitize_title( $hook );
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
}
