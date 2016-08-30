<?php

use Timber\Timber;

use Pedestal\Posts\Newsletter;
use Pedestal\Posts\Slots\Slots;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Newsletter::get_by_post_id( $p->ID );

Timber::render( 'single-newsletter.twig', $context );
