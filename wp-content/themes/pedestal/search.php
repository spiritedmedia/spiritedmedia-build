<?php

use Timber\Timber;

use \Pedestal\Posts\Post;

global $wp_query;

$context = Timber::get_context();
$posts = Timber::get_posts( false, 'WP_Post' );
$items = Post::get_posts( $posts );

$context['items'] = $items;
$context['found_results'] = $wp_query->found_posts;
$context['search_query'] = get_search_query();

$context['sort_by'] = ( ! empty( $_GET['orderby'] ) && 'date' == $_GET['orderby'] ) ? 'date' : 'relevance';
$context['sort_by_relevance_link'] = add_query_arg( 's', $_GET['s'], home_url( '/' ) );
$context['sort_by_date_link'] = add_query_arg( 'orderby', 'date', $context['sort_by_relevance_link'] );

Timber::render( 'search.twig', $context );
