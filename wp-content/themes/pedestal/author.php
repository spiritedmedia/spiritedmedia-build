<?php

use Timber\Timber;

use Pedestal\Objects\User;

use Pedestal\Posts\Post;

global $wp_query;

$context = Timber::get_context();

if ( $author_id = get_query_var( 'author' ) ) {

    $author = new User( $author_id );

    $context['items']   = $author->get_entities();
    $context['user']    = $author;
    $context['sidebar'] = false;

    Timber::render( 'author.twig', $context );

} else {
    require_once get_404_template();
}
