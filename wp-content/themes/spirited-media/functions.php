<?php
$version = file_get_contents( ABSPATH . '/VERSION' );
$version = str_replace( 'Version: ', '', $version );
define( 'PEDESTAL_VERSION', $version );

add_action( 'wp_enqueue_scripts', function() {
    wp_register_style( 'spirited-media-google-fonts', 'https://fonts.googleapis.com/css?family=Lato:400,700', [], null, 'all' );
    wp_enqueue_style( 'spirited-media-styles', get_template_directory_uri() . '/assets/dist/css/theme.css', [ 'spirited-media-google-fonts' ], PEDESTAL_VERSION );

    if ( is_home() ) {
        wp_enqueue_script( 'spirited-media-home', get_template_directory_uri() . '/assets/dist/js/home.js', [ 'jquery' ], PEDESTAL_VERSION, true );
    }
});

/**
 * Add favicons to `wp_head`
 */
add_action( 'wp_head', function() {
    include 'favicons.php';
} );

function svg_icon( $icon = '' ) {
    if ( ! $icon ) {
        return;
    }
    $path = get_template_directory() . '/assets/icons/' . $icon . '.svg';
    $args = [
        'css_class' => 'icon icon-' . $icon,
    ];
    return get_svg( $path, $args );
}

function svg_logo( $logo = '' ) {
    if ( ! $logo ) {
        return;
    }

    $path = get_template_directory() . '/assets/images/logos/' . $logo . '.svg';
    $args = [
        'css_class' => 'logo logo-' . sanitize_title( $logo ),
    ];
    return get_svg( $path, $args );
}

function get_svg( $path, $args = [] ) {
    if ( ! $path ) {
        return;
    }
    $defaults = [
        'role' => 'image',
        'css_class' => '',
    ];
    $args = wp_parse_args( $args, $defaults );

    $role_attr = $args['role'];
    $css_class = $args['css_class'];
    if ( is_array( $css_class ) ) {
        $css_class = implode( ' ', $css_class );
    }

    if ( file_exists( $path ) ) {
        $svg = file_get_contents( $path );
        $svg = preg_replace( '/(width|height)="[\d\.]+"/i', '', $svg );
        $svg = str_replace( '<svg ', '<svg class="' . esc_attr( $css_class ) . '" role="' . esc_attr( $role_attr ) . '" ', $svg );

        return $svg;
    }
}
