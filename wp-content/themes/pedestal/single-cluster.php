<?php
use Timber\Timber;
use Pedestal\Objects\Stream;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Email\Follow_Updates;
use Pedestal\Adverts;

$cluster_id = get_the_ID();
$item       = Post::get( $cluster_id );
$context    = Timber::get_context();

if ( Types::is_post( $item ) ) :

    if ( $item->is_story() ) {
        $context['cluster_prompt'] = Follow_Updates::get_signup_form( [
            'signup_source' => $item->get_type_name(),
        ], $cluster_id );

        if ( $item->get_slug() == 'whos-next' ) {
            $context['rail_class'] = '';
        }

        add_filter( 'pedestal_stream_item_context', function( $context ) {
            $context['overline'] = '';
            return $context;
        } );
    }

    $cluster_stream    = new Stream( $item->get_posts_query() );
    $context['stream'] = $cluster_stream->get_the_stream();

    if ( $cluster_stream->is_last_page() ) {
        $context['show_closure_rule'] = true;
    }

    if ( ! $item->is_story() ) {
        $context['heading_prefix'] = $cluster_stream->is_first_page()
            ? 'Posts tagged with'
            : 'More posts tagged with';
    }

    $context['pagination'] = $cluster_stream->get_load_more_button();

    if ( ! $cluster_stream->is_first_page() ) {
        $context['upper_pagination'] = $cluster_stream->get_pagination( [
            'show_text' => true,
            'show_nav'  => false,
        ] );
    }

    // Load Post context after everything else so it takes priority
    $context = $item->get_context( $context );

    $context['rail_class'] = '';
    if ( empty( $context['sidebar'] ) && $item->get_slug() != 'whos-next' ) {
        $context['rail_class'] .= 'is-sticky';
    }

endif;

$context['item'] = $item;
Timber::render( 'single-cluster.twig', $context );
