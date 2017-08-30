<?php

namespace Pedestal\Posts\Entities;

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
            return $what_field;
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

    public function get_location( $escape = false ) {
        $venue = $this->get_venue_name();
        $address = $this->get_address();
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
     * Get the full time string for the event
     *
     * If the event ends on the same day as it starts, displays the end time
     * without the date.
     *
     * @return string
     */
    public function get_when( $format = PEDESTAL_DATETIME_FORMAT ) {
        $out = $this->get_start_time( $format );
        if ( $this->get_end_time() ) {
            if ( $this->get_start_time( 'Y-m-d' ) === $this->get_end_time( 'Y-m-d' ) ) {
                $end = $this->get_end_time( get_option( 'time_format' ) );
            } else {
                $end = $this->get_end_time( $format );
            }
            $out .= ' to ' . $end;
        }
        return $out;
    }

    /**
     * Get the start time for the event
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
     * Get the end time for the event
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
     * Get the link to more info
     *
     * @return string
     */
    public function get_details_link() {
        $url = $this->get_details_link_url();
        $text = $this->get_details_link_text();
        if ( $url && $text ) {
            $out = '<p class="c-details-table__link">';
            $out .= '<a href="' . esc_url( $url ) . '" class="c-details-table__link__btn" target="_blank"';
            $out .= 'data-ga-category="Event" data-ga-label="Details|' . esc_attr( $text ) . '">';
            $out .= $text;
            $out .= '</a>';
            $out .= '</p>';
            return $out;
        }
        return '';
    }

    public function get_google_calendar_link() {
        $endpoint = 'https://www.google.com/calendar/render?';
        $dates = $this->get_dtstart() . '/' . $this->get_dtend();
        $params = [
            'action'   => 'TEMPLATE',
            'text'     => $this->get_summary(),
            'dates'    => $dates,
            'details'  => $this->get_description(),
            'location' => $this->get_location(),
            'output'   => 'xml',
        ];

        return $endpoint . http_build_query( $params );
    }

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
        return preg_replace( '/([\,;])/','\\\$1' , $string );
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
            $end = $start + 60 * 60;
        }
        return $this->to_ics_datetime( $end );
    }

    /**
     * Get a summary for the event (that is, the event name.)
     *
     * @return string
     */
    public function get_summary( $escape = false ) {
        $summary = $this->get_title();
        if ( $escape ) {
            $summary = $this->to_ics_string( $summary );
        }
        return $summary;
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
}
