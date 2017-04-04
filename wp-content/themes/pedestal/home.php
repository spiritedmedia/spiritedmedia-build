<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

global $wp_query;

$context = Timber::get_context();
$context['items'] = Stream::get( $wp_query->posts );

Timber::render( 'home.twig', $context );
