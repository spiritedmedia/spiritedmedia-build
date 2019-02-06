<?php

namespace Pedestal\Posts\Entities;

use function Pedestal\Pedestal;

use Timber\Timber;
use Pedestal\Utils\Utils;

class Event extends Entity {

    protected static $post_type = 'pedestal_event';

    /**
     * Get the event's additional details
     *
     * Will favor the 'more' field and fall back to `post_content` if unavailable
     *
     * @return string
     */
    public function get_more() {
        $more_field = $this->get_fm_field( 'event_details', 'more' );
        if ( $more_field ) {
            return $more_field;
        }
        return $this->get_content();
    }

    /**
     * Get the 'what' for the event
     *
     * Will favor the 'what' field and fall back to excerpt if unavailable
     *
     * @return string
     */
    public function get_what() {
        $what_field = $this->get_fm_field( 'event_details', 'what' );
        if ( $what_field ) {
            return Utils::reverse_wpautop( $what_field );
        }
        return $this->get_excerpt();
    }

    /**
     * Get the venue name for the event
     *
     * @return string
     */
    public function get_venue_name() {
        return $this->get_fm_field( 'event_details', 'venue_name' );
    }

    /**
     * Get the address for the event
     *
     * @return string
     */
    public function get_address() {
        return $this->get_fm_field( 'event_details', 'address' );
    }

    /**
     * Get the full 'where' for the event
     *
     * @return string
     */
    public function get_where() {
        $where   = '';
        $venue   = $this->get_venue_name();
        $address = $this->get_address();
        if ( $venue ) {
            $where .= $venue;
        }
        if ( $venue && $address ) {
            $where .= ' at ';
        }
        if ( $address ) {
            $where .= $address;
        }
        return $where;
    }

    /**
     * Get an event location in "venue (address)"" format for ICS
     *
     * @param  boolean $escape  Whether to escape the string
     * @return string           The location in "venue (address)" format"
     */
    public function get_ics_location( $escape = false ) {
        $venue    = $this->get_venue_name();
        $address  = $this->get_address();
        $location = "$venue ($address)";

        if ( $escape ) {
            $location = $this->to_ics_string( $location );
        }

        return $location;
    }

    /**
     * Get the cost of the event
     */
    public function get_cost() {
        return $this->get_fm_field( 'event_details', 'cost' );
    }

    /**
     * Is this an all-day event?
     *
     * @return bool
     */
    public function is_all_day() {
        $all_day = $this->get_fm_field( 'event_details', 'all_day' );
        if ( ! $all_day ) {
            return false;
        }
        // See http://fieldmanager.org/docs/fields/checkbox/ for default values
        if ( '1' === $all_day ) {
            return true;
        }
    }

    /**
     * Get the full time string for the event
     *
     * @return string
     */
    public function get_when() {
        $start_timestamp = (int) $this->get_start_time();
        if ( ! $start_timestamp ) {
            return;
        }

        $end_timestamp = (int) $this->get_end_time();
        $all_day       = $this->is_all_day();
        $display_end   = (bool) $end_timestamp;
        $start_date    = date( 'Y-m-d', $start_timestamp );
        $end_date      = date( 'Y-m-d', $end_timestamp );
        $same_day_end  = $start_date === $end_date;

        $date_format     = 'F j, Y';
        $datetime_format = $date_format . ' \a\t ' . PEDESTAL_TIME_FORMAT;

        if ( $all_day ) {
            $start_format = $date_format;
            $end_format   = $date_format;

            // If the event lasts all day and it ends on the same day then we
            // shouldn't display an end date
            if ( $same_day_end ) {
                $display_end = false;
            }
        } else {
            $start_format = $datetime_format;
            $end_format   = $datetime_format;

            if ( $same_day_end ) {
                $start_time = date( $start_format, $start_timestamp );
                $end_time   = date( $end_format, $end_timestamp );
                if ( $start_time === $end_time ) {
                    // Hide the end time if it's identical to the start time
                    $display_end = false;
                } else {
                    // Display the end time without the date if the event ends on the same day
                    $end_format = PEDESTAL_TIME_FORMAT;
                }
            }
        }

        $start_time = $start_time ?? date( $start_format, $start_timestamp );
        // Tweak the start date preposition to sound better with a single-day time range
        if ( ! $all_day && $display_end && $same_day_end ) {
            $start_time = str_replace( 'at', 'from', $start_time );
        }

        $out = $start_time;
        if ( $display_end ) {
            $end_time = date( $end_format, $end_timestamp );
            $out     .= ' to ' . $end_time;
        }

        return apply_filters( 'pedestal_event_get_when', $out );
    }

    /**
     * Get the raw start time for the event
     *
     * Does not take all day events into account.
     *
     * @param  string $format Date format. Defaults to Unix timestamp
     *
     * @return string
     */
    public function get_start_time( $format = 'U' ) {
        $start_time = $this->get_fm_field( 'event_details', 'start_time' );
        if ( $start_time ) {
            return date( $format, $start_time );
        }
        return '';
    }

    /**
     * Get the raw end time for the event
     *
     * Does not take all day events into account.
     *
     * @param  string $format Date format. Defaults to Unix timestamp
     *
     * @return string
     */
    public function get_end_time( $format = 'U' ) {
        $end_time = $this->get_fm_field( 'event_details', 'end_time' );
        if ( $end_time ) {
            return date( $format, $end_time );
        }
        return '';
    }

    /**
     * Get the more info link URL
     *
     * @return string URL
     */
    public function get_details_link_url() {
        $url = $this->get_fm_field( 'event_details', 'url' );
        if ( $url ) {
            return $url;
        }
        // Fallback for old behavior
        return $this->get_fm_field( 'event_link', 'url' );
    }

    /**
     * Get the more info link text
     *
     * @return string
     */
    public function get_details_link_text() {
        $text = $this->get_fm_field( 'event_details', 'text' );
        if ( $text ) {
            return $text;
        }
        // Fallback for old behavior
        return $this->get_fm_field( 'event_link', 'text' );
    }

    /**
     * Get a Google Calendar link with all of the event details
     *
     * @return string  Google Calendar URL
     */
    public function get_google_calendar_link() {
        $endpoint = 'https://www.google.com/calendar/render?';
        $dates    = $this->get_dtstart() . '/' . $this->get_dtend();
        $params   = [
            'action'   => 'TEMPLATE',
            'text'     => $this->get_ics_title(),
            'dates'    => $dates,
            'details'  => $this->get_description(),
            'location' => $this->get_ics_location(),
            'output'   => 'xml',
        ];

        return $endpoint . http_build_query( $params );
    }

    /**
     * Get the link to the iCal version of the event
     *
     * @return string  URL of the iCal version of the event
     */
    public function get_ics_link() {
        return rtrim( $this->get_permalink(), '/' ) . '/ics/';
    }

    /**
     * Convert a timestamp into the iCal datetime format
     *
     * @return string
     */
    private function to_ics_datetime( $date ) {
        return date( 'Ymd\THis\Z', $date );
    }

    private function to_ics_string( $string ) {
        return preg_replace( '/([\,;])/', '\\\$1', $string );
    }

    /**
     * Get the start time for the event in a format that
     * can be used in the iCal `DTSTART` field.
     *
     * @return string
     */
    public function get_dtstart() {
        $start = $this->get_fm_field( 'event_details', 'start_time' );
        return $this->to_ics_datetime( $start );
    }

    /**
     * Get the end time for the event in a format that
     * can be used in the iCal `DTEND` field.
     *
     * @return string
     */
    public function get_dtend() {
        $end = $this->get_fm_field( 'event_details', 'end_time' );
        if ( ! $end ) {
            $start = $this->get_fm_field( 'event_details', 'start_time' );
            $end   = $start + 60 * 60;
        }
        return $this->to_ics_datetime( $end );
    }

    /**
     * Get an ICS-safe title
     *
     * @return string
     */
    public function get_ics_title() {
        return $this->to_ics_string( $this->get_title() );
    }

    /**
     * Get a description for the event.
     *
     * @return string
     */
    public function get_description( $escape = false ) {
        $description = $this->get_excerpt();
        if ( $escape ) {
            $description = $this->to_ics_string( $description );
        }
        return $description;
    }

    /**
     * Get the name of the icon for this entity's source
     *
     * @return string
     */
    public function get_source_icon_name() {
        return 'calendar';
    }

    /**
     * Set the event details from an array
     *
     * @param array $details An array of event details
     */
    public function set_event_details( $details ) {
        return $this->set_meta( 'event_details', $details );
    }

    /**
     * Get the default SEO description for the post
     *
     * @param integer $len Length of description in characters. Defaults to 150.
     *
     * @return string
     */
    public function get_default_seo_description( $len = 150 ) {
        $description = $this->get_what();
        if ( ! $description ) {
            $description = parent::get_default_seo_description( $len );
        }
        return strip_tags( $description );
    }

    /**
     * Get the Twig context for this post
     *
     * @param array Existing context to filter
     * @return array Twig context
     */
    public function get_context( $context = [] ) {
        $context = [
            'what'           => $this->get_what(),
            'where'          => $this->get_where(),
            'when'           => $this->get_when(),
            'cost'           => $this->get_cost(),
            'cta_link'       => $this->get_details_link_url(),
            'cta_label'      => $this->get_details_link_text(),
            'cta_source'     => $this->get_fm_field( 'event_details', 'cta_source' ),
            'show_meta_info' => false,
            'content'        => '',
            'show_header'    => true,
            'ga_category'    => 'post-content',
        ] + parent::get_context( $context );
        if ( is_singular( static::$post_type ) ) {
            $context['show_header'] = false;
        }
        if ( Pedestal()->is_stream() ) {
            $context['ga_category'] = 'stream-item';
        }
        ob_start();
            Timber::render( 'partials/event.twig', $context );
        $context['content'] = ob_get_clean();
        return $context;
    }
}
