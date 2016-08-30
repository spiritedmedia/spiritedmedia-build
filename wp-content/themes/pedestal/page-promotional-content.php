<?php

use Timber\Timber;

$context = Timber::get_context();

$context['promo_story_title'] = '';
$context['promo_story_title'] = 'BrandLand';

Timber::render( 'page-promotional-content.twig', $context );
