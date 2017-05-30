<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

global $wp_query;

$context = Timber::get_context();
$context['items'] = Stream::get( $wp_query->posts );

if ( $sponsored_items = Stream::get_sponsored_items() ) {
	$context['sponsored_items'] = $sponsored_items;
}

Timber::render( 'home.twig', $context );
