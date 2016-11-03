<?php

use Timber\Timber;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

use Pedestal\Posts\Slots\Slots;

add_filter( 'timber_context', function( $context ) {
    $context['is_email'] = true;

    $context['everyblock'] = $context['everyblock']['labels'] = [];
    $context['everyblock']['labels']['section']       = esc_html( 'Section', 'pedestal' );
    $context['everyblock']['labels']['location']      = esc_html( 'Location', 'pedestal' );
    $context['everyblock']['labels']['date']          = esc_html( 'Date', 'pedestal' );
    $context['everyblock']['labels']['dispatch_date'] = esc_html( 'Dispatch Date', 'pedestal' );
    $context['everyblock']['labels']['dispatch_time'] = esc_html( 'Dispatch Time', 'pedestal' );

    $context['grouped'] = true;

    return $context;
} );

$css = file_get_contents( get_stylesheet_directory() . '/assets/dist/css/email.css' );
$context = array_merge( Timber::get_context(), $vars );

ob_start();
Timber::render( 'emails/' . $template_name . '.twig', $context );
$html = ob_get_clean();

$inliner = new CssToInlineStyles();
echo $inliner->convert( $html, $css );
