<?php

use Timber\Timber;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

use Pedestal\Posts\Slots\Slots;

$css = file_get_contents( get_stylesheet_directory() . '/assets/dist/css/email.css' );

$debug_styles = false;
if ( defined( 'PEDESTAL_DEBUG_EMAIL_CSS' ) && true === PEDESTAL_DEBUG_EMAIL_CSS ) {
    $debug_styles = $css;
}

add_filter( 'timber_context', function( $context ) use ( $debug_styles ) {
    $context['is_home'] = false;
    $context['email_styles'] = $debug_styles;
    return $context;
} );

$context = array_merge( Timber::get_context(), $vars );

ob_start();
Timber::render( 'emails/' . $template_name . '.twig', $context );
$html = ob_get_clean();

if ( false === $debug_styles ) {
    $inliner = new CssToInlineStyles();
    $html = $inliner->convert( $html, $css );
}

echo $html;
