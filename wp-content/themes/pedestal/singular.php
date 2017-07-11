<?php

use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Objects\Stream;

$p = Timber::query_post();
$item = Post::get_by_post_id( $p->ID );

$context = Timber::get_context();

$sponsored_items = Stream::get_sponsored_items();
if ( $sponsored_items ) {
    $context['sponsored_items'] = $sponsored_items;
}

$templates = [];
if ( is_a( $item, '\\Pedestal\\Posts\\Post' ) ) {
    $templates[] = 'single-' . $item->get_type() . '.twig';

    if ( $item->is_cluster() ) {
        $templates[] = 'single-cluster.twig';
        if ( is_active_sidebar( 'sidebar-stream' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-stream' );
        }
    }

    if ( $item->is_story() ) {
        $context['cluster'] = $item;
        $context['follow_text'] = 'follow this';
        if ( is_active_sidebar( 'sidebar-story' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-story' );
        }
    }

    if ( $item->is_entity() ) {
        if ( is_active_sidebar( 'sidebar-entity' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-entity' );
        }
    }

    if ( $item->is_entity() && $item->has_story() ) {
        $context['cluster'] = $item->get_primary_story();
        $context['follow_text'] = 'follow story';
    }
}
$templates[] = 'single.twig';

$context['item'] = $item;

Timber::render( $templates, $context );
