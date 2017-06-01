<?php

use Timber\Timber;

$context = Timber::get_context();

Timber::render( 'emails/pages/newsletter-signup.twig', $context );
