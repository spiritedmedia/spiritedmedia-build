<?php

use Timber\Timber;
use \Pedestal\Posts\Clusters;
use \Pedestal\Objects\ActiveCampaign;
use \Pedestal\Email\Email_Lists;

$context = Timber::get_context();

$key = get_query_var( 'pedestal-confirm-subscription' );
$provided_list_ids = [];
$subscription_type = '';
if ( ! empty( $_GET['list_ids'] ) ) {
    $provided_list_ids = sanitize_text_field( $_GET['list_ids'] );
    $provided_list_ids = explode( ',', $provided_list_ids );
    $provided_list_ids = array_map( 'intval', $provided_list_ids );
    $subscription_type = 'newsletter';
    $context['provided_list_ids'] = $provided_list_ids;
}
$transient_key = 'pending_email_confirmation_' . $key;
$data = get_transient( $transient_key );
if ( $data ) {
    if ( isset( $data['email'] ) && isset( $data['list_ids'] ) ) {
        $activecampaign = new ActiveCampaign;
        $result = $activecampaign->subscribe_contact( $data['email'], $data['list_ids'] );
        if ( $result ) {
            delete_transient( $transient_key );
            Timber::render( 'emails/pages/confirm-subscription.twig', $context );
            exit;
        }
    }
}

$clusters = Email_Lists::get_clusters_from_list_ids( $provided_list_ids );
$subscription_title = '';
if ( 1 == count( $clusters ) ) {
    $cluster = $clusters[0];
    $subscription_type = $cluster->get_type();
    $subscription_title = $cluster->get_title();
}
$help_text = 'Help! I\'m trying to subscribe to ';
$context['list_ids'] = $provided_list_ids;
$context['list_id_str'] = implode( ',', $provided_list_ids );
$context['is_newsletter'] = false;
$context['is_cluster'] = false;
switch ( $subscription_type ) {
    case 'newsletter':
        $context['is_newsletter'] = true;
        $context['help_text'] = $help_text . 'the Newsletter';
        break;
    default:
        $context['is_cluster'] = true;
        $context['subscription_title'] = $subscription_title;
        $context['help_text'] = $help_text . $subscription_title;
        break;
}
Timber::render( 'emails/pages/confirm-subscription-fail.twig', $context );
