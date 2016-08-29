<?php

use Timber\Timber;

$context = Timber::get_context();
Timber::render( 'index.twig', $context );
