<?php

use Timber\Timber;
use Pedestal\Objects\User;
use Pedestal\Posts\Post;
use Pedestal\Objects\Stream;

global $wp_query;

$context = Timber::get_context();

$author_id = get_query_var( 'author' );
if ( $author_id ) {
    $author = new User( $author_id );
    $context['user'] = $author;

    $author_stream = new Stream( $author->get_stream_query() );
    $context['stream'] = $author_stream->get_the_stream();
    if ( $author_stream->is_last_page() ) {
        $context['show_closure_rule'] = true;
    }
    $context['pagination'] = $author_stream->get_pagination( [
        'show_text' => true,
    ] );
    $context['upper_pagination'] = $author_stream->get_pagination( [
        'show_text' => true,
        'show_nav' => false,
    ] );

    Timber::render( 'author.twig', $context );

} else {
    require_once get_404_template();
}
