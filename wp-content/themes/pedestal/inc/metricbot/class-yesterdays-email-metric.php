<?php
namespace Pedestal\Metricbot;

use Pedestal\Objects\{
    Google_Analytics,
    Notifications
};
use Pedestal\Objects\ActiveCampaign;
use Pedestal\Email\Email_Lists;
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

    /**
     * Compile all the data and form a message to send to Slack
     */
    public function send() {
        $ac            = new ActiveCampaign;
        $email_lists   = new Email_Lists;
        $notifications = new Notifications;

        $newsletter_id = $email_lists->get_newsletter_list_id( 'Daily Newsletter' );
        $args          = [
            'lists'     => [ $newsletter_id ],
            'end_date'  => strtotime( '7daysago' ),
            'max_pages' => 10,
        ];
        $emails                          = $ac->get_camapigns( $args );
        $yesterdays_email                = $emails[1];
        if (
               ! $yesterdays_email
            || ! is_object( $yesterdays_email )
            || ! isset( $yesterdays_email->unique_opens )
        ) {
            return false;
        }
        $yesterdays_open_rate            = ( $yesterdays_email->unique_opens / $yesterdays_email->sent_to ) * 100;
        $yesterdays_click_through        = ( $yesterdays_email->clicks / $yesterdays_email->sent_to ) * 100;
        $yesterdays_campaign_report_link = 'https://spiritedmedia.activehosted.com/report/#/campaign/' . $yesterdays_email->id;
        $links                           = $ac->get_links_report( $yesterdays_email->id, $yesterdays_email->message_ids[0] );
        $link_clicks                     = [];
        foreach ( $links as $link ) {
            if ( $link->unique_clicks <= 0 ) {
                continue;
            }
            $link_cutoff  = 30;
            $link_text     = $link->url;
            $link_text     = str_replace( untrailingslashit( get_site_url() ), '', $link_text );
            if ( strlen( $link->url ) > $link_cutoff ) {
                $link_text = substr( $link_text, 0, $link_cutoff );
                $link_text .= '...';
            }

            $link_url      = '<' . $link->url . '|' . $link_text . '>';
            $link_clicks[] = $link->unique_clicks . ' ' . $link_url;
        }
        $link_clicks     = array_slice( $link_clicks, 0, 10 );
        $newsletter_link = Newsletter::get_yesterdays_newsletter_link();
        $label           = 'yesterday\'s newsletter';
        if ( $newsletter_link ) {
            $label = '<' . $newsletter_link . '|' . $label . '>';
        }

        $message = [
            'Here\'s how ' . $label . ' performed!',
            '',
            number_format( $yesterdays_email->sent_to ) . ' recipients',
            round( $yesterdays_open_rate, 2 ) . '% opened the email',
            round( $yesterdays_click_through, 2 ) . '% clicked a link',
            number_format( $yesterdays_email->unsubscribes ) . ' unsubscribed',
            '',
            'The top ' . count( $link_clicks ) . ' links clicked',
        ];
        foreach ( $link_clicks as $link ) {
            $message[] = $link;
        }
        $message[] = '';
        $message[] = '(Full report in <' . $yesterdays_campaign_report_link . '|Active Campaign>)';

        $message = implode( "\n", $message );
        $slack_args = [
            'username'    => 'Spirit',
            'icon_emoji'  => ':ghost:',
            'channel'     => PEDESTAL_SLACK_CHANNEL_NEWSLETTER,
        ];
        $notifications->send( $message, $slack_args );
    }
}
