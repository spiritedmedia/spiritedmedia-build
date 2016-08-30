<?php

use Timber\Timber;

use Pedestal\Posts\Entities\Event;

$context = Timber::get_context();
$post = Timber::query_post();
$event = Event::get_by_post_id( $post->ID );
$context['item'] = $event;
$filename = $event->get_name() . '.ics';

header( 'Content-type: text/calendar; charset=utf-8' );
header( 'Content-Disposition: attachment; filename=' . $filename );
Timber::render( 'single-event-ics.twig', $context );
