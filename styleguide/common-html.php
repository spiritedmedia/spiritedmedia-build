<?php
include 'include.php';
use Timber\Timber;

$context = Timber::get_context();

Timber::render( 'views/common-html.twig', $context );
