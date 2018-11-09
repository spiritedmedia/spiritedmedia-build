<?php
include 'include.php';
use Timber\Timber;

/**
 * Parse a list of given files looking for 3 or 6 digit hex codes
 *
 * @param  array  $files List of files to parse
 * @return array         Output keyed to the sass variable containing the hex color value and any comments
 */
function get_sass_colors( $files = [] ) {
    if ( ! is_array( $files ) ) {
        $files = [ $files ];
    }

    $output = [];
    foreach ( $files as $file ) :
        $handle = fopen( $file, 'r' );
        if ( ! $handle ) {
            return $output;
        }
        while ( $line = fgets( $handle ) ) {
            $line = trim( $line );
            if ( empty( $line ) ) {
                continue;
            }

            // Match 3 or 6 character hex code patterns
            preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $line, $match );
            if ( empty( $match[0] ) ) {
                continue;
            }
            $value = $match[0];
            $value = str_replace( '#', '', $value );
            if ( ! $value ) {
                continue;
            }

            // Match Sass variable names:
            // - Starts with $
            // - Contains a-z, A-Z, 0-9
            // - Contains hyphen or underscore
            // - Has a colon in it
            preg_match( '/(\$[0-9a-z_\-]+)(.*):/i', $line, $match );
            if ( empty( $match[1] ) ) {
                continue;
            }
            $variable = $match[1];

            $comment = '';
            $comment_parts = explode( '//', $line );
            if ( ! empty( $comment_parts[1] ) ) {
                $comment = trim( $comment_parts[1] );
            }

            $output[ $variable ] = [
                'hex'     => $value,
                'comment' => $comment,
            ];
        }
        fclose( $handle );
    endforeach;
    return $output;
}

/**
 * Get the brandColor value of the current site from /config/config.json
 *
 * @return string Hex value of the current site's brandColor
 */
function get_brand_color() {
    $filename = ABSPATH . 'config/config.json';
    $file = file_get_contents( $filename );
    $json = json_decode( $file );
    $color = $json->pedestal->children->{ PEDESTAL_THEME_NAME }->brandColor;
    return str_replace( '#', '', $color );
}

$parent_file             = get_template_directory() . '/assets/scss/family/_colors.scss';
$child_file              = get_stylesheet_directory() . '/assets/scss/base/_settings.scss';
$files                   = [ $parent_file, $child_file ];
$colors                  = get_sass_colors( $files );

// Get value for $brand-1-color defined in /config/config.json
$colors['$brand-1-color'] = [
    'hex'     => get_brand_color(),
    'comment' => 'Defined in /config/config.json',
];

$context = Timber::get_context();
$context['colors'] = $colors;
Timber::render( 'views/colors.twig', $context );
