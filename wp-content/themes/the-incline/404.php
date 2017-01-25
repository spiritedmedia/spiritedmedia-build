<?php

use Timber\Timber;

$context = Timber::get_context();

$context['message'] = [];
$context['message']['hook'] = esc_html__( 'Whoa! You just found the tunnel monster.', 'pedestal' );
$context['message']['content'] = esc_html__( 'This page doesn’t really exist.', 'pedestal' );

$context['tunnel_monster'] = [];
$context['tunnel_monster']['src'] = get_stylesheet_directory_uri() . '/assets/images/tunnel-monster.png';

Timber::render( '404.twig', $context );
