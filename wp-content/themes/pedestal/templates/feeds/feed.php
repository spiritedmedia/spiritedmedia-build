<?php

use Timber\Timber;

use \Pedestal\Posts\Post;

$context = Timber::get_context();
$posts = Timber::get_posts( false, 'WP_Post' );
$items = [];
foreach ( $posts as $post ) {
    $items[] = Post::get_by_post_id( $post->ID );
}
$context['items'] = $items;

header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=utf-8', true );

Timber::render( 'feed.xml.twig', $context );
