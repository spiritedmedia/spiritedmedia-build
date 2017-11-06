<?php

namespace Pedestal;

use function Pedestal\Pedestal;
use \Pedestal\Utils\Utils;

class Icons {

    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    private function setup_actions() {
        add_action( 'add_attachment', [ $this, 'update_svg_fallback' ] );
        add_action( 'edit_attachment', [ $this, 'update_svg_fallback' ] );
        add_action( 'delete_attachment', [ $this, 'delete_svg_fallback' ] );
    }

    private function setup_filters() {
        // Set up some Twig functions
        add_filter( 'timber/twig', function( $twig ) {

            // ped_icon()
            $twig->addFunction( new \Twig_SimpleFunction( PEDESTAL_PREFIX . 'icon',
                [ __CLASS__, 'get_icon' ]
            ) );

            // ped_logo()
            $twig->addFunction( new \Twig_SimpleFunction( PEDESTAL_PREFIX . 'logo',
                [ __CLASS__, 'get_logo' ]
            ) );

            return $twig;
        }, 99 );
    }

    /**
     * Helper for fetching icons
     *
     * @param  string     $icon    Name of the file in the icons directory
     * @param  string     $classes Additional classes to apply to the SVG element
     * @param  string     $color   Color of the PNG for email -- should be a hex
     *     value. Defaults to primary color.
     * @param  int|string $size    Width|height for email PNG
     * @return string              Inline SVG/PNG markup
     */
    static function get_icon( $icon = '', $classes = '', $color = '', $size = 40 ) {
        $icon = sanitize_title( $icon );
        if ( ! $icon ) {
            return;
        }

        $atts = [
            'alt'   => $icon,
            'class' => 'o-icon  o-icon--' . $icon,
        ];

        if ( $classes ) {
            $atts['class'] = $classes . ' ' . $atts['class'];
        }

        if ( Pedestal()->is_email() ) {
            $color = $color ?: 'primary';
            $base = get_template_directory_uri();
            // Border must be removed for MSO
            $atts['border'] = '0';
            // Size must be set explicitly for MSO
            if ( $size && is_numeric( $size ) ) {
                $atts['width'] = $size;
            }
            // Primary colors are set per child theme, so assets must be stored there
            if ( 'primary' == $color ) {
                $site_config = Pedestal()->get_site_config();
                if ( empty( $site_config['site_branding_color'] ) ) {
                    return;
                }
                $color = $site_config['site_branding_color'];
                $base = get_stylesheet_directory_uri();
            }
            $icon = $icon . '.' . str_replace( '#', '', $color );
            $location = "{$base}/assets/images/icons/png/{$icon}.png";
            return static::get_png( $location, $atts );
        }

        $base = get_template_directory();
        $location = "{$base}/assets/images/icons/svg/{$icon}.svg";
        return static::get_svg( $location, $atts );
    }

    /**
     * Helper for fetching logos
     *
     * @param  string     $logo    Name of the file in the logos directory
     * @param  string     $classes Additional CSS classes
     * @param  int|string $size    Width|height for email PNG
     * @return string              Inline SVG/PNG markup
     */
    static function get_logo( string $logo = '', $classes = '', $size = 40 ) {
        $logo = sanitize_title( $logo );
        if ( ! $logo ) {
            return;
        }

        $logo_modifier_class = str_replace( 'logo-', '', $logo );
        if ( 'logo' == $logo_modifier_class ) {
            $logo_modifier_class = 'wide';
        }
        $atts = [
            'class' => $classes . ' logo logo--' . $logo_modifier_class,
        ];

        if ( Pedestal()->is_email() ) {
            $base = get_stylesheet_directory_uri();
            $location = "{$base}/assets/images/logos/{$logo}.png";
            // Border must be removed for MSO
            $atts['border'] = '0';
            // Size must be set explicitly for MSO
            if ( $size && is_numeric( $size ) ) {
                $atts['width'] = $size;
            }
            return static::get_png( $location, $atts );
        }

        $base = get_stylesheet_directory();
        $location = "{$base}/assets/images/logos/{$logo}.svg";
        return static::get_svg( $location, $atts );
    }

    /**
     * Get SVG markup from a path
     *
     * @param  string $location Filepath of the SVG
     * @param  array  $atts     Additional arguments
     * @return string           <svg> element
     */
    private static function get_svg( $location, $atts = [] ) {
        if ( ! $location || ! file_exists( $location ) ) {
            return;
        }
        $atts = static::handle_element_atts( $atts );
        return str_replace( '<svg ',
            '<svg ' . Utils::array_to_atts_str( $atts ),
            file_get_contents( $location )
        );
    }

    /**
     * Get PNG img element from a URL
     *
     * @param  string $location URL of the PNG
     * @param  array  $atts     Additional arguments
     * @return string           <img> element with PNG as `src`
     */
    private static function get_png( $location, $atts = [] ) {
        if ( ! $location ) {
            return;
        }
        $atts = static::handle_element_atts( $atts );
        return sprintf( '<img src="%s" %s />',
            $location,
            Utils::array_to_atts_str( $atts )
        );
    }

    /**
     * Prepare the attributes for the element
     *
     * @param  array $atts Attributes
     * @return array
     */
    private static function handle_element_atts( $atts = [] ) {
        $defaults = [
            'alt'       => '',
            'role'      => 'image',
            'class' => '',
        ];
        $atts = wp_parse_args( $atts, $defaults );
        if ( is_array( $atts['class'] ) ) {
            $atts['class'] = implode( ' ', $atts['class'] );
        }
        return $atts;
    }

    /**
     * Save a PNG fallback for SVG.
     *
     * @link http://eperal.com/automatically-generate-png-images-from-uploaded-svg-images-in-wordpress/
     *
     * @param int    $attachment_id
     * @param string $color         Hex foreground color
     */
    static function save_svg_fallback( $attachment_id, $color ) {
        $attachment_src = get_attached_file( $attachment_id );
        $fallback_src = str_replace( '.svg','.png', $attachment_src );

        // Load the re-colorized SVG into memory
        $svg = static::recolor_svg( $attachment_id, $color );

        // Create PNG from SVG
        $im = new \Imagick();
        $im->setBackgroundColor( new \ImagickPixel( 'transparent' ) );
        $im->readImageBlob( $svg );
        $im->trimImage( 0 );
        $im->setImageFormat( 'png' );
        $im->resizeImage( 25, 25, \Imagick::FILTER_LANCZOS, 1 );  /*Optional, if you need to resize*/
        $im->writeImage( $fallback_src );
        $im->clear();
        $im->destroy();
    }

    /**
     * Save optimized SVG
     *
     * @param  SVG    $svg          An SVG file loaded into memory
     * @param  string $path         Path to save the SVG to
     * @param  string $fallback_url URL to load as fallback image
     */
    static function save_svg( $svg, $path, $fallback_url ) {
        // @codingStandardsIgnoreStart
        $dom = new \DOMDocument();
        $dom->loadXML( $svg );
        $dom->createAttributeNS( 'http://www.w3.org/1999/xlink', 'xmlns:xlink' );
        $dom->documentElement->removeAttribute( 'width' );
        $dom->documentElement->removeAttribute( 'height' );
        foreach ( $dom->getElementsByTagName( 'image' ) as $image ) {
            $image->parentNode->removeChild( $image );
        }
        $f = $dom->createDocumentFragment();
        $f->appendXML( "<image src='$fallback_url'></image>" );
        $dom->documentElement->appendChild( $f );
        file_put_contents( $path, $dom->saveXML() );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Change the SVG fill color based on ID
     *
     * @param  int    $attachment_id SVG attachment ID
     * @param  string $color         Hex color to replace with
     * @return SVG
     */
    static function recolor_svg( $attachment_id, $color ) {
        $attachment_src = get_attached_file( $attachment_id );
        $svg = Utils::file_get_contents_with_auth( $attachment_src );
        // The SVG must have the xml declaration.
        // Very hacky.
        if ( '?' != $svg[1] ) {
            $svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $svg;
        }
        // Search/replace 3- or 6-character hex color code
        return preg_replace( '/#(?:[0-9a-fA-F]{3}){1,2}\b/i', $color, $svg );
    }

    /**
     * When an attachment is uploaded, create svg fallback.
     *
     * @param int $attachment_id
     */
    static function update_svg_fallback( $attachment_id ) {
        $type = get_post_mime_type( $attachment_id );
        if ( 'image/svg+xml' == $type ) {
            static::save_svg_fallback( $attachment_id, '#ffffff' );
        }
    }

    /**
     * Remove PNG fallback for SVG on attatchment delete.
     *
     * @link http://eperal.com/automatically-generate-png-images-from-uploaded-svg-images-in-wordpress/
     *
     * @param int $attachment_id
     */
    static function delete_svg_fallback( $attachment_id ) {
        $type = get_post_mime_type( $attachment_id );
        if ( 'image/svg+xml' == $type ) {
            $attachment_src = get_attached_file( $attachment_id ); // Gets path to attachment
            unlink( str_replace( '.svg','.png', $attachment_src ) );
        }
    }
}
