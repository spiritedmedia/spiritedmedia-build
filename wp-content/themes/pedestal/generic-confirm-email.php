<?php
use Timber\Timber;

$context = Timber::get_context();
Timber::render( 'emails/pages/confirm-generic.twig', $context );
exit;
