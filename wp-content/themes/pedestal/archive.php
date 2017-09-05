<?php

use Timber\Timber;
use Pedestal\Objects\Stream;

$stream = new Stream;

$context = Timber::get_context();
$context['archive_title'] = Pedestal\Frontend::get_archive_title();
if ( $stream->is_stream_list() ) {
    $context['stream'] = $stream->get_the_stream_list();
    $context['extra_stream_container_classes'] = 'stream--list';
} else {
    $context['stream'] = $stream->get_the_stream();
}
$context['pagination'] = $stream->get_pagination( [
    'show_text' => false,
] );

Timber::render( 'archive.twig', $context );
