<?php
use Timber\Timber;

$context = Timber::get_context();
Timber::render( 'emails/generic-confirm-email.twig', $context );
exit;
