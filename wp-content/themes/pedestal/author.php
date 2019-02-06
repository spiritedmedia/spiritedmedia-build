<?php

use Timber\Timber;
use Pedestal\Objects\User;
use Pedestal\Posts\Post;
use Pedestal\Objects\Stream;

global $wp_query;

$context           = Timber::get_context();
$context['format'] = 'extended';

$author_id = get_query_var( 'author' );
if ( $author_id ) {
    $author          = new User( $author_id );
    $context['user'] = $author;

    $author_stream     = new Stream( $author->get_posts_query() );
    $context['stream'] = $author_stream->get_the_stream();
    if ( $author_stream->is_last_page() ) {
        $context['show_closure_rule'] = true;
    }
    $context['pagination']       = $author_stream->get_load_more_button();
    $context['upper_pagination'] = $author_stream->get_pagination( [
        'show_text' => true,
        'show_nav'  => false,
    ] );

    Timber::render( 'author.twig', $context );
} else {
    require_once get_404_template();
}
