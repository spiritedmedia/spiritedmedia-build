<?php

use Timber\Timber;

use Pedestal\Posts\Post;
use Pedestal\Objects\User;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Post::get_by_post_id( $p->ID );
if ( wp_get_current_user() ) {
    $context['current_user'] = new User( wp_get_current_user() );
} else {
    $context['current_user'] = false;
}

$context['grouped'] = true;
$context['is_cluster'] = true;

$context['everyblock'] = $context['everyblock']['labels'] = [];
$context['everyblock']['labels']['section']       = esc_html( 'Section', 'pedestal' );
$context['everyblock']['labels']['location']      = esc_html( 'Location', 'pedestal' );
$context['everyblock']['labels']['date']          = esc_html( 'Date', 'pedestal' );
$context['everyblock']['labels']['dispatch_date'] = esc_html( 'Dispatch Date', 'pedestal' );
$context['everyblock']['labels']['dispatch_time'] = esc_html( 'Dispatch Time', 'pedestal' );

Timber::render( 'single-hood.twig', $context );
