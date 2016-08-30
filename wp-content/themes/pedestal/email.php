<?php

use Timber\Timber;

use Pedestal\Posts\Slots\Slots;

add_filter( 'timber_context', function( $context ) {
    $context['is_email'] = true;

    $context['styles'] = file_get_contents( get_stylesheet_directory() . '/assets/dist/css/email.css' );
    $context['styles'] = str_replace(
        [ PHP_EOL, '../images/' ],
        [ ' ', get_template_directory_uri() . '/assets/images/' ],
        $context['styles']
    );
    $context['styles'] = preg_replace(
        '!/\*[^*]*\*+([^/][^*]*\*+)*/!',
        '',
        $context['styles']
    ); // strip out comments

    $context['everyblock'] = $context['everyblock']['labels'] = [];
    $context['everyblock']['labels']['section']       = esc_html( 'Section', 'pedestal' );
    $context['everyblock']['labels']['location']      = esc_html( 'Location', 'pedestal' );
    $context['everyblock']['labels']['date']          = esc_html( 'Date', 'pedestal' );
    $context['everyblock']['labels']['dispatch_date'] = esc_html( 'Dispatch Date', 'pedestal' );
    $context['everyblock']['labels']['dispatch_time'] = esc_html( 'Dispatch Time', 'pedestal' );

    $context['grouped'] = true;

    return $context;
} );

$context = array_merge( Timber::get_context(), $vars );
Timber::render( 'emails/' . $template_name . '.twig', $context );
