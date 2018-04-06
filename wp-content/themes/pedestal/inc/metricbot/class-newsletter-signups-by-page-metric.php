<?php
namespace Pedestal\MetricBot;

use Pedestal\Objects\{
    MailChimp,
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
                'operator'    => 'BEGINS_WITH',
                'expressions' => [ 'Newsletter Signup' ],
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
        $mc = MailChimp::get_instance();
        $since_send_time = new \DateTime( $start_date );
        $campaign_args = [
            'since_send_time' => $since_send_time->format( 'c' ),
            'sort_field'      => 'send_time',
            'sort_dir'        => 'DESC',
            'count'           => 100,
        ];
        $group_name = 'Daily Newsletter';
        $group_category = 'Newsletters';
        $raw_campaigns = $mc->get_campaigns_by_group(
            $group_name,
            $group_category,
            $campaign_args
        );
        $campaigns = [];
        foreach ( $raw_campaigns as $campaign ) {
            $report_url = $mc->get_admin_url( '/reports/summary?id=' . $campaign->web_id );

            $campaigns[] = (object) [
                'report_url'        => $report_url,
                'send_time'         => $campaign->send_time,
                'sent_to'           => $campaign->emails_sent,
                'recipient_count'   => $campaign->recipients->recipient_count,
                'subject'           => $campaign->settings->subject_line,
                'opens'             => $campaign->report_summary->opens,
                'unique_opens'      => $campaign->report_summary->unique_opens,
                'clicks'            => $campaign->report_summary->clicks,
                'subscriber_clicks' => $campaign->report_summary->subscriber_clicks,
                'click_rate'        => $campaign->report_summary->click_rate,
            ];
        }
        if ( empty( $campaigns[0] ) ) {
            return (object) [
                'total'     => 0,
                'trend'     => 'down',
                'diff'      => 0,
                'campaigns' => [],
            ];
        }
        $campaigns_reversed = array_reverse( $campaigns );
        $latest_campaign    = $campaigns[0];
        $last_campaign      = $campaigns_reversed[0];

        $total_subscribers  = intval( $latest_campaign->sent_to );
        $diff               = $total_subscribers - intval( $last_campaign->sent_to );
        $trend              = 'up';

        if ( $diff < 0 ) {
            $trend = 'down';
            $diff  = abs( $diff );
        }

        return (object) [
            'total'     => $total_subscribers,
            'trend'     => $trend,
            'diff'      => $diff,
            'campaigns' => $campaigns,
        ];
    }

    public function get_data( $start_date = '7daysAgo', $end_date = 'yesterday' ) {
        $ga_data          = $this->get_newsletter_signups_by_page_data( $start_date, $end_date );
        $page_paths       = [];
        foreach ( $ga_data as $item ) {
            $page_paths[] = $item->{'ga:pagePath'};
        }
        $sessions         = $this->get_sessions_by_page( $start_date, $end_date, $page_paths );

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
        return $output;
    }

    public function get_message() {
        $start_date            = '7daysAgo';
        $end_date              = 'yesterday';

        $output                = $this->get_data( $start_date, $end_date );
        $output_by_signups     = array_reverse( Utils::sort_obj_array_by_prop( $output, 'signups' ) );
        $output_by_conversions = array_reverse( Utils::sort_obj_array_by_prop( $output, 'conversion' ) );
        $subscriber_stats      = $this->get_newsletter_subscriber_stats( $start_date, $end_date );

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

        $message[] = '';
        $total     = number_format( $subscriber_stats->total );
        $trend     = $subscriber_stats->trend;
        $punct     = '.';
        if ( 'up' == $trend ) {
            $punct = '!';
        }
        $diff      = $subscriber_stats->diff;
        $message[] = $total . ' subscribers, ' . $trend . ' ' . $diff . ' from last week' . $punct;

        return implode( "\n", $message );
    }

    /**
     * Compile all the data and form a message to send to Slack
     */
    public function send() {
        $notifications = new Notifications;
        $message = $this->get_message();
        if ( ! $message ) {
            return;
        }
        $slack_args = [
            'username'    => 'Spirit',
            'icon_emoji'  => ':ghost:',
            'channel'     => PEDESTAL_SLACK_CHANNEL_NEWSLETTER,
        ];
        return $notifications->send( $message, $slack_args );
    }
}
