<?php

use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$item    = Post::get( get_queried_object() );
$context = Timber::get_context();

if ( Types::is_cluster( $item ) ) {
    $context['items'] = $item->get_posts();
}

header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=utf-8', true );

Timber::render( 'feed.xml.twig', $context );
