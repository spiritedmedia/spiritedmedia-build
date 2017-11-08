<?php
include 'include.php';
use Timber\Timber;

$context = Timber::get_context();
$context['classes'] = [
    'u-size-h1' => 'H1',
    'u-size-h2' => 'H2',
    'u-size-h3' => 'H3',
    'u-size-h4' => 'H4',
    'u-size-h5' => 'H5',
    'u-size-h6' => 'H6',
];

$context['elements'] = [
    'h1' => 'H1',
    'h2' => 'H2',
    'h3' => 'H3',
    'h4' => 'H4',
    'h5' => 'H5',
    'h6' => 'H6',
    'p' => 'paragraph',
];

Timber::render( 'views/utilities.twig', $context );
