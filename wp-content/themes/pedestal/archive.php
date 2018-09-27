<?php
use Timber\Timber;
use Pedestal\Objects\Stream;
use Pedestal\Adverts;

$stream = new Stream;

$context = Timber::get_context();
$context['archive_title'] = Pedestal\Frontend::get_archive_title();
$context['archive_description'] = get_queried_object()->description ?? '';

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

$context['sidebar'] = '<li class="widget widget_pedestal_dfp_rail_right">' . Adverts::render_sidebar_ad_unit() . '</li>';

Timber::render( 'archive.twig', $context );
