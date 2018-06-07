<?php

include 'include.php';

use Timber\Timber;
use Pedestal\Objects\Stream;

// Display the site editor's info as an example
$site_editor_id = 0;
switch ( get_current_blog_id() ) {
    case 2:
        $site_editor_id = 2317;
        break;
    case 3:
        $site_editor_id = 17258;
        break;
    case 4:
        $site_editor_id = 19619;
        break;
}
$user = new \Pedestal\Objects\User( $site_editor_id );

// Include the site editor and the product team in the user grid example
$user_grid_ids = $site_editor_id . ',19609,15756,3,19610';
$context = [
    'user' => $user,
    'user_grid_shortcode' => "[pedestal-user-grid ids={$user_grid_ids}]",
] + Timber::get_context();

// Copied from author.php
$author_stream = new Stream( $user->get_stream_query() );
$context['stream'] = $author_stream->get_the_stream();
if ( $author_stream->is_last_page() ) {
    $context['show_closure_rule'] = true;
}
$context['pagination'] = $author_stream->get_load_more_button();
$context['upper_pagination'] = $author_stream->get_pagination( [
    'show_text' => true,
    'show_nav' => false,
] );

Timber::render( 'views/user-card.twig', $context );
