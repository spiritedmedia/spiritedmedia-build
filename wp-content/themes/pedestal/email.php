<?php

use Timber\Timber;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

use Pedestal\Posts\Slots\Slots;

$base_css = file_get_contents( get_template_directory() . '/assets/dist/css/email-base.css' );
$css = file_get_contents( get_stylesheet_directory() . '/assets/dist/css/email.css' );

$debug_styles = false;
if ( defined( 'PEDESTAL_DEBUG_EMAIL_CSS' ) && true === PEDESTAL_DEBUG_EMAIL_CSS ) {
    $debug_styles = $css;
}

add_filter( 'timber_context', function( $context ) use (
    $template_name,
    $vars,
    $debug_styles,
    $base_css
    ) {

    $template_name = str_replace( '_', '-', $template_name );
    $base_spacing_unit = 10;
    $context = array_merge( $context, [
        'template_name'                   => $template_name,
        'is_home'                         => false,
        'show_content_header'             => true,
        'base_spacing_unit'               => $base_spacing_unit,
        'email_debug_styles'              => $debug_styles,
        'email_base_styles'               => $base_css,
        'email_background_color'          => '#ffffff',
        'email_container_width'           => 680,
        'email_container_width_inner'     => 680 - $base_spacing_unit * 2,
        'email_module_text_overlay_width' => 500,
        'email_table_atts'                => 'role="presentation" aria-hidden="true" cellspacing="0" cellpadding="0" border="0"',
        'email_header_preview_text'       => $vars['preview_text'],
    ] );
    return $context;

} );

$context = array_merge( Timber::get_context(), $vars );


ob_start();
Timber::render( 'emails/messages/' . $template_name . '.twig', $context );
$html = ob_get_clean();

if ( false === $debug_styles ) {
    $inliner = new CssToInlineStyles();
    $html = $inliner->convert( $html, $css );
}

echo $html;
