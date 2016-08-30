<?php

namespace Pedestal\Shortcodes;

use \Shortcake_Bakery\Shortcodes\Shortcode;

class Twitter extends Shortcode {

    public static function get_shortcode_ui_args() {
        return [
            'label'          => esc_html__( 'Twitter', 'pedestal' ),
            'listItemImage'  => '<img src="' . esc_url( SHORTCAKE_BAKERY_URL_ROOT . 'assets/images/svg/icon-twitter.svg' ) . '" />',
            'attrs'          => [
                [
                    'label'        => esc_html__( 'URL', 'pedestal' ),
                    'attr'         => 'url',
                    'type'         => 'text',
                    'description'  => esc_html__( 'URL to a tweet', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Image/Video Visibility', 'pedestal' ),
                    'attr'         => 'media_visibility',
                    'type'         => 'select',
                    'options'      => [
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Should the tweet display its image or video if available?', 'pedestal' ),
                ],
            ],
        ];
    }

    public static function reversal( $content ) {

        if ( false === stripos( $content, '<script' ) ) {
            return $content;
        }

        $needle = '#<blockquote class="twitter-(tweet|video).+<a href="(https://twitter\.com/[^/]+/status/[^/]+)">.+(?=</blockquote>)</blockquote>\n?<script[^>]+src="//platform\.twitter\.com/widgets\.js"[^>]+></script>#';
        if ( preg_match_all( $needle, $content, $matches ) ) {
            $replacements = [];
            $shortcode_tag = self::get_shortcode_tag();
            foreach ( $matches[0] as $key => $value ) {
                $replacements[ $value ] = '[' . $shortcode_tag . ' url="' . esc_url_raw( $matches[2][ $key ] ) . '"]';
            }
            $content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
        }

        return $content;
    }

    public static function callback( $attrs, $content = '' ) {

        if ( empty( $attrs['url'] ) ) {
            return '';
        }

        $url = trim( $attrs['url'] );

        $needle = '#https?://twitter\.com/([^/]+)/status/([^/]+)#';
        if ( preg_match( $needle, $url, $matches ) ) {
            $username = $matches[1];
        } else {
            return '';
        }

        $media_visibility = '';
        if ( isset( $attrs['media_visibility'] ) && 'false' === $attrs['media_visibility'] ) {
            $media_visibility = 'data-cards="hidden"';
        }

        return sprintf( '<blockquote class="twitter-tweet" %s><a href="%s">%s</a></blockquote><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>',
            $media_visibility,
            esc_url( $url ),
            sprintf( esc_html__( 'Tweet from @%s', 'shortcake-bakery' ), $username )
        );
    }
}
