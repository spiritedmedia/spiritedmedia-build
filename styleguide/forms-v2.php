<?php
include 'include.php';
use Timber\Timber;

$context = Timber::get_context();
$context['input_types'] = [
    'text',
    'search',
    'email',
    'number',
    'url',
    'tel',
];
Timber::render( 'views/forms-v2.twig', $context );
