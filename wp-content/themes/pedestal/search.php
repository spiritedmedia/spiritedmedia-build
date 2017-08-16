<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

global $wp_query;

$context = Timber::get_context();
$context['items'] = Stream::get( $wp_query->posts );
$context['found_results'] = $wp_query->found_posts;
if ( $wp_query->found_posts < 1 ) {
    $context['items'] = [];
}

$context['search_query'] = get_search_query();
$context['sort_by'] = ( ! empty( $_GET['orderby'] ) && 'date' == $_GET['orderby'] ) ? 'date' : 'relevance';
$context['sort_by_relevance_link'] = add_query_arg( 's', $_GET['s'], home_url( '/' ) );
$context['sort_by_date_link'] = add_query_arg( 'orderby', 'date', $context['sort_by_relevance_link'] );

Timber::render( 'search.twig', $context );
