<?php

use Timber\Timber;

use Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

use \Pedestal\Objects\Stream;

$context = Timber::get_context();
$args = [
    'post_type'      => Types::get_original_post_types(),
    'posts_per_page' => 10,
];
if ( is_singular() ) {
    $args['posts_per_page'] = 1;
    $args['name'] = get_query_var( 'pagename' );
}
$stream = new Stream( $args );

$context['items'] = $stream->get_stream();
$context['is_fias_feed'] = true;
header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=utf-8', true );

Timber::render( 'feed-fias.xml.twig', $context );
