<?php

namespace Pedestal;

use function Pedestal\Pedestal;
use \Pedestal\Utils\Utils;

class Icons {

    /**
     * SVG markup for all icons in the filesystem
     */
    private static $icon_svgs;

    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_filters();
        }
        return $instance;
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
            $base  = get_template_directory_uri();
            // Border must be removed for MSO
            $atts['border'] = '0';
            // Size must be set explicitly for MSO
            if ( $size && is_numeric( $size ) ) {
                $atts['width'] = $size;
            }
            // Primary colors are set per child theme, so assets must be stored there
            if ( 'primary' == $color ) {
                if ( ! defined( 'PEDESTAL_BRAND_COLOR' ) ) {
                    return;
                }
                $color = PEDESTAL_BRAND_COLOR;
                $base  = get_stylesheet_directory_uri();
            }
            $icon     = $icon . '.' . str_replace( '#', '', $color );
            $location = "{$base}/assets/images/icons/png/{$icon}.png";
            return static::get_png( $location, $atts );
        }

        $base     = get_template_directory();
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
            $base     = get_stylesheet_directory_uri();
            $location = "{$base}/assets/images/logos/{$logo}.png";
            // Border must be removed for MSO
            $atts['border'] = '0';
            // Size must be set explicitly for MSO
            if ( $size && is_numeric( $size ) ) {
                $atts['width'] = $size;
            }
            return static::get_png( $location, $atts );
        }

        $base     = get_stylesheet_directory();
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
            'alt'   => '',
            'role'  => 'image',
            'class' => '',
        ];
        $atts     = wp_parse_args( $atts, $defaults );
        if ( is_array( $atts['class'] ) ) {
            $atts['class'] = implode( ' ', $atts['class'] );
        }
        return $atts;
    }

    /**
     * Read all of the icons in the SVG assets directory in filesystem
     *
     * @return object Icon names and SVG contents sorted in alphabetical order
     */
    public static function get_all_icons_svg() {
        if ( self::$icon_svgs && is_object( self::$icon_svgs ) ) {
            return self::$icon_svgs;
        }
        $directory = get_template_directory() . '/assets/images/icons/svg/';
        $icons     = [];
        $iterator  = new \DirectoryIterator( $directory );
        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() ) {
                continue;
            }
            $parts = explode( '.', $file->getFilename() );
            if ( empty( $parts[1] ) || 'svg' != $parts[1] ) {
                continue;
            }
            $icon_name           = $parts[0];
            $icon                = static::get_icon( $icon_name );
            $icons[ $icon_name ] = (object) [
                'svg'   => $icon,
                'label' => $icon_name,
            ];
        }
        ksort( $icons );
        self::$icon_svgs = $icons;
        return self::$icon_svgs;
    }
}
