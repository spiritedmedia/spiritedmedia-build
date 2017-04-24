<?php
// define( 'WP_USE_THEMES', false );
require ( '../wp-load.php' );

if ( ! is_user_logged_in() ) {
	auth_redirect();
}

function styleguide_search_and_replace( $string = '' ) {
	// Replace any links to the root with styleguide root links
	$string = str_replace( 'href="https://billypenn.com"', 'href="/styleguide/"', $string );
	$old_stylesheet = 'https://a.spirited.media/wp-content/themes/billy-penn/assets/dist/css/theme.css?ver=5.2.5';
	$new_stylesheet = get_stylesheet_directory_uri() . '/assets/dist/css/theme.css?ver=' . rand();
	$string = str_replace( $old_stylesheet, $new_stylesheet, $string );

	$string = str_replace( 'https://a.spirited.media/', trailingslashit( get_site_url() ), $string );

	return $string;
}
add_filter( 'styleguide_header', 'styleguide_search_and_replace');
add_filter( 'styleguide_footer', 'styleguide_search_and_replace');

function styleguide_header() {
    ob_start();
    require_once 'header.php';
    $header = ob_get_clean();
	echo apply_filters( 'styleguide_header', $header );
}

function styleguide_footer() {
    ob_start();
    require_once 'footer.php';
    $footer = ob_get_clean();
	echo apply_filters( 'styleguide_footer', $footer );
}

function styleguide_icon( $icon = '', $classes = '' ) {
    $icon = sanitize_title( $icon );
    if ( ! $icon ) {
        return;
    }

    $atts = [
        'alt'       => $icon,
        'role'      => 'image',
        'css_class' => 'o-icon  o-icon--' . $icon,
    ];

    if ( $classes ) {
        $atts['css_class'] = $classes . ' ' . $atts['css_class'];
    }

    $base = get_template_directory();
    $location = "{$base}/assets/images/icons/svg/{$icon}.svg";
    if ( ! $location || ! file_exists( $location ) ) {
        return;
    }

    echo str_replace( '<svg ',
        '<svg class="' . esc_attr( $atts['css_class'] ) . '" role="' . esc_attr( $atts['role'] ) . '" ',
        file_get_contents( $location )
    );
}
