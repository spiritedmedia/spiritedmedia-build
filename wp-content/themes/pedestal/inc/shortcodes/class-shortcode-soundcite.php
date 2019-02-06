<?php

namespace Pedestal\Shortcodes;

use \Shortcake_Bakery\Shortcodes\Shortcode;

class Soundcite extends Shortcode {

    public static function get_shortcode_ui_args() {
        return [
            'label'         => esc_html__( 'SoundCite', 'pedestal' ),
            'listItemImage' => 'dashicons-controls-volumeon',
            'inner_content' => [
                'label'       => esc_html__( 'Content', 'pedestal' ),
                'description' => esc_html__( 'This is the content to be wrapped in the SoundCite player.', 'pedestal' ),
            ],
            'attrs'         => [
                [
                    'label'       => esc_html__( 'ID', 'pedestal' ),
                    'attr'        => 'id',
                    'type'        => 'number',
                    'description' => esc_html__( 'data-id &mdash; if your media is from a URL, then you can skip this field and use the URL field below.', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'URL', 'pedestal' ),
                    'attr'        => 'url',
                    'type'        => 'url',
                    'description' => esc_html__( 'data-url &mdash; if your media is from SoundCloud, then you can skip this field and use the ID field above.', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'Start Time', 'pedestal' ),
                    'attr'        => 'start',
                    'type'        => 'number',
                    'description' => esc_html__( 'data-start', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'End Time', 'pedestal' ),
                    'attr'        => 'end',
                    'type'        => 'number',
                    'description' => esc_html__( 'data-end', 'pedestal' ),
                ],
                [
                    'label'       => esc_html__( 'Number of Loops', 'pedestal' ),
                    'attr'        => 'plays',
                    'type'        => 'number',
                    'description' => esc_html__( 'data-plays', 'pedestal' ),
                ],
            ],
        ];
    }

    public static function callback( $attrs, $content = '' ) {
        $required_attrs = [ 'id', 'start', 'end', 'plays' ];
        // if ( $required_attrs != $attrs || empty( $content ) ) {
        //     return '';
        // }

        if ( ! empty( $attrs['id'] ) ) {
            $src = 'data-id="' . $attrs['id'] . '"';
        } elseif ( ! empty( $attrs['url'] ) ) {
            $src = 'data-url="' . $attrs['url'] . '"';
        }

        if ( empty( $src ) ) {
            return '';
        }

        return sprintf( '<span class="soundcite" %s data-start="%d" data-end="%d" data-plays="%d">%s</span>',
            $src,
            $attrs['start'],
            $attrs['end'],
            $attrs['plays'],
            $content
        );
    }
}
