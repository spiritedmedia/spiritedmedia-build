<?php
use Timber\Timber;
use Pedestal\Objects\Stream;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$cluster_id = get_the_ID();
$item = Post::get( $cluster_id );
$context = Timber::get_context();

if ( Types::is_post( $item ) ) :

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

    if ( $item->is_story() ) {
        $context['cluster'] = $item;
        $context['cluster_id'] = $cluster_id;
        $context['cluster_group_id'] = $item->get_mailchimp_group_id();
        $context['cluster_group_category'] = $item->get_mailchimp_group_category();

        if ( is_active_sidebar( 'sidebar-story' ) ) {
            $context['sidebar'] = Timber::get_widgets( 'sidebar-story' );
        }

        if ( ! empty( $_GET['force-display-follow-form'] ) ) {
            $context['force_display_follow_form'] = true;
        }
    }

    // Load Post context after everything else so it takes priority
    $context = $item->get_context( $context );

endif;

$context['item'] = $item;
Timber::render( 'single-cluster.twig', $context );
