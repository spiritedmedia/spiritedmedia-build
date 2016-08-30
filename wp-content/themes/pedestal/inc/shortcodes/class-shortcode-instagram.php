<?php

namespace Pedestal\Shortcodes;

use function Pedestal\Pedestal;

use \Shortcake_Bakery\Shortcodes\Shortcode;

class Instagram extends Shortcode {

    public static function get_shortcode_ui_args() {
        return [
            'label'          => esc_html__( 'Instagram', 'shortcake-bakery' ),
            'listItemImage'  => '<img src="' . esc_url( SHORTCAKE_BAKERY_URL_ROOT . 'assets/images/svg/icon-instagram.svg' ) . '" />',
            'attrs'          => [
                [
                    'label'        => esc_html__( 'URL', 'shortcake-bakery' ),
                    'attr'         => 'url',
                    'type'         => 'text',
                    'description'  => esc_html__( 'URL to an Instagram', 'shortcake-bakery' ),
                ],
                [
                    'label'        => esc_html__( 'Hide Caption', 'shortcake-bakery' ),
                    'attr'         => 'hidecaption',
                    'type'         => 'checkbox',
                    'description'  => esc_html__( 'By default, the Instagram embed will include the caption. Check this box to hide the caption.', 'shortcake-bakery' ),
                ],
            ],
        ];
    }

    public static function reversal( $content ) {
        return $content;

        if ( false === stripos( $content, '<script' ) && false === stripos( $content, '<iframe' ) && false === stripos( $content, 'class="instagram-media' ) ) {
            return $content;
        }

        $needle = '#<blockquote class="instagram-media.+<a href="(https://instagram\.com/p/[^/]+/)"[^>]+>.+(?=</blockquote>)</blockquote>\n?(<script[^>]+src="//platform\.instagram\.com/[^>]+></script>)?#';
        if ( preg_match_all( $needle, $content, $matches ) ) {
            $replacements = [];
            $shortcode_tag = self::get_shortcode_tag();
            foreach ( $matches[0] as $key => $value ) {
                $replacements[ $value ] = '[' . $shortcode_tag . ' url="' . esc_url_raw( $matches[1][ $key ] ) . '"]';
            }
            $content = self::make_replacements_to_content( $content, $replacements );
        }

        if ( $iframes = self::parse_iframes( $content ) ) {
            $replacements = [];
            foreach ( $iframes as $iframe ) {
                if ( 'instagram.com' !== self::parse_url( $iframe->attrs['src'], PHP_URL_HOST ) ) {
                    continue;
                }
                if ( preg_match( '#//instagram\.com/p/([^/]+)/embed/?#', $iframe->attrs['src'], $matches ) ) {
                    $embed_id = $matches[1];
                } else {
                    continue;
                }
                $replacements[ $iframe->original ] = '[' . self::get_shortcode_tag() . ' url="' . esc_url_raw( 'https://instagram.com/p/' . $embed_id . '/' ) . '"]';
            }
            $content = self::make_replacements_to_content( $content, $replacements );
        }
        return $content;
    }

    public static function callback( $attrs, $content = '' ) {
        global $content_width;

        if ( empty( $attrs['url'] ) ) {
            return '';
        }

        $attrs['url'] = trim( $attrs['url'] );

        $max_width = 698;
        $min_width = 320;

        $defaults = [
            'width'       => isset( $content_width ) ? $content_width : $max_width,
            'hidecaption' => false,
        ];
        $attrs = array_merge( $defaults, $attrs );

        $attrs['width'] = absint( $attrs['width'] );
        if ( $attrs['width'] > $max_width || $min_width > $attrs['width'] ) {
            $attrs['width'] = $max_width;
        }

        $url_args = [
            'width' => $attrs['width'],
        ];

        if ( $attrs['hidecaption'] ) {
            $url_args['hidecaption'] = 'true';
            // Filter oembed_fetch_url to tell the Instagram API to hide the caption
            add_filter( 'oembed_fetch_url', function( $provider, $url, $args ) {
                $provider = add_query_arg( 'hidecaption', 'true', $provider );
                return $provider;
            }, 10, 3 );
        }

        return self::get_oembed_html( $attrs['url'], $url_args );
    }

    /**
     * Get embed HTML using oEmbed, using cache if available
     *
     * @param  string $url  URL
     * @param  array  $args URL args. Optional.
     * @return string        Embed HTML
     */
    protected static function get_oembed_html( $url, $args = [] ) {
        global $post;
        $post_id = $post->ID;
        $tag = self::get_shortcode_tag();

        if ( empty( $post_id ) || empty( $url ) ) {
            return;
        }

        // Check for a cached result (stored in the post meta)
        $key_prefix = "_shortcake_bakery_{$tag}_oembed_";
        $key_suffix = md5( $url . serialize( $args ) );
        $cachekey = $key_prefix . $key_suffix;
        $cachekey_time = $key_prefix . 'time_' . $key_suffix;

        /**
         * Filter the oEmbed TTL value (time to live).
         *
         * @param int    $time    Time to live (in seconds).
         * @param array  $attr    An array of shortcode attributes.
         * @param int    $post_id Post ID.
         */
        $ttl = apply_filters( 'shortcake_bakery_oembed_ttl', DAY_IN_SECONDS, $args, $post_id );
        $cache = get_post_meta( $post_id, $cachekey, true );
        $cache_time = get_post_meta( $post_id, $cachekey_time, true );

        if ( ! $cache_time ) {
            $cache_time = 0;
        }

        $cached_recently = ( time() - $cache_time ) < $ttl;
        if ( $cached_recently ) {
            // Failures are cached. Return if we're using the cache.
            if ( '{{unknown}}' === $cache ) {
                return;
            }
            if ( ! empty( $cache ) ) {
                return $cache;
            }
        }

        $class = Pedestal();
        remove_filter( 'oembed_result', [ $class, 'filter_oembed_result' ], 10, 3 );
        $html = wp_oembed_get( $url, $args );
        add_filter( 'oembed_result', [ $class, 'filter_oembed_result' ], 10, 3 );
        if ( '[' == $html[0] ) {
            return '';
        }

        // Maybe cache the result
        if ( $html ) {
            update_post_meta( $post_id, $cachekey, $html );
            update_post_meta( $post_id, $cachekey_time, time() );
        } elseif ( ! $cache ) {
            update_post_meta( $post_id, $cachekey, '{{unknown}}' );
        }

        // If there was a result, return it
        if ( $html ) {
            return $html;
        }
    }
}
