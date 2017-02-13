<?php
// define( 'WP_USE_THEMES', false );
require ( '../wp-load.php' );

if ( ! is_user_logged_in() ) {
	wp_die( 'You wish! Please login.' );
}

function styleguide_search_and_replace( $string = '' ) {
	// Replace any links to the root with styleguide root links
	$string = str_replace( 'href="https://billypenn.com"', 'href="/styleguide/"', $string );
	$old_stylesheet = 'https://a.spirited.media/wp-content/themes/billy-penn/assets/dist/css/theme.css';
	$new_stylesheet = get_stylesheet_directory_uri() . '/assets/dist/css/theme.css';
	$string = str_replace( $old_stylesheet, $new_stylesheet, $string );

	$string = str_replace( 'https://a.spirited.media/', trailingslashit( get_site_url() ), $string );

	return $string;
}
add_filter( 'styleguide_header', 'styleguide_search_and_replace');
add_filter( 'styleguide_footer', 'styleguide_search_and_replace');

function styleguide_header() {
	echo apply_filters( 'styleguide_header', file_get_contents( 'header.html' ) );
}

function styleguide_footer() {
	echo apply_filters( 'styleguide_footer', file_get_contents( 'footer.html' ) );
}
