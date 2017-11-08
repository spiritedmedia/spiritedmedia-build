<?php
include 'include.php';
use Timber\Timber;

$context = Timber::get_context();
Timber::render( 'views/media-object.twig', $context );
