<?php

use Timber\Timber;

use Pedestal\Posts\Page;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Page::get_by_post_id( $p->ID );

$context['sidebar'] = false;

Timber::render( 'single-page.twig', $context );
