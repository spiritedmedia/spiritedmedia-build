<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

global $wp_query;

$stream = new Stream;

$context = Timber::get_context();
$context['stream'] = $stream->get_the_stream();
$context['pagination'] = $stream->get_pagination( [
    'show_text' => false,
] );
$context['found_results'] = $wp_query->found_posts;
$context['search_query'] = get_search_query();

Timber::render( 'search.twig', $context );
