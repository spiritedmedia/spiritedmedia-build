<?php

use Timber\Timber;

use Pedestal\Posts\Entities\Event;
use Pedestal\Posts\Slots\Slots;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Event::get_by_post_id( $p->ID );

$context['heading'] = [];
$context['heading']['details'] = esc_html__( 'Details', 'pedestal' );

Timber::render( 'single-event.twig', $context );
