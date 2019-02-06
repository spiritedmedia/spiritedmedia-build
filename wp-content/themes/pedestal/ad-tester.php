<?php
use Timber\Timber;
use Pedestal\Adverts;

$context            = Timber::get_context();
$context['sidebar'] = false;

$ad_name = false;
if ( ! empty( $_GET['ad-name'] ) ) {
    $ad_name            = sanitize_text_field( $_GET['ad-name'] );
    $context['ad_name'] = $ad_name;
}

$ad_sizes = false;
if ( ! empty( $_GET['ad-sizes'] ) ) {
    $ad_sizes            = sanitize_text_field( $_GET['ad-sizes'] );
    $context['ad_sizes'] = $ad_sizes;
}
$context['ad'] = Adverts::render_dfp_unit( $ad_name, $ad_sizes, [], '1' );
Timber::render( 'ad-tester.twig', $context );
