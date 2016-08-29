<?php

use Timber\Timber;

use Pedestal\Posts\Post;
use Pedestal\Objects\User;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Post::get_by_post_id( $p->ID );
if ( wp_get_current_user() ) {
    $context['current_user'] = new User( wp_get_current_user() );
} else {
    $context['current_user'] = false;
}

$context['grouped'] = true;
$context['is_cluster'] = true;

Timber::render( 'single-story.twig', $context );
