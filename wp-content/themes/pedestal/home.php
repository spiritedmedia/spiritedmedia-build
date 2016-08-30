<?php

use Timber\Timber;

use \Pedestal\Posts\Post;

$context = Timber::get_context();

$posts = Timber::get_posts( false, 'WP_Post' );
$items = Post::get_posts( $posts );
$context['items'] = $items;

Timber::render( 'home.twig', $context );
