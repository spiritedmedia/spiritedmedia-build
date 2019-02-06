<?php
namespace Pedestal\MetricBot;

use Pedestal\Objects\{
    MailChimp,
    Google_Analytics,
    Notifications
};
use Pedestal\Utils\Utils;
use Pedestal\Posts\Newsletter;

class Yesterdays_Email_Metric {

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
        $timestamp = 'tomorrow 12:01am ' . get_option( 'timezone_string' );
        $timestamp = strtotime( $timestamp );

        $events['metricbot_yesterdays_email'] = [
            'timestamp'  => $timestamp,
            'recurrence' => 'daily',
            'callback'   => [ $this, 'send' ],
        ];
        return $events;
    }

    public function get_data() {
        $mc              = MailChimp::get_instance();
        $since_send_time = new \DateTime( '1dayAgo' );
        $campaign_args   = [
            'since_send_time' => $since_send_time->format( 'c' ),
            'sort_field'      => 'send_time',
            'sort_dir'        => 'DESC',
            'count'           => 100,
        ];
        $group_name      = 'Daily Newsletter';
        $group_category  = 'Newsletters';
        $raw_campaigns   = $mc->get_campaigns_by_group(
            $group_name,
            $group_category,
            $campaign_args
        );
        $campaigns       = [];
        foreach ( $raw_campaigns as $campaign ) {
            $report_url      = $mc->get_admin_url( '/reports/summary?id=' . $campaign->web_id );
            $raw_link_clicks = $mc->get_campaign_link_clicks( $campaign->id, [
                'count' => 100,
            ] );

            // Need to de-dupe and normalize the link clicks
            $link_clicks = [];
            foreach ( $raw_link_clicks as $link ) {
                // Discard ?utm query strings to normalize URLs
                $de_duped_url = explode( '?utm', $link->url )[0];
                if ( empty( $link_clicks[ $de_duped_url ] ) ) {
                    $link_clicks[ $de_duped_url ] = (object) [
                        'url'           => $de_duped_url,
                        'total_clicks'  => 0,
                        'unique_clicks' => 0,
                        'campaign_id'   => $campaign->id,
                    ];
                }
                $link_clicks[ $de_duped_url ]->total_clicks  += $link->total_clicks;
                $link_clicks[ $de_duped_url ]->unique_clicks += $link->unique_clicks;
            }

            // Convert associative array into index so they can be sorted
            $link_clicks = array_values( $link_clicks );
            $link_clicks = Utils::sort_obj_array_by_prop( $link_clicks, 'unique_clicks' );
            // Sort in descending order
            $link_clicks = array_reverse( $link_clicks );

            $unsubscribes = $mc->get_campaign_unsubscribes( $campaign->id );

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
                'link_clicks'       => $link_clicks,
                'unsubscribes'      => $unsubscribes,
            ];
        }
        return $campaigns;
    }

    public function get_message() {
        $data = $this->get_data();
        if ( ! is_array( $data ) || ! isset( $data[0] ) ) {
            return false;
        }
        $campaign = $data[0];

        $open_rate   = $campaign->unique_opens / $campaign->recipient_count * 100;
        $click_rate  = $campaign->click_rate * 100;
        $link_clicks = array_slice( $campaign->link_clicks, 0, 10 );

        $newsletter_link = Newsletter::get_yesterdays_newsletter_link();
        $label           = 'yesterday\'s newsletter';
        if ( $newsletter_link ) {
            $label = '<' . $newsletter_link . '|' . $label . '>';
        }
        $message = [
            'Here\'s how ' . $label . ' performed!',
            '',
            $campaign->subject,
            '',
            number_format( $campaign->sent_to ) . ' recipients',
            round( $open_rate, 1 ) . '% opened the email',
            round( $click_rate, 1 ) . '% clicked a link',
            number_format( count( $campaign->unsubscribes ) ) . ' unsubscribed',
            '',
            'The top ' . count( $link_clicks ) . ' links clicked',
        ];

        $link_cutoff = 30;
        // Sometimes get_site_url() returns the wrong URL scheme for an environment
        // but for our purposes we want to replace both http and https versions of get_site_url()
        $needles = [
            set_url_scheme( get_site_url(), 'http' ),
            set_url_scheme( get_site_url(), 'https' ),
        ];
        foreach ( $link_clicks as $link ) {
            $link_url = $link->url;
            // Trim off UTM query parameters
            $link_url = explode( '?utm', $link_url )[0];

            $link_text = $link_url;
            $link_text = str_replace( $needles, '', $link_text );
            if ( strlen( $link_url ) > $link_cutoff ) {
                $link_text  = substr( $link_text, 0, $link_cutoff );
                $link_text .= '...';
            }

            $message[] = $link->unique_clicks . ' <' . $link_url . '|' . $link_text . '>';
        }
        $message[] = '';
        $message[] = '(Full report in <' . $campaign->report_url . '|MailChimp>)';

        return implode( "\n", $message );
    }

    /**
     * Compile all the data and form a message to send to Slack
     */
    public function send() {
        $notifications = new Notifications;
        $message       = $this->get_message();
        if ( ! $message ) {
            return;
        }
        $slack_args = [
            'username'   => 'Spirit',
            'icon_emoji' => ':ghost:',
            'channel'    => PEDESTAL_SLACK_CHANNEL_NEWSLETTER,
        ];
        $notifications->send( $message, $slack_args );
    }
}
