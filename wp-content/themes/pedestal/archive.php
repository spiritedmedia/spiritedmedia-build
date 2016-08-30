<?php

use Timber\Timber;

use \Pedestal\Posts\Post;

$context = Timber::get_context();
$posts = Timber::get_posts( false, 'WP_Post' );
$items = Post::get_posts( $posts );
$context['items'] = $items;

$context['archive_title'] = Pedestal\Frontend::get_archive_title();

if ( $items ) {
    Timber::render( 'archive.twig', $context );
} else {
    locate_template( [ '404.php' ], true );
}
