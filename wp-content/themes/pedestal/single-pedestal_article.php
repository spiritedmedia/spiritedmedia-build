<?php

use Timber\Timber;

use Pedestal\Posts\Post;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Post::get_by_post_id( $p->ID );

Timber::render( 'single-article.twig', $context );
