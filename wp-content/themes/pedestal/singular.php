<?php

use Timber\Timber;
use Pedestal\Posts\Post;

$post = Timber::query_post();
$item = Post::get_by_post_id( $post->ID );

$context = Timber::get_context();

$templates = [];
if ( is_a( $item, '\\Pedestal\\Posts\\Post' ) ) {
    $templates[] = 'single-' . $item->get_type() . '.twig';

    if ( $item->is_cluster() ) {
        $templates[] = 'single-cluster.twig';
        $context['cluster'] = $item;
        $context['follow_text'] = 'follow this';
    }

    if ( $item->is_entity() && $item->has_story() ) {
        $context['cluster'] = $item->get_story();
        $context['follow_text'] = 'follow story';
    }
}
$templates[] = 'single.twig';

$context['item'] = $item;

Timber::render( $templates, $context );
