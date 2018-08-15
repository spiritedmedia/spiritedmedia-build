<?php

use Timber\Timber;

add_filter( 'show_admin_bar', '__return_false' );

$context = Timber::get_context();
Timber::render( 'base-preview.twig', $context );
