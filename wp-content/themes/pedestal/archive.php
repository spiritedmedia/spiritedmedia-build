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

$button_text = 'More stories';
if ( is_archive() && 'originals' == get_query_var( 'pedestal_originals' ) ) {
    $button_text = 'More originals';
}
if ( is_post_type_archive() ) {
    if ( isset( get_queried_object()->labels->name ) ) {
        $button_text = 'More ' . strtolower( get_queried_object()->labels->name );
    }
}
$context['pagination'] = $stream->get_load_more_button([
    'text' => $button_text,
]);

Timber::render( 'archive.twig', $context );
