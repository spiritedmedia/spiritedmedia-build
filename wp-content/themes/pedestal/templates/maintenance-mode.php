<?php

use Timber\Timber;

$context = Timber::get_context();
Timber::render( 'maintenance-mode.twig', $context );
