<?php

use Timber\Timber;
use Pedestal\Adverts;
use Pedestal\Posts\Post;
use Pedestal\Objects\Stream;

$p = Timber::query_post();
$item = Post::get_by_post_id( $p->ID );

$context = Timber::get_context();

$adverts = new Adverts;
$sponsored_item = $adverts->get_the_sponsored_item();
if ( $sponsored_item ) {
    $context['sponsored_item'] = $sponsored_item;
}

$templates = [];
if ( is_a( $item, '\\Pedestal\\Posts\\Post' ) ) :
    $templates[] = 'single-' . $item->get_type() . '.twig';

    if ( $item->is_cluster() ) {
        $cluster_stream = new Stream( $item->get_entities_query() );
        $context['stream'] = $cluster_stream->get_the_stream();
        if ( $cluster_stream->is_last_page() ) {
            $context['show_closure_rule'] = true;
        }
        $context['pagination'] = $cluster_stream->get_pagination( [
            'show_text' => true,
        ] );
        if ( ! $cluster_stream->is_first_page() ) {
            $context['upper_pagination'] = $cluster_stream->get_pagination( [
                'show_text' => true,
                'show_nav' => false,
            ] );
        }

        $templates[] = 'single-cluster.twig';
        if ( is_active_sidebar( 'sidebar-stream' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-stream' );
        }
    }

    if ( $item->is_story() ) {
        $context['cluster'] = $item;
        if ( is_active_sidebar( 'sidebar-story' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-story' );
        }
    }

    if ( $item->is_entity() ) {
        $context['cluster'] = $item->get_primary_story();
        if ( is_active_sidebar( 'sidebar-entity' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-entity' );
        }
    }
endif;
$templates[] = 'single.twig';

$context['item'] = $item;

Timber::render( $templates, $context );
