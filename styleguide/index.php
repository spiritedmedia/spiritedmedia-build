<?php
use Timber\Timber;

include 'include.php';

$context = Timber::get_context();
Timber::render( 'views/index.twig.md', $context );
