<?php
use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$item = Post::get( get_the_ID() );
$context = Timber::get_context();

if ( Types::is_post( $item ) ) {
    $context = $item->get_context( $context );
}

$context['item'] = $item;

Timber::render( 'single-newsletter.twig', $context );
