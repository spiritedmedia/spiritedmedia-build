<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

$context = Timber::get_context();

$posts = Timber::get_posts( false, 'WP_Post' );
$items = Stream::get( $posts );
$context['items'] = $items;

Timber::render( 'home.twig', $context );
