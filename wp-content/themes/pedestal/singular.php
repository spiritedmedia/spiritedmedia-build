<?php

use Timber\Timber;
use Pedestal\Adverts;
use Pedestal\Objects\Stream;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$p = Timber::query_post();
$item = Post::get( $p->ID );

$context = Timber::get_context();

$adverts = new Adverts;
$sponsored_item = $adverts->get_the_sponsored_item();
if ( $sponsored_item ) {
    $context['sponsored_item'] = $sponsored_item;
}

// Set up the templates Twig will search for in order of priority
$templates = [];
if ( Types::is_post( $item ) ) :

    $templates[] = 'single-' . $item->get_type() . '.twig';

    if ( $item->is_entity() ) {
        $templates[] = 'single-entity.twig';
        $context['cluster'] = $item->get_primary_story();
        $context['featured_image_sizes'] = [
            '(max-width: 640px) 95vw',
            '(max-width: 1024px) 97vw',
            '(min-width: 1025px) 676px',
            '96vw',
        ];
        $context['featured_image_srcset'] = [
            'ratio'  => 16 / 9,
            'widths' => [ 320, 480, 640, 676, 700, 800, 1024 ],
        ];
        if ( is_active_sidebar( 'sidebar-entity' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-entity' );
        }
    }

    if ( $item->is_cluster() ) {
        add_filter( 'pedestal_stream_item_context', function( $context, $ped_post ) {
            // Use the excerpt/subhead in cluster streams instead of the summary
            $context['description'] = $ped_post->get_the_excerpt();
            return $context;
        }, 10, 2 );
        $templates[] = 'single-cluster.twig';
        $cluster_stream = new Stream( $item->get_entities_query() );
        $context['stream'] = $cluster_stream->get_the_stream();

        if ( $cluster_stream->is_last_page() ) {
            $context['show_closure_rule'] = true;
        }

        $context['pagination'] = $cluster_stream->get_load_more_button();

        if ( ! $cluster_stream->is_first_page() ) {
            $context['upper_pagination'] = $cluster_stream->get_pagination( [
                'show_text' => true,
                'show_nav' => false,
            ] );
        }

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

    // Load Post context after everything else so it takes priority
    $context = $item->get_context( $context );

    // Do some post-processing
    if ( isset( $context['content_classes'] ) && is_array( $context['content_classes'] ) ) {
        $context['content_classes'] = implode( ' ', $context['content_classes'] );
    }

endif;

// single.twig is the lowest priority template and for now should just be blank
$templates[] = 'single.twig';

$context['item'] = $item;

Timber::render( $templates, $context );
