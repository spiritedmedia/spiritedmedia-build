<?php

use Timber\Timber;

$context = Timber::get_context();

$context['quote'] = [];
$context['quote']['body'] = esc_html__( 'Time is what we want most, but what we use worst.', 'pedestal' );
$context['quote']['author'] = 'William Penn';

$context['message'] = [];
$context['message']['hook'] = esc_html__( 'Don’t waste yours.', 'pedestal' );
$context['message']['content'] = esc_html__( 'This page doesn’t exist.', 'pedestal' );

$context['young_penn'] = [];
$context['young_penn']['src'] = get_template_directory_uri() . '/assets/images/young-penn.png';
$context['young_penn']['description'] = esc_html__( 'Painting: Oil on canvas portrait of Billy Penn at age 22 in 1666.', 'pedestal' );
$context['young_penn']['source'] = esc_html__( 'Source: Library of Congress, American Memory project, Historical Society of Pennsylvania', 'pedestal' );

Timber::render( '404.twig', $context );
