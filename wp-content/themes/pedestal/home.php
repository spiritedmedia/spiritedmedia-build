<?php

use Timber\Timber;
use Pedestal\Objects\Stream;
use Pedestal\Featured_Posts;

$stream = new Stream;
$featured_posts = Featured_Posts::get_instance();

$context = Timber::get_context();
if ( ! get_query_var( 'paged' ) ) {
    $context['featured_stream_items'] = $featured_posts->get_the_featured_posts();
    $context['stream_header_city'] = '';
    switch ( PEDESTAL_THEME_NAME ) {
        case 'billy-penn':
            $context['stream_header_city'] = PEDESTAL_CITY_NICKNAME;
            break;
        default:
            $context['stream_header_city'] = PEDESTAL_CITY_NAME;
    }
}
$context['stream'] = $stream->get_the_stream();
$context['pagination'] = $stream->get_pagination( [
    'show_text' => false,
] );

if ( is_active_sidebar( 'sidebar-stream' ) ) {
    $context['sidebar'] = Timber::get_widgets( 'sidebar-stream' );
}

Timber::render( 'home.twig', $context );
