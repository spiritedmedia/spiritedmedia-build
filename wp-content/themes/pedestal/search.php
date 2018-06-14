<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

global $wp_query;

$stream = new Stream;

$context = Timber::get_context();

add_filter( 'pedestal_stream_item_context', function( $context ) {
    $context['featured_image'] = '';
    $context['thumbnail_image'] = '';
    return $context;
} );

$context['stream'] = $stream->get_the_stream();
$context['pagination'] = $stream->get_load_more_button();
$context['found_results'] = $wp_query->found_posts;
$context['search_query'] = get_search_query();

Timber::render( 'search.twig', $context );
