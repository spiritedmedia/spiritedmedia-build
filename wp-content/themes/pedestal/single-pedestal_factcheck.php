<?php

use Timber\Timber;

use Pedestal\Posts\Post;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Post::get_by_post_id( $p->ID );
$context['sidebar_class'] = 'sidebar--factcheck';

ob_start();
$context['sidebar'] = Timber::render( 'sidebar-factcheck.twig', $context );
ob_get_clean();

Timber::render( 'single-factcheck.twig', $context );
