<?php

use Timber\Timber;

use Pedestal\Posts\Page;

$context = Timber::get_context();
$p = Timber::query_post();
$context['item'] = Page::get_by_post_id( $p->ID );

$context['quote'] = [];
$context['quote']['body']   = esc_html__( 'Knowledge is the treasure of a wise man.', 'pedestal' );
$context['quote']['author'] = 'William Penn';

$context['sidebar'] = false;

Timber::render( 'single-page.twig', $context );
