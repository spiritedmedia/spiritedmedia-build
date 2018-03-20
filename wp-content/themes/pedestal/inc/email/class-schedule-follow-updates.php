<?php
namespace Pedestal\Email;

use Timber\Timber;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Clusters\Cluster;
use Pedestal\Email\{
    Email,
    Follow_Updates
};

class Schedule_Follow_Updates {

    /**
     * Name of the nonce that gets submitted in the $_POST request
     * @var string
     */
    private $nonce_name = 'pedestal-schedule-follow-updates-nonce';

    /**
     * Nonce action that we use to generate the nonce from for the metabox form
     * @var string
     */
    private $nonce_action = 'pedestal-schedule-follow-update-options';

    /**
     * Name of the action the cron job calls
     * @var string
     */
    private $cron_action = 'pedestal_schedule_follow_updates_cron';

    /**
     * Name of the radio button name attribute
     * @var string
     */
    private $radio_button_name = 'pedestal-schedule-follow-updates';

    /**
     * The days of the week for the days of the week <select>
     * @var array
     */
    private $days_of_week = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

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
     * Hook into various actions
     */
    public function setup_actions() {
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ] );
        add_action( 'transition_post_status', [ $this, 'action_transition_post_status' ], 10, 3 );
        add_action( 'save_post', [ $this, 'action_save_post' ], 10, 3 );
        add_action( 'before_delete_post', [ $this, 'action_before_delete_post' ] );
        add_action( 'manage_posts_custom_column', [ $this, 'action_manage_posts_custom_column' ], 10, 2 );
        add_action( $this->cron_action, [ $this, 'action_pedestal_schedule_follow_update_cron' ], 10, 2 );
    }

    /**
     * Hook into various filters
     */
    public function setup_filters() {
        add_filter( 'manage_posts_columns', [ $this, 'filter_manage_posts_columns' ] );
    }

    /**
     * Determine if we should add our scheduled sending of updates metabox to the edit screen
     */
    public function action_add_meta_boxes() {
        $post = get_post();
        if ( ! $this->can_show_meta_box() ) {
            return;
        }
        $id = 'pedestal-schedule-follow-updates';
        $title = 'Schedule Sending of Updates';
        $screen = $post->post_type;
        $context = 'side';
        $priority = 'default';

        add_meta_box(
            $id,
            $title,
            [ $this, 'render_metabox' ],
            $screen,
            $context,
            $priority
        );
    }

    /**
     * Handles rendering the metabox contents
     *
     * @param  WP_Post $post WordPress post object of the edit screen
     */
    public function render_metabox( $post ) {
        $data = $this->get_data( $post->ID );
        $context = [
            'radio_name'     => $this->radio_button_name,
            'days_of_week'   => $this->days_of_week,
            'frequency'      => $data->frequency,
            'next_send_date' => $this->get_next_send_date( $post->ID, PEDESTAL_DATETIME_FORMAT . ' T' ),
        ];
        foreach ( $data as $key => $value ) {
            if ( 'frequency' == $key ) {
                continue;
            }

            $normalized_value = $value;
            switch ( $key ) {
                case 'time':
                    $normalized_value = $this->normalize_time( $value );
                    break;

                case 'minutes':
                    $normalized_value = $this->normalize_minutes( $value );
                    break;
            }
            $context_key = $data->frequency . '_' . $key;
            $context[ $context_key ] = $normalized_value;
        }
        $referer = true;
        $echo = false;
        $context['nonce_field'] = wp_nonce_field( $this->nonce_action, $this->nonce_name, $referer, $echo );
        Timber::render( 'partials/admin/metabox-schedule-follow-update-emails.twig', $context );
    }

    /**
     * Conditional logic for determing if we can show a meta box for a given post
     * @param  integer|WP_Post $post_token Post ID or WP_Post object
     * @return bool            Whether we can or cannot show the metabox
     */
    public function can_show_meta_box( $post_token = null ) {
        $post = get_post( $post_token );
        if (
            ! is_object( $post )
            || ! isset( $post->post_status )
            || 'publish' != $post->post_status
        ) {
            return false;
        }
        if ( ! Types::is_followable_post_type( $post->post_type ) ) {
            return false;
        }
        return true;
    }

    /**
     * Clear the cron event and delete post meta if the post goes from publish to something else
     *
     * @param  string $new_status The new status of the post
     * @param  string $old_status The old status of the post
     * @param  WP_Post $post      A WordPress post object
     * @return [type]             [description]
     */
    public function action_transition_post_status( $new_status = '', $old_status = '', $post ) {
        if ( ! is_object( $post )
            || ! isset( $post->post_type )
            || ! Types::is_followable_post_type( $post->post_type )
        ) {
            return;
        }
        // If the post transitioned from 'publish' to anything else
        if ( 'publish' != $new_status && 'publish' == $old_status ) {
            $this->unschedule_cron_event( $post->ID );
            $this->delete_post_meta( $post->ID );
        }
    }

    /**
     * Save meta data when the post is saved
     *
     * @param  integer $post_id ID of the post being saved
     * @param  WP_Post  $post    WP_Post object being saved
     * @param  boolean $update  Whether the post being saved is an update or not
     */
    public function action_save_post( $post_id = 0, $post, $update = false ) {
        // No $_POST data set, so bail
        if ( empty( $_POST['pedestal-schedule-follow-updates'] ) ) {
            return;
        }

        // Don't bother messing with revisions
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Bail if the post_type isn't a followable post type
        $post = get_post( $post );
        if ( ! is_object( $post )
            || ! isset( $post->post_type )
            || ! Types::is_followable_post_type( $post->post_type )
        ) {
            return;
        }

        // Only save data when the post is published
        if ( 'publish' != $post->post_status ) {
            return;
        }

        // Check nonce
        if ( ! check_admin_referer( $this->nonce_action, $this->nonce_name ) ) {
            wp_die( 'Bad nonce!' );
        }

        $frequency = $_POST['pedestal-schedule-follow-updates'];
        $values = [];
        switch ( $frequency ) {
            case 'weekly':
                $day_key = $this->radio_button_name . '-weekly-day';
                $values['day'] = $this->sanitize_day( $day_key );

                $time_key = $this->radio_button_name . '-weekly-time';
                $values['time'] = $this->sanitize_time( $time_key );
                break;

            case 'daily':
                $time_key = $this->radio_button_name . '-daily-time';
                $values['time'] = $this->sanitize_time( $time_key );
                break;

            case 'hourly':
                $minute_key = $this->radio_button_name . '-hourly-minutes';
                $values['minutes'] = $this->sanitize_minutes( $minute_key );
                break;
        }

        if ( empty( $values ) ) {
            $frequency = 'none';
        }
        $defaults = [
            'day'     => '',
            'time'    => '',
            'minutes' => '',
        ];
        $values = wp_parse_args( $values, $defaults );

        // Get old data for comparisons
        $old_data = $this->get_data( $post->ID );
        $old_timestamp = $this->get_cron_timestamp( $post->ID );

        // Update post meta
        update_post_meta( $post->ID, 'schedule_frequency', $frequency );
        update_post_meta( $post->ID, 'schedule_timing', $values );

        // Get new data for comparisons
        $new_data = $this->get_data( $post->ID );
        $new_timestamp = $this->get_cron_timestamp( $post->ID );

        // Schedule cron event
        $recurrence = $frequency;
        $hook = $this->cron_action;
        $args = [
            'post_id' => $post->ID,
        ];

        // $new_data/$old_data are objects
        // See http://php.net/manual/en/language.oop5.object-comparison.php
        if ( $new_data != $old_data || 'none' == $frequency ) {
            $this->unschedule_cron_event( $post->ID );
        }
        if ( ! wp_next_scheduled( $hook, $args ) && 'none' != $frequency ) {
            wp_schedule_event( $new_timestamp, $recurrence, $hook, $args );
        }
    }

    /**
     * Delete the scheduled cron event if the post is being deleted
     *
     * @param  integer $post_id ID of the post being deleted
     */
    public function action_before_delete_post( $post_id = 0 ) {
        $post = get_post( $post_id );
        if ( ! is_object( $post )
            || ! isset( $post->post_type )
            || ! Types::is_followable_post_type( $post->post_type )
        ) {
            return;
        }
        $this->unschedule_cron_event( $post->ID );
    }

    /**
     * Callback for the scheduled cron event to actually send out the follow update email
     *
     * @param  integer $post_id Post ID to send a follow update for
     */
    public function action_pedestal_schedule_follow_update_cron( $post_id = 0 ) {
        $cluster = Cluster::get( (int) $post_id );
        if ( ! Types::is_followable_post_type( $cluster->get_post_type() ) ) {
            return;
        }
        $email = Follow_Updates::get_instance();
        $result = $email->send_email_to_group( $cluster );
        if ( $result ) {
            // Set the last sent email date
            $cluster->set_last_email_notification_date();
        }
    }

    /**
     * Display content for our new columns to show on followable post types
     *
     * @param  string  $column_name Name of the column that is being rendered
     * @param  integer $post_id     Post ID of the item being rendered
     */
    public function action_manage_posts_custom_column( $column_name = '', $post_id = 0 ) {
        if ( 'update_frequency' == $column_name ) {
            $data = $this->get_data( $post_id );
            $frequency = ucfirst( $data->frequency );
            if ( 'None' == $frequency ) {
                $frequency = '--';
            }
            echo $frequency;
        }
        if ( 'next_send_date' == $column_name ) {
            echo $this->get_next_send_date( $post_id, 'g:i A<\b\r>D M d ' );
        }
    }

    /**
     * Add new columns to the edit posts screen of followable post types
     *
     * @param  array $columns Group of column slugs and labels
     * @return array          Modified columns
     */
    public function filter_manage_posts_columns( $columns = [] ) {
        $post_type = get_current_screen()->post_type;
        if ( ! Types::is_followable_post_type( $post_type ) ) {
            return $columns;
        }

        $new_columns = [];
        foreach ( $columns as $key => $label ) {
            // Add our new columns before the date column
            if ( 'date' == $key ) {
                $new_columns['update_frequency'] = 'Update Frequency';
                $new_columns['next_send_date'] = 'Next Send Date';
            }
            $new_columns[ $key ] = $label;
        }
        return $new_columns;
    }

    /**
     * Sanitize Day data being sent from the metabox form
     *
     * @param  string $key Key of the $_POST to check
     * @return string      Day of the week
     */
    public function sanitize_day( $key = '' ) {
        if ( ! empty( $_POST[ $key ] ) && in_array( $_POST[ $key ], $this->days_of_week ) ) {
            return $_POST[ $key ];
        }
        return $this->days_of_week[0];
    }

    /**
     * Sanitize Time data being sent from the metabox form
     *
     * @param  string $key Key of the $_POST to check
     * @return integer     Number of seconds past midnight for a given time
     */
    public function sanitize_time( $key = '' ) {
        if ( empty( $_POST[ $key ] ) ) {
            return 0;
        }
        $seconds = strtotime( 'midnight ' . $_POST[ $key ] ) - strtotime( 'midnight' );
        return $seconds;
    }

    /**
     * Sanitize Minute data being sent from the metabox form
     *
     * @param  string $key Key of the $_POST to check
     * @return integer     Number 0 - 59
     */
    public function sanitize_minutes( $key = '' ) {
        if ( empty( $_POST[ $key ] ) ) {
            return 0;
        }
        return min( absint( $_POST[ $key ] ), 59 );
    }

    /**
     * Normalize the number of seconds past midnight to a timestamp
     *
     * @param  integer $time   Number of seconds past midnight of a timestamp
     * @param  string  $format Date format to output
     * @return string          Formatted date
     */
    public function normalize_time( $time = 0, $format = 'H:i' ) {
        $seconds = absint( $time );
        return date( $format, $seconds );
    }

    /**
     * Normalize minutes by padding with a 0 if less than 10
     * The number will always be less than 60
     *
     * @param  integer $minutes Minutes past the hour to be normalized
     * @return string|integer           Normalized minutes
     */
    public function normalize_minutes( $minutes = 0 ) {
        $minutes = absint( $minutes );
        if ( 9 > $minutes ) {
            return '0' . $minutes;
        }
        return min( $minutes, 59 );
    }

    /**
     * Get the frequency and timing data for a given post id
     *
     * @param  integer $post_id ID of the post to get data for
     * @return object           Data
     */
    public function get_data( $post_id = 0 ) {
        $post_id = absint( $post_id );
        $frequency = get_post_meta( $post_id, 'schedule_frequency', true );
        if ( ! $frequency ) {
            $frequency = 'none';
        }
        $timing = get_post_meta( $post_id, 'schedule_timing', true );
        if ( ! $timing ) {
            $timing = [
                'day'     => '',
                'time'    => '',
                'minutes' => '',
            ];
        }
        $timing['frequency'] = $frequency;
        return (object) $timing;
    }

    /**
     * Delete schedule follow update emails specific post meta for a given post ID
     *
     * @param  integer $post_id ID of the post to delete post meta for
     */
    public function delete_post_meta( $post_id = 0 ) {
        $post_id = absint( $post_id );
        delete_post_meta( $post_id, 'schedule_frequency' );
        delete_post_meta( $post_id, 'schedule_timing' );
    }

    /**
     * Get the date when the next scheduled follow update email will be sent
     *
     * @param  integer $post_id     ID of the post to get the next send date for
     * @param  string  $date_format PHP date format that should be returned
     * @return string               Date string of when the next scheduled follow update email will be sent
     */
    public function get_next_send_date( $post_id = 0, $date_format = PEDESTAL_DATETIME_FORMAT ) {
        $data = $this->get_data( $post_id );
        // If scheduling isn't being used for this post then bail
        if (
            ! is_object( $data )
            || ! isset( $data->frequency )
            || 'none' == $data->frequency
        ) {
            return '';
        }

        // Calculate the date in UTC time
        $tz = get_option( 'timezone_string' );
        switch ( $data->frequency ) {
            case 'weekly':
                $pieces = [
                    $data->day,
                    $this->normalize_time( $data->time ),
                    $tz,
                ];
                // UTC version time
                $time = strtotime( implode( ' ', $pieces ) );
                $now = strtotime( 'now UTC' );
                if ( $time < $now ) {
                    $time = strtotime( '+1 week', $time );
                }
                break;

            case 'daily':
                // UTC version time
                $time = strtotime( $this->normalize_time( $data->time ) . ' ' . $tz );
                $now = strtotime( 'now UTC' );
                if ( $time < $now ) {
                    $time = strtotime( '+1 day', $time );
                }
                break;

            case 'hourly':
                $hour = intval( date( 'H' ) );
                $current_minute = intval( date( 'i' ) );
                if ( $data->minutes < $current_minute ) {
                    $hour++;
                }
                $time = mktime( $hour, $data->minutes );
                break;
        }

        // Return the result in local time
        return get_date_from_gmt(
            date( 'Y-m-d H:i:s', $time ),
            $date_format
        );
    }

    /**
     * Get Unix time of the next send date for use in cron events
     *
     * @param  integer $post_id ID of the post to get the next send date for
     * @return integer          Unix time of the next send date
     */
    public function get_cron_timestamp( $post_id = 0 ) {
        $post_id = absint( $post_id );
        $timestamp = $this->get_next_send_date( $post_id, 'Y-m-d H:i:s' ); // Local time
        if ( ! $timestamp ) {
            return 0;
        }
        $timestamp = get_gmt_from_date( $timestamp, 'U' ); // UTC time
        $timestamp = intval( $timestamp );
        return $timestamp;
    }

    /**
     * Unschedule a cron event for a given post id
     *
     * @param  integer $post_id ID of the post to unschedule a cron event for
     * @return boolean          Whether unscheduling the cron was successful or not
     */
    public function unschedule_cron_event( $post_id = 0 ) {
        // Unschedule any cron events
        $post_id = absint( $post_id );
        $hook = $this->cron_action;
        $args = [
            'post_id' => $post_id,
        ];
        // Get the Unix timestamp of the next time the scheduled hook will occur
        $timestamp = wp_next_scheduled( $hook, $args );
        return wp_unschedule_event( $timestamp, $hook, $args );
    }
}
