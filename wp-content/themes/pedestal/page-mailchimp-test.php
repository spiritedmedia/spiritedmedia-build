<?php

use Pedestal\MetricBot;
use Pedestal\Objects\ActiveCampaign;

function wp_dump() {
    foreach ( func_get_args() as $arg ) {
        echo '<xmp>';
        var_dump( $arg );
        echo '</xmp>';
    }
}

/*
$bot = MetricBot::get_instance();
wp_dump( $bot->newsletter_signups_by_page() );
*/
$newsletter_id = 320;
$ac = new ActiveCampaign;
$date_cutoff = strtotime( '8daysago' );

$keep_looping = true;
$page = 1;
$emails = [];
while ( $keep_looping ) {

    $campaigns = $ac->get_campaign([
        'ids' => 'all',
        'filters[sdate_since_datetime]' => date( 'Y-m-d H:i:s', strtotime( '8daysago' ) ),
        'page' => $page,
    ]);
    foreach ( $campaigns as $campaign ) {
        if ( $newsletter_id != $campaign->lists[0]->id ) {
            continue;
        }
        $emails[] = (object) [
            'name' => $campaign->name,
            'unsubscribes' => intval( $campaign->unsubscribes ),
            'sent_to' => intval( $campaign->total_amt ),
            'sdate' => $campaign->sdate,
            'ldate' => $campaign->ldate,
        ];
        if ( strtotime( $campaign->sdate ) < $date_cutoff ) {
            $keep_looping = false;
        }
    }

    $page++;
    if ( $page > 10 ) {
        $keep_looping = false;
    }
}
$emails = array_reverse( $emails );
$output = [];
foreach ( $emails as $email ) {
    $subscribes = 0;
    $total_before_sent = $email->sent_to;
    $unsubscribes = $email->unsubscribes;
    $new_total = $total_before_sent - $unsubscribes;
    $output[] = (object) [
        'subscribes' => $subscribes,
        'unsubscribes' => $unsubscribes,
        'total' => $total_before_sent,
        'new_ttoal' => $new_total,
        'date' => $email->sdate,
        'subject' => $email->name,
    ];
}
wp_dump( $output );
