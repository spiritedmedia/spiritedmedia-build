<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

global $wp_query;

$context = Timber::get_context();
$context['items'] = Stream::get( $wp_query->posts );

header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=utf-8', true );

Timber::render( 'feed.xml.twig', $context );
