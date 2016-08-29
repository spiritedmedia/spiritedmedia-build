<?php

namespace Pedestal\Shortcodes;

use \Shortcake_Bakery\Shortcodes\Shortcode;

class Script extends Shortcode {

    public static function get_shortcode_ui_args() {
        $args = [
            'label'          => esc_html__( 'Script', 'shortcake-bakery' ),
            'listItemImage'  => 'dashicons-media-code',
            'attrs'          => [
                [
                    'label'        => esc_html__( 'URL', 'shortcake-bakery' ),
                    'attr'         => 'src',
                    'type'         => 'text',
                    'description'  => esc_html__( 'Full URL to the script file.', 'shortcake-bakery' ),
                ],
            ],
        ];
        return $args;
    }

    public static function reversal( $content ) {

        if ( $scripts = self::parse_scripts( $content ) ) {
            $replacements = [];
            $shortcode_tag = static::get_shortcode_tag();
            foreach ( $scripts as $script ) {
                $shortcode_attrs = $script->attrs;
                unset( $shortcode_attrs['src'] );
                $other_attrs = '';
                foreach ( $shortcode_attrs as $key => $val ) {
                    $other_attrs .= ' ' . $key . '="' . esc_attr( $val ) . '"';
                }

                $replacements[ $script->original ] = '[' . $shortcode_tag . ' src="' . esc_url_raw( $script->attrs['src'] ) . '"' . $other_attrs . ']';
            }
            $content = self::make_replacements_to_content( $content, $replacements );
        }
        return $content;
    }

    public static function callback( $attrs, $content = '' ) {
        if ( empty( $attrs['src'] ) ) {
            return '';
        }

        $src = trim( $attrs['src'] );
        unset( $attrs['src'] );
        $other_attrs = '';

        foreach ( $attrs as $key => $val ) {
            $other_attrs .= ' ' . $key . '="' . esc_attr( $val ) . '"';
        }

        return '<script src="' . esc_url( $src ) . '"' . $other_attrs . '></script>';
    }
}
