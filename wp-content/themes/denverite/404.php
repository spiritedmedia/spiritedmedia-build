<?php

use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$context = Timber::get_context();

$post     = get_page_by_path( 'not-found' );
$ped_post = Post::get( $post );
if ( Types::is_post( $ped_post ) ) {
    $context['item'] = $ped_post;
}

Timber::render( '404.twig', $context );
