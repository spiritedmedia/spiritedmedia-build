<?php

use Timber\Timber;
use Pedestal\Posts\Post;

global $wp_query;

$context          = Timber::get_context();
$context['items'] = Post::get_posts_from_query( $wp_query );

header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=utf-8', true );

Timber::render( 'feed.xml.twig', $context );
