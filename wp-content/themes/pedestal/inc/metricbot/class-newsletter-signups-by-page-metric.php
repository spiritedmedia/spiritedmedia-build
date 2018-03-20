<?php
namespace Pedestal\Metricbot;

use Pedestal\Objects\{
    Google_Analytics,
    Notifications
};
use Pedestal\Email\Newsletter_Groups;
use Pedestal\Utils\Utils;

class Newsletter_Signups_By_Page_Metric {

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
     * Hook in to WordPress via filters
     */
    public function setup_filters() {
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
        $timestamp = 'friday 8:00am ' . get_option( 'timezone_string' );
        $timestamp = strtotime( $timestamp );

        $events['metricbot_newsletter_signups_by_page'] = [
            'timestamp'  => $timestamp,
            'recurrence' => 'weekly',
            'callback'   => [ $this, 'send' ],
        ];
        return $events;
    }

    /**
     * Get newsletter signup events data for a given time frame
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @return array              Newsletter signup data
     */
    public function get_newsletter_signups_by_page_data( $start_date = 'yesterday', $end_date = 'yesterday' ) {
        $ga = Google_Analytics::get_instance();
        $date_range = $ga->get_date_range( $start_date, $end_date );

        $metric_args = [
            'ga:totalEvents' => '',
        ];
        $metrics = $ga->get_metrics( $metric_args );
        $dimension_filter_args = [
            [
                'name'        => 'ga:eventLabel',
                'operator'    => 'EXACT',
                'expressions' => [ 'Newsletter Signup Widget', 'Newsletter Signup Page' ],
            ],
        ];
        $dimension_filter_clause = $ga->get_dimension_filters( $dimension_filter_args );

        $data = $ga->make_request([
            'date_range'              => [ $date_range ],
            'metrics'                 => [ $metrics ],
            'dimensions'              => [ $ga->get_dimension( 'ga:pagePath' ) ],
            'page_size'               => '20',
            'dimension_filter_clause' => $dimension_filter_clause,
            'order_by'                => [
                'fieldName' => 'ga:totalEvents',
                'sortOrder' => 'DESCENDING',
            ],
        ]);
        return $ga->format_output( $data );
    }

    /**
     * Get session data by page path
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @param  array  $page_paths Page paths to get session data for
     * @return array              Session data
     */
    public function get_sessions_by_page( $start_date = 'yesterday', $end_date = 'yesterday', $page_paths = [] ) {
        $ga = Google_Analytics::get_instance();
        $date_range = $ga->get_date_range( $start_date, $end_date );

        $metric_args = [
            'ga:sessions' => '',
        ];
        $metrics = $ga->get_metrics( $metric_args );

        // Construct a regular expression of page paths
        // to limit the data returned to just those pages
        $dimension_filter_expression = '(';
        foreach ( $page_paths as $path ) {
            $dimension_filter_expression .= '^' . $path . '$|';
        }
        $dimension_filter_expression = rtrim( $dimension_filter_expression, '|' );
        $dimension_filter_expression .= ')';
        $dimension_filter_args = [
            [
                'name'        => 'ga:pagePath',
                'operator'    => 'REGEXP',
                'expressions' => [ $dimension_filter_expression ],
            ],
        ];
        $dimension_filter_clause = $ga->get_dimension_filters( $dimension_filter_args );

        $data = $ga->make_request([
            'date_range'              => [ $date_range ],
            'metrics'                 => [ $metrics ],
            'dimensions'              => [ $ga->get_dimension( 'ga:pagePath' ) ],
            'page_size'               => '20',
            'dimension_filter_clause' => $dimension_filter_clause,
            'order_by'                => [
                'fieldName' => 'ga:sessions',
                'sortOrder' => 'DESCENDING',
            ],
        ]);
        $data = $ga->format_output( $data );

        // We need to transform the output to make it easier to look up a session value
        // using the page path as a key
        $output = [];
        foreach ( $data as $item ) {
            $path            = $item->{'ga:pagePath'};
            $val             = $item->{'ga:sessions'};
            $output[ $path ] = $val;
        }
        return $output;
    }

    /**
     * Get newsletter subscriber stats from ActiveCampaign by fetching campaigns
     * sent between the given dates and comparing the sent_to numbers to determine a trend
     *
     * @param  string $start_date A date further in the past
     * @param  string $end_date   A date closer to the present
     * @return object              Newsletter subscriber data
     */
    public function get_newsletter_subscriber_stats( $start_date = '', $end_date = '' ) {
        $ac = ActiveCampaign::get_instance();
        $newsletter_groups = Newsletter_Groups::get_instance();

        $list_id           = $newsletter_groups->get_newsletter_group_id( 'Daily Newsletter' );
        $args              = [
            'lists'      => [ $list_id ],
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'max_pages'  => 10,
        ];
        $emails            = $ac->get_camapigns( $args );
        $emails_reversed   = array_reverse( $emails );
        $latest_email      = $emails[0];
        $last_email        = $emails_reversed[0];

        $total_subscribers = intval( $latest_email->sent_to );
        $diff              = $total_subscribers - intval( $last_email->sent_to );
        $trend             = 'up';

        if ( $diff < 0 ) {
            $trend = 'down';
            $diff  = abs( $diff );
        }

        return (object) [
            'total' => intval( $latest_email->sent_to ),
            'trend' => $trend,
            'diff'  => $diff,
        ];
    }

    /**
     * Compile all the data and form a message to send to Slack
     */
    public function send() {
        $notifications = new Notifications;

        $start_date       = '7daysAgo';
        $end_date         = 'yesterday';
        $ga_data          = $this->get_newsletter_signups_by_page_data( $start_date, $end_date );
        $page_paths       = [];
        foreach ( $ga_data as $item ) {
            $page_paths[] = $item->{'ga:pagePath'};
        }
        $sessions         = $this->get_sessions_by_page( $start_date, $end_date, $page_paths );
        // $subscriber_stats = $this->get_newsletter_subscriber_stats( $end_date, $start_date );

        $total_signups    = 0;
        $output = [];
        foreach ( $ga_data as $item ) {
            $path          = $item->{'ga:pagePath'};
            $page_paths[]  = $path;
            $signups       = intval( $item->{'ga:totalEvents'} );
            $total_signups += $signups;
            $pageview      = 0;
            $ratio         = 0;
            $conversion    = 0;

            if ( isset( $sessions[ $path ] ) ) {
                $pageview = intval( $sessions[ $path ] );
                if ( $signups > 0 ) {
                    $ratio = $pageview / $signups;
                }

                if ( $pageview > 0 ) {
                    $conversion = $signups / $pageview * 100;
                }
            }

            $link_url   = untrailingslashit( get_site_url() ) . $path;
            $link_text  = $path;
            if ( '/' == $link_text ) {
                $link_text = get_site_url();
            }
            $link_cutoff = 30;
            if ( strlen( $link_text ) > $link_cutoff ) {
                $link_text = substr( $link_text, 0 , $link_cutoff ) . '...';
            }
            $link = '<' . $link_url . '|' . $link_text . '>';

            $output[] = (object) [
                'link_url'   => $link_url,
                'link_text'  => $link_text,
                'link'       => $link,
                'path'       => $path,
                'signups'    => $signups,
                'pageviews'  => $pageview,
                'ratio'      => round( $ratio, 1 ),
                'conversion' => round( $conversion ),
            ];
        }

        $output_by_signups     = array_reverse( Utils::sort_obj_array_by_prop( $output, 'signups' ) );
        $output_by_conversions = array_reverse( Utils::sort_obj_array_by_prop( $output, 'conversion' ) );
        $message = [
            'Here\'s how we did this week at building our subscriber list!',
            '',
        ];

        $message[] = 'The top performers (subscriptions/session)';
        foreach ( $output_by_conversions as $item ) {
            if ( $item->conversion <= 1 ) {
                continue;
            }
            $signups   = number_format( $item->signups );
            $sessions  = number_format( $item->pageviews );
            $stats     = '(' . $signups . '/' . $sessions . ')';
            $message[] = $item->conversion . '% ' . $stats . ' ' . $item->link;
        }
        $message[] = '';

        $message[] = 'Where people subscribed';
        foreach ( $output_by_signups as $item ) {
            $signups   = number_format( $item->signups );
            $message[] = $signups . ' ' . $item->link;
        }

        // Will be reimplemented after our move to MailChimp. See #2425
        /*
        $message[] = '';
        $total     = number_format( $subscriber_stats->total );
        $trend     = $subscriber_stats->trend;
        $punct     = '.';
        if ( 'up' == $trend ) {
            $punct = '!';
        }
        $diff      = $subscriber_stats->diff;
        $message[] = $total . ' subscribers, ' . $trend . ' ' . $diff . ' from last week' . $punct;
        */

        $message = implode( "\n", $message );
        $slack_args = [
            'username'    => 'Spirit',
            'icon_emoji'  => ':ghost:',
            'channel'     => PEDESTAL_SLACK_CHANNEL_NEWSLETTER,
        ];
        return $notifications->send( $message, $slack_args );
    }
}
