<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

global $wp_query;

$context = Timber::get_context();
$items = Stream::get( $wp_query->posts );
$context['items'] = $items;
$context['archive_title'] = Pedestal\Frontend::get_archive_title();

if ( $items ) {
    Timber::render( 'archive.twig', $context );
} else {
    locate_template( [ '404.php' ], true );
}
