<?php

use Timber\Timber;

use Pedestal\Posts\Entities\Embed;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Embed::get_by_post_id( $p->ID );

Timber::render( 'single-embed.twig', $context );
