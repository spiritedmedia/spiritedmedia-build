<?php

use Timber\Timber;
use Pedestal\Objects\Stream;
use Pedestal\Featured_Posts;
use Pedestal\Adverts;

$stream         = new Stream;
$featured_posts = Featured_Posts::get_instance();

$context = Timber::get_context();
if ( get_query_var( 'paged' ) ) {
    $context['page_title'] = 'More Stories';
} else {
    $context['featured_stream_items'] = $featured_posts->get_the_featured_posts();
    $context['stream_header_city']    = '';
    switch ( PEDESTAL_THEME_NAME ) {
        case 'billy-penn':
            $context['stream_header_city'] = PEDESTAL_CITY_NICKNAME;
            break;
        default:
            $context['stream_header_city'] = PEDESTAL_CITY_NAME;
    }
}
$context['stream']     = $stream->get_the_stream();
$context['pagination'] = $stream->get_load_more_button();

if ( is_active_sidebar( 'sidebar-homepage' ) ) {
    $context['sidebar'] = Timber::get_widgets( 'sidebar-homepage' );
}

Timber::render( 'home.twig', $context );
