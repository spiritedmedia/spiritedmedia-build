<?php

namespace Pedestal\Shortcodes;

use \Shortcake_Bakery\Shortcodes\Shortcode;

class Iframe extends Shortcode {

    public static function get_shortcode_ui_args() {
        return [
            'label'         => esc_html__( 'Iframe', 'pedestal' ),
            'listItemImage' => 'dashicons-admin-site',
            'attrs'         => [
                [
                    'label'       => esc_html__( 'URL', 'pedestal' ),
                    'attr'        => 'src',
                    'type'        => 'text',
                    'description' => esc_html__( 'Full URL to the iFrame source. Host must be whitelisted.', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'Height', 'pedestal' ),
                    'attr'        => 'height',
                    'type'        => 'text',
                    'description' => esc_html__( 'Pixel height of the iframe. Defaults to 600.', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'Width', 'pedestal' ),
                    'attr'        => 'width',
                    'type'        => 'text',
                    'description' => esc_html__( 'Pixel width of the iframe. Defaults to 670.', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'Disable Responsiveness', 'pedestal' ),
                    'attr'        => 'disableresponsiveness',
                    'type'        => 'checkbox',
                    'description' => esc_html__( 'By default, height/width ratio of iframe will be maintained regardless of container width. Check this to keep constant height/width.', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'Allow Scrolling', 'pedestal' ),
                    'attr'        => 'allowscrolling',
                    'type'        => 'checkbox',
                    'description' => esc_html__( 'By default, scrolling is disabled because it\'s usually unnecessary. Some embeds don\'t resize to fit the iframe properly, so sometimes it is necessary.', 'pedestal' ),
                ],
            ],
        ];
    }

    /**
     * Transform any <iframe> embeds within content to our iframe shortcode
     *
     * @param string $content
     * @return string
     */
    public static function reversal( $content ) {
        $iframes = self::parse_iframes( $content );
        if ( ! $iframes ) {
            return $content;
        }
        $replacements = [];
        foreach ( $iframes as $iframe ) {
            $iframe_atts = $iframe->attrs;
            unset( $iframe_atts['src'] );
            $other_atts = '';
            foreach ( $iframe_atts as $key => $val ) {
                $other_atts .= ' ' . $key . '="' . esc_attr( $val ) . '"';
            }
            $replacements[ $iframe->original ] = '[' . self::get_shortcode_tag() . ' src="' . esc_url_raw( $iframe->attrs['src'] ) . '"' . $other_atts . ']';
        }
        return self::make_replacements_to_content( $content, $replacements );
    }

    public static function callback( $attrs, $content = '' ) {
        if ( empty( $attrs['src'] ) ) {
            return '';
        }

        $attrs['src'] = trim( $attrs['src'] );

        $defaults = [
            'height'                => 600,
            'width'                 => 670,
            'disableresponsiveness' => false,
            'allowscrolling'        => false,
            'class'                 => '',
        ];
        $attrs    = array_merge( $defaults, $attrs );

        if ( $attrs['allowscrolling'] ) {
            $scrolling = 'auto';
        } else {
            $scrolling = 'no';
        }

        if ( $attrs['disableresponsiveness'] ) {
            $attrs['class'] .= ' disable-responsiveness';
        }

        $iframe_atts = $attrs;
        unset( $iframe_atts['src'] );
        unset( $iframe_atts['width'] );
        unset( $iframe_atts['height'] );
        unset( $iframe_atts['allowscrolling'] );
        unset( $iframe_atts['scrolling'] );
        unset( $iframe_atts['disableresponsiveness'] );
        unset( $iframe_atts['frameborder'] );
        unset( $iframe_atts['style'] );
        $other_atts = '';
        foreach ( $iframe_atts as $key => $val ) {
            $other_atts .= ' ' . $key . '="' . esc_attr( $val ) . '"';
        }

        return sprintf(
            '<iframe src="%s" width="%s" height="%s" frameborder="0" scrolling="%s" %s></iframe>',
            esc_url( $attrs['src'] ),
            esc_attr( $attrs['width'] ),
            esc_attr( $attrs['height'] ),
            esc_attr( $scrolling ),
            $other_atts
        );
    }
}
