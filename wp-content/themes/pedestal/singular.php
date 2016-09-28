<?php

use Timber\Timber;
use Pedestal\Posts\Post;

$post = Timber::query_post();
$item = Post::get_by_post_id( $post->ID );

$templates = [];
if ( is_a( $item, '\\Pedestal\\Posts\\Post' ) ) {
    $templates[] = 'single-' . $item->get_type() . '.twig';

    if ( $item->is_cluster() ) {
        $templates[] = 'single-cluster.twig';
    }
}
$templates[] = 'single.twig';

$context = Timber::get_context();
$context['item'] = $item;

Timber::render( $templates, $context );
