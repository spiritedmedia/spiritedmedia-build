<?php
use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$item = Post::get( get_the_ID() );
$context = Timber::get_context();

if ( Types::is_post( $item ) ) {
    $context = $item->get_context( $context );

    // Do some post-processing
    if ( isset( $context['content_classes'] ) && is_array( $context['content_classes'] ) ) {
        $context['content_classes'] = implode( ' ', $context['content_classes'] );
    }
}

$context['item'] = $item;
Timber::render( [ 'single-page.twig', 'single.twig' ], $context );
