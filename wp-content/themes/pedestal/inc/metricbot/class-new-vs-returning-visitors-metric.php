<?php
namespace Pedestal\Metricbot;

use Pedestal\Objects\{
    Google_Analytics,
    Notifications
};
use Pedestal\Utils\Utils;

class New_Vs_Returning_Users_Metric {

    /**
     * Get an instance of this class
     */
    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into WordPress via filters
     */
    private function setup_filters() {
        if ( 'live' === PEDESTAL_ENV ) {
            add_filter( 'pedestal_cron_events', [ $this, 'filter_pedestal_cron_events' ] );
        }
    }

    /**
     * Setup a cron event to fire this metric periodically
     *
     * @param  array  $events Cron events that we wish to register
     * @return array          Cron events that we wish to register
     */
    public function filter_pedestal_cron_events( $events = [] ) {
        // We want this to fire at a specific time
        $timestamp = 'monday 9:00am ' . get_option( 'timezone_string' );
        $timestamp = strtotime( $timestamp );

        $events['metricbot_new_vs_returning_users'] = [
            'timestamp'  => $timestamp,
            'recurrence' => 'weekly',
            'callback'   => [ $this, 'send' ],
        ];
        return $events;
    }

    /**
     * Get the session data for a given date range
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @return object             Session data
     */
    public function get_session_data( $start_date = 'yesterday', $end_date = 'yesterday' ) {
        $ga          = Google_Analytics::get_instance();
        $date_range  = $ga->get_date_range( $start_date, $end_date );

        $metric_args = [
            'ga:sessions' => '',
        ];
        $metrics     = $ga->get_metrics( $metric_args );

        $data        = $ga->make_request([
            'date_range' => [ $date_range ],
            'metrics'    => [ $metrics ],
            'dimensions' => [ $ga->get_dimension( 'ga:userType' ) ],
        ]);
        $output             = $ga->format_output( $data );
        $new_visitors       = $output[0]->{'ga:sessions'};
        $returning_visitors = $output[1]->{'ga:sessions'};
        $total_visitors     = $new_visitors + $returning_visitors;
        return (object) [
            'new_visitors'       => $new_visitors,
            'returning_visitors' => $returning_visitors,
            'total_visitors'     => $total_visitors,
        ];
    }

    /**
     * Get the pageviews for a given date range
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @return integer            Pageviews
     */
    public function get_pageviews( $start_date = 'yesterday', $end_date = 'yesterday' ) {
        $ga          = Google_Analytics::get_instance();
        $date_range  = $ga->get_date_range( $start_date, $end_date );

        $metric_args = [
            'ga:pageviews' => '',
        ];
        $metrics     = $ga->get_metrics( $metric_args );
        $data = $ga->make_request([
            'date_range' => [ $date_range ],
            'metrics' => [ $metrics ],
            'page_size' => '',
            'order_by' => '',
        ]);
        $output = $ga->format_output( $data );
        return $output[0]->{'ga:pageviews'};
    }

    /**
     * Get pageviews for the top articles published between the given timeframe
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @return object             Pageveiw data
     */
    public function get_top_pageview_data( $start_date = 'yesterday', $end_date = 'yesterday' ) {
        $ga          = Google_Analytics::get_instance();
        $date_range  = $ga->get_date_range( $start_date, $end_date );

        $metric_args = [
            'ga:pageviews' => '',
        ];
        $metrics = $ga->get_metrics( $metric_args );

        $date_range_arr        = $this->get_date_period( $start_date, $end_date );
        $dimension_filter_args = [];
        foreach ( $date_range_arr as $date ) {
            $expression = '/' . $date->format( 'Y/m/d' );
            $dimension_filter_args[] = [
                'name'        => 'ga:pagePath',
                'operator'    => 'BEGINS_WITH',
                'expressions' => [ $expression ],
            ];
        }
        $dimension_filter_clause = $ga->get_dimension_filters( $dimension_filter_args );

        $data = $ga->make_request([
            'date_range'              => [ $date_range ],
            'metrics'                 => [ $metrics ],
            'dimensions'              => [ $ga->get_dimension( 'ga:pagePath' ) ],
            'page_size'               => '10',
            'dimension_filter_clause' => $dimension_filter_clause,
            'order_by'                => [
                'fieldName' => 'ga:pageviews',
                'sortOrder' => 'DESCENDING',
            ],
        ]);
        return $ga->format_output( $data );
    }

    /**
     * Get data to calculate the engaged completion rate for posts published between the given timeframe
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @return array              Engagement data
     */
    public function get_engaged_completion_data( $start_date = 'yesterday', $end_date = 'yesterday' ) {
        $ga          = Google_Analytics::get_instance();
        $date_range  = $ga->get_date_range( $start_date, $end_date );

        $metric_args = [
            'ga:totalEvents' => '',
        ];
        $metrics = $ga->get_metrics( $metric_args );

        $date_range_arr        = $this->get_date_period( $start_date, $end_date );
        $dimension_filter_args = [];
        foreach ( $date_range_arr as $date ) {
            $expression = '/' . $date->format( 'Y/m/d' );
            $dimension_filter_args[] = [
                'name'        => 'ga:pagePath',
                'operator'    => 'BEGINS_WITH',
                'expressions' => [ $expression ],
            ];
        }
        $dimension_filter_clause = $ga->get_dimension_filters( $dimension_filter_args );
        $data                    = $ga->make_request([
            'date_range'              => [ $date_range ],
            'metrics'                 => [ $metrics ],
            'dimensions'              => [
                $ga->get_dimension( 'ga:pagePath' ),
                $ga->get_dimension( 'ga:eventCategory' ),
                $ga->get_dimension( 'ga:eventAction' ),
            ],
            'page_size'               => '1000',
            'dimension_filter_clause' => $dimension_filter_clause,
            'order_by'                => [
                'fieldName' => 'ga:totalEvents',
                'sortOrder' => 'DESCENDING',
            ],
        ]);
        return $ga->format_output( $data );
    }

    /**
     * Process and transform the data so we can calculate the engaged completion rate
     *
     * Note: Google Analytics doesn't like multiple dimension filters so we need to do some filtering on our own here
     *
     * @param  object  $raw_data The raw data from Google Analytics to process
     * @return array             Completion rate data
     */
    public function process_engaged_completion_data( $raw_data = [] ) {
        // We need to filter out event categories we don't want
        $raw_data = array_filter( $raw_data, function( $obj ) {
            if ( 'Scroll Depth' == $obj->{'ga:eventCategory'} ) {
                return true;
            }
            return false;
        });

        // Transform and tabulate the output
        $output = [];
        foreach ( $raw_data as $row ) {
            $page                       = $row->{'ga:pagePath'};
            $action                     = $row->{'ga:eventAction'};
            $value                      = $row->{'ga:totalEvents'};
            $output[ $page ][ $action ] = $value;
            if (
                ! empty( $output[ $page ]['0%'] ) &&
                ! empty( $output[ $page ]['100%'] )
            ) {
                $percentage                    = $output[ $page ]['100%'] / $output[ $page ]['0%'];
                $percentage                    = $percentage * 100;
                $output[ $page ]['percentage'] = $percentage;
            }
        }

        // Filter out 100% rates that were only one person
        $output = array_filter( $output, function( $item ) {
            if ( ! isset( $item['0%'] ) ) {
                return true;
            }
            if ( ! isset( $item['100%'] ) ) {
                return true;
            }

            if ( 1 == $item['0%'] && 1 == $item['100%'] ) {
                return false;
            }
            return true;
        });

        // Sort the output by the percentage in descending order
        uasort( $output, function( $a, $b ) {
            if ( ! isset( $a['percentage'] ) ) {
                return 1;
            }
            if ( ! isset( $b['percentage'] ) ) {
                return -1;
            }
            return $b['percentage'] - $a['percentage'];
        });

        return $output;
    }


    /**
     * Truncate a number in the thousands
     * 1,234 => 1K
     *
     * @param  integer $num The number to truncate
     * @return string       The truncated number
     */
    public function truncate_number( $num = 0 ) {
        $bum = intval( $num );
        if ( $num > 999 && $num <= 999999 ) {
            return floor( $num / 1000 ) . 'K';
        }
        return $num;
    }

    /**
     * Truncate a string to the given number of characters
     *
     * @param  string  $str    The string to truncate
     * @param  integer $cutoff The character limit of the string to truncate
     * @return string          Truncated string
     */
    public function truncate_string( $str = '', $cutoff = 30 ) {
        if ( strlen( $str ) > $cutoff ) {
            $str = substr( $str, 0 , $cutoff ) . '...';
        }
        return $str;
    }

    /**
     * Get an array of date objects by a specified interval
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @param  string $interval   ISO 8601 duration string
     * @return array              Array of date objects
     */
    public function get_date_period( $start_date = '7daysAgo', $end_date = 'yesterday', $interval = 'P1D' ) {
        return new \DatePeriod(
            new \DateTime( $start_date ),
            new \DateInterval( $interval ), // 1 day See https://en.wikipedia.org/wiki/ISO_8601#Durations
            new \DateTime( $end_date )
        );
    }

    /**
     * Compile all the data and form a message to send to Slack
     */
    public function send() {
        $notifications = new Notifications;

        $start_date              = '7daysAgo';
        $end_date                = 'yesterday';
        $sessions                = $this->get_session_data( $start_date, $end_date );
        $total_sessions          = $this->truncate_number( $sessions->total_visitors );
        $total_pageviews         = $this->get_pageviews( $start_date, $end_date );
        $total_pageviews         = $this->truncate_number( $total_pageviews );
        $new_visitors_percentage = $sessions->new_visitors / $sessions->total_visitors * 100;
        $new_visitors_percentage = round( $new_visitors_percentage );

        $message = [
            'Here’s our weekly reader report!',
            '',
            $total_sessions . ' sessions (viewing ' . $total_pageviews . ' pages)',
            $new_visitors_percentage . '% for the first time',
            '',
        ];

        $message[] = 'By page views, the top five stories';
        $top_page_views = $this->get_top_pageview_data( $start_date, $end_date );
        foreach ( $top_page_views as $item ) {
            $path      = $item->{'ga:pagePath'};
            $pageviews = intval( $item->{'ga:pageviews'} );
            $link_url  = untrailingslashit( get_site_url() ) . $path;
            $link_text = $this->truncate_string( $path, 30 );
            $link      = '<' . $link_url . '|' . $link_text . '>';
            $message[] = $pageviews . ' ' . $link;
        }

        $message[] = '';

        $what_link            = '<https://docs.google.com/a/spiritedmedia.com/document/d/1tA8owu22ucLyxS9RQuAeTT_L4bAKMJcU1d1uqaHTtYo/edit?usp=sharing|what>';
        $message[]            = 'By engaged completion rate (' . $what_link . '?), the top five stories';
        $completion_rate_data = $this->get_engaged_completion_data( $start_date, $end_date );
        $completion_rate      = $this->process_engaged_completion_data( $completion_rate_data );
        $completion_rate      = array_slice( $completion_rate, 0, 10 );
        foreach ( $completion_rate as $path => $item ) {
            $rate        = round( $item['percentage'] ) . '%';
            $numerator   = $this->truncate_number( $item['100%'] );
            $denominator = $this->truncate_number( $item['0%'] );
            $link_url    = untrailingslashit( get_site_url() ) . $path;
            $link_text   = $this->truncate_string( $path, 30 );
            $link        = '<' . $link_url . '|' . $link_text . '>';
            $message[]   = $rate . ' (' . $numerator . '/' . $denominator . ') ' . $link;
        }

        $message = implode( "\n", $message );
        $slack_args = [
            'username'    => 'Spirit',
            'icon_emoji'  => ':ghost:',
            'channel'     => PEDESTAL_SLACK_CHANNEL_CITY,
        ];

        return $notifications->send( $message, $slack_args );
    }
}
