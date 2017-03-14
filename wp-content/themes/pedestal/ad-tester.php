<?php
use Timber\Timber;
$context = Timber::get_context();
$context['sidebar'] = false;

$context['ad_name'] = false;
if ( ! empty( $_GET['ad-name'] ) ) {
	$context['ad_name'] = sanitize_text_field( $_GET['ad-name'] );
}

$context['ad_sizes'] = false;
if ( ! empty( $_GET['ad-sizes'] ) ) {
	$context['ad_sizes'] = sanitize_text_field( $_GET['ad-sizes'] );
}
Timber::render( 'ad-tester.twig', $context );
