<?php

use Timber\Timber;

$context = Timber::get_context();

Timber::render( 'page-unfollow-confirmation.twig', $context );
