<?php
use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$item    = Post::get( get_the_ID() );
$context = Timber::get_context();

// Set up the templates Twig will search for in order of priority
$templates = [];
if ( Types::is_post( $item ) ) :
    $templates[] = 'single-' . $item->get_type() . '.twig';

    // Load Post context after everything else so it takes priority
    $context = $item->get_context( $context );
endif;

// single.twig is the lowest priority template and for now should just be blank
$templates[] = 'single.twig';

$context['item'] = $item;
Timber::render( $templates, $context );
