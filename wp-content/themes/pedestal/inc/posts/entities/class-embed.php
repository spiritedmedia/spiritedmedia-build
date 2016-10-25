<?php

namespace Pedestal\Posts\Entities;

use \Pedestal\Utils\Utils;

class Embed extends Entity {

    /**
     * Embed type
     *
     * @var string
     */
    protected $embed_type = '';

    protected static $post_type = 'pedestal_embed';

    /**
     * Providers we support, with labels
     *
     * @var array
     */
    protected static $supported_providers = [
        'youtube'    => 'YouTube',
        'twitter'    => 'Twitter',
        'instagram'  => 'Instagram',
        'vine'       => 'Vine',
        'facebook'   => 'Facebook',
        'scribd'     => 'Scribd',
        'flickr'     => 'Flickr',
        'giphy'      => 'Giphy',
        'infogram'   => 'Infogram',
        'soundcloud' => 'SoundCloud',
    ];

    /**
     * Add the embed type to the classes
     */
    public function get_css_classes() {
        $classes = parent::get_css_classes();
        if ( $embed_type = $this->get_embed_type() ) {
            $classes = array_merge( [
                'embed-' . $embed_type
            ], $classes );
        }
        return $classes;
    }

    /**
     * Setup data attributes
     */
    public function set_data_atts() {
        parent::set_data_atts();
        $atts = parent::get_data_atts();
        $new_atts = [
            'source-name' => $this->get_source(),
        ];
        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Get the providers we support
     *
     * @return array
     */
    public static function get_providers() {
        return self::$supported_providers;
    }

    /**
     * Get the Instagram of the Day date as Unix timestamp
     *
     * @return int Unix timestamp
     */
    public function get_daily_insta_date() {
        return $this->get_meta( 'daily_insta_date' );
    }

    /**
     * Get the name of the author of the embedded media
     *
     * This is *not* the same as the author of our Embed post.
     *
     * @return string|false
     */
    public function get_embed_author_name() {
        return $this->get_meta( 'embed_author_name' );
    }

    /**
     * Get the Embed's caption
     *
     * @return string|false
     */
    public function get_embed_caption() {
        return $this->get_meta( 'embed_caption' );
    }

    /**
     * Get the embed URL for the post
     *
     * @return string
     */
    public function get_embed_url() {
        return $this->get_meta( 'embed_url' );
    }

    /**
     * Set the embed URL for the post
     *
     * @return string
     */
    public function set_embed_url( $value ) {
        return $this->set_meta( 'embed_url', $value );
    }

    /**
     * Whether or not this embed has a featured image
     */
    public function has_featured_image() {

        switch ( $this->get_embed_type() ) {
            case 'instagram':

                $embed_data = $this->get_embed_data();
                if ( ! empty( $embed_data['image_url_large'] ) ) {
                    return true;
                } else {
                    return false;
                }
                break;

            case 'youtube':

                $embed_data = $this->get_embed_data();
                if ( ! empty( $embed_data['thumbnail_url'] ) ) {
                    return true;
                } else {
                    return false;
                }
                break;

        }
        return false;
    }

    /**
     * Get the featured image url
     *
     * @param string $size
     * @param array $args
     * @return string|false
     */
    public function get_featured_image_url( $size = 'full', $args = [] ) {

        $image_url = '';
        $embed_data = $this->get_embed_data();
        switch ( $this->get_embed_type() ) {
            case 'youtube':
                if ( ! empty( $embed_data['thumbnail_url'] ) ) {
                    $image_url = $embed_data['thumbnail_url'];
                }
                break;

            case 'instagram':
                if ( ! empty( $embed_data['image_url_large'] ) ) {
                    $image_url = $embed_data['image_url_large'];
                }
                break;
        }

        return $image_url;

    }

    /**
     * Get the HTML for the featured image
     *
     * @return string
     */
    public function get_featured_image_html( $size = 'full', $args = [] ) {

        $html = '';
        switch ( $this->get_embed_type() ) {
            case 'youtube':
            case 'instagram':

                $image_url = $this->get_featured_image_url();

                if ( is_string( $size ) ) {
                    $size_meta = Utils::get_image_sizes( $size );
                    if ( ! $size_meta ) {
                        break;
                    }
                    $width = $size_meta['width'];
                    $height = $size_meta['height'];
                } elseif ( is_array( $size ) ) {
                    list( $width, $height ) = $size;
                }

                $html = sprintf(
                    '<img class="size-%s" src="%s" />',
                    esc_attr( $size ),
                    $this->maybe_resize_image_src( $image_url, [ 'width' => $width, 'height' => $height ] )
                );

                break;

        }

        return $html;

    }

    /**
     * Get this Embed's HTML representation
     *
     * @return string
     */
    public function get_embed_html() {
        $args = [];
        $args['url'] = $this->get_embed_url();
        $args['caption'] = $this->get_embed_caption();
        $args['media_visibility'] = 'true';
        if ( ! is_singular( self::$post_type ) ) {
            $args['media_visibility'] = 'false';
        }

        $html = self::do_embed( $args );

        if ( ! empty( $html ) ) {
            $html = '<div class="' . esc_attr( 'pedestal-embed pedestal-embed-' . $this->get_embed_type() ) . '">' . $html . '</div>';
        }
        return $html;
    }

    /**
     * Get the HTML representation of an embed from a URL
     *
     * @param  array $args URL and other settings
     * @return string      Embed HTML
     */
    public static function do_embed( $args ) {
        $html = '';
        $url = $args['url'];

        $embed_type = self::get_embed_type_from_url( $url );
        if ( ! $embed_type ) {
            return '';
        }

        $shortcode = sprintf( '[%s url="%s" ', $embed_type, $url );

        switch ( $embed_type ) {
            case 'twitter':
                $media_visibility = 'true';
                if ( isset( $args['media_visibility'] ) ) {
                    $media_visibility = $args['media_visibility'];
                }
                $shortcode .= sprintf( 'media_visibility="%s" ', $media_visibility );
                break;
        }

        $shortcode .= sprintf( 'caption="%s"]', $args['caption'] );
        return do_shortcode( $shortcode );
    }

    /**
     * Get the embed data for the embed
     *
     * @return mixed
     */
    public function get_embed_data() {
        $key = $this->get_embed_data_key();
        if ( ! $key ) {
            return false;
        }
        return $this->get_meta( $key );
    }

    /**
     * Set the embed data for the embed
     *
     * @param mixed
     */
    public function set_embed_data( $embed_data ) {
        $key = $this->get_embed_data_key();
        if ( ! $key ) {
            return false;
        }
        $this->set_meta( $key, $embed_data );
    }

    /**
     * Get the meta key for the embed data
     */
    protected function get_embed_data_key() {
        $url = $this->get_embed_url();
        if ( ! $url ) {
            return false;
        }
        return 'embed_data_' . md5( $url );
    }

    /**
     * Store an oEmbed data property as Embed post meta
     *
     * @param string $property Name of the oEmbed property
     * @param string $url      Optional URL to get the oEmbed data for. Defaults
     *     to the instantiated Embed's URL.
     * @return void
     */
    public function set_embed_meta_from_oembed( string $property, string $url = '' ) {
        if ( empty( $url ) ) {
            $url = $this->get_embed_url();
        }
        $oembed_data = $this->get_oembed_data( $url );
        if ( is_object( $oembed_data ) && property_exists( $oembed_data, $property ) ) {
            $this->set_meta( 'embed_' . $property, $oembed_data->$property );
        }
    }

    /**
     * Get the oEmbed data for a URL
     *
     * @param  string $url Optional URL to get the oEmbed data for. Defaults
     *     to the instantiated Embed's URL.
     * @return object|false Object of oEmbed data if successful, false if not
     */
    protected function get_oembed_data( string $url = '' ) {
        if ( empty( $url ) ) {
            $url = $this->get_embed_url();
        }

        $cache_key = 'oembed_' . $url;

        if ( $data = wp_cache_get( $cache_key ) ) {
            return $data;
        }

        $wp_oembed = new \WP_oEmbed;
        $data = $wp_oembed->fetch( $this->get_oembed_provider_url( $url ), $url );
        wp_cache_set( $cache_key, $data );
        return $data;
    }

    /**
     * Get the oEmbed provider URL for a given URL
     *
     * @param  string $url Optional URL to get the provider URL for. Defaults
     *     to the instantiated Embed's URL.
     * @return string|false Provider URL if successful, false if not
     */
    protected function get_oembed_provider_url( string $url = '' ) {
        if ( empty( $url ) ) {
            $url = $this->get_embed_url();
        }
        $wp_oembed = new \WP_oEmbed;
        return $wp_oembed->get_provider( $url );
    }

    /**
     * Get the name of the icon for this entity's source
     *
     * The name should align with Font Awesome icon names. If there is no
     * equivalent, then just use `external-link`.
     *
     * @return string
     */
    public function get_source_icon_name() {
        $embed_type = $this->get_embed_type();
        switch ( $embed_type ) {
            case 'giphy':
            case 'infogram':
                return 'external-link';
                break;

            default:
                return $embed_type;
                break;
        }
    }

    /**
     * Get the source for the embed
     *
     * @return string
     */
    public function get_source() {
        if ( $embed_type = $this->get_embed_type() ) {
            $sources = self::get_providers();
            return $sources[ $embed_type ];
        } else {
            return '';
        }
    }

    /**
     * Get the Embed type
     *
     * @return string|false
     */
    public function get_embed_type() {
        if ( $this->embed_type ) {
            return $this->embed_type;
        }
        $this->embed_type = static::get_embed_type_from_url( $this->get_embed_url() );
        return $this->embed_type;
    }

    /**
     * Set the embed type
     *
     * @param string $embed_type Embed type
     */
    public function set_embed_type( string $embed_type = '' ) {
        if ( empty( $embed_type ) ) {
            $embed_type = $this->get_embed_type();
        }
        $this->set_meta( 'embed_type', $embed_type );
    }

    /**
     * Get an embed type string from a URL
     *
     * @param string $url URL
     * @return string|false
     */
    public static function get_embed_type_from_url( string $url = '' ) {

        if ( ! $url ) {
            return false;
        }

        $domain = parse_url( $url, PHP_URL_HOST );

        $base_types = [
            'twitter.com'     => 'twitter',
            'instagram.com'   => 'instagram',
            'instagr.am'      => 'instagram',
            'youtube.com'     => 'youtube',
            'youtu.be'        => 'youtube',
            'vine.co'         => 'vine',
            'facebook.com'    => 'facebook',
            'scribd.com'      => 'scribd',
            'flickr.com'      => 'flickr',
            'giphy.com'       => 'giphy',
            'infogr.am'       => 'infogram',
            'soundcloud.com'  => 'soundcloud',
        ];
        $types = $base_types;
        foreach ( $base_types as $k => $type ) {
            $prefixed_domain = 'www.' . $k;
            $types[ $prefixed_domain ] = $type;
        }

        if ( isset( $types[ $domain ] ) ) {
            return $types[ $domain ];
        } else {
            return false;
        }

    }

    /**
     * Update stored embed data without overwriting existing data
     */
    public function update_embed_data() {
        if ( ! $this->get_embed_data() ) {
            $this->set_embed_data( $this->fetch_embed_data() );
        }
    }

    /**
     * Fetch embed data from the remote source
     */
    public function fetch_embed_data() {

        $url = $this->get_embed_url();
        if ( ! $url ) {
            return;
        }

        switch ( $this->get_embed_type() ) {
            case 'youtube':
                $id = static::get_youtube_id_from_url( $url );
                if ( ! $id ) {
                    break;
                }

                $youtube_url = sprintf( 'http://www.youtube.com/watch?v=%s', $id );
                $request_url = 'http://www.youtube.com/oembed?format=json&maxheight=9999&maxwidth=9999&url=' . urlencode( $youtube_url );
                $response = wp_remote_get( $request_url );
                if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
                    break;
                }

                $body = json_decode( wp_remote_retrieve_body( $response ) );
                // See if there's a high-res version
                $high_res = str_replace( 'hqdefault.jpg', 'maxresdefault.jpg', $body->thumbnail_url );
                $high_res_request = wp_remote_head( $high_res );
                if ( 200 === wp_remote_retrieve_response_code( $high_res_request ) ) {
                    $thumbnail_url = $high_res;
                } else {
                    $thumbnail_url = $body->thumbnail_url;
                }

                return [
                    'youtube_id'      => $id,
                    'thumbnail_url'   => $thumbnail_url,
                ];
                break;

            case 'instagram':
                $id = static::get_instagram_id_from_url( $url );
                if ( ! $id ) {
                    break;
                }

                $embed_data = [
                    'instagram_id'      => $id,
                ];
                $image_url = sprintf( 'http://instagram.com/p/%s/media/?size=l', $id );
                $response = wp_remote_get( $image_url, [ 'redirection' => 0 ] );

                // If the image URL is not redirected to the actual image, then
                // bail and return nothing
                if ( 301 || 302 == wp_remote_retrieve_response_code( $response ) ) {
                    $image_url_large = wp_remote_retrieve_header( $response, 'location' );
                    $size = getimagesize( $image_url_large );
                    $embed_data['image_url_large'] = $image_url_large;
                    $embed_data['width'] = $size[0];
                    $embed_data['height'] = $size[1];
                    return $embed_data;
                }

                break;
        }

    }

    /**
     * Get the Embed's Twitter username
     *
     * @return string Twitter username
     */
    public function get_twitter_username() {
        return static::get_twitter_username_from_url( $this->get_embed_url() );
    }

    /**
     * Get a Twitter username from a URL
     *
     * @param  string $url Twitter URL
     * @return string      Twitter username
     */
    public static function get_twitter_username_from_url( $url ) {
        $pattern = self::get_embed_type_url_pattern( 'twitter' );
        preg_match( $pattern, $url, $matches );
        return isset( $matches[3] ) ? $matches[3] : '';
    }

    /**
     * Get the Embed's Twitter status ID
     *
     * @return string Twitter status ID
     */
    protected function get_twitter_status_id() {
        return self::get_twitter_status_id_from_url( $this->get_embed_url() );
    }

    /**
     * Get a Twitter status ID from a URL
     *
     * @param  string $url Twitter URL
     * @return int         Twitter status ID
     */
    public static function get_twitter_status_id_from_url( $url ) {
        $pattern = self::get_embed_type_url_pattern( 'twitter' );
        preg_match( $pattern, $url, $matches );
        return isset( $matches[3] ) ? $matches[3] : 0;
    }

    /**
     * Get the Embed's Instagram ID
     *
     * @return string
     */
    protected function get_instagram_id() {
        return self::get_instagram_id_from_url( $this->get_embed_url() );
    }

    /**
     * Get an Instagram ID from a URL
     *
     * @param  string $url Instagram post URL
     * @return string      Instagram ID
     */
    public static function get_instagram_id_from_url( $url ) {
        $pattern = self::get_embed_type_url_pattern( 'instagram' );
        preg_match( $pattern, $url, $matches );
        if ( ! empty( $matches[3] ) ) {
            return $matches[3];
        } else {
            return '';
        }
    }

    /**
     * Get the Embed's YouTube ID
     *
     * @return string
     */
    protected function get_youtube_id() {
        return self::get_youtube_id_from_url( $this->get_embed_url() );
    }

    /**
     * Get a YouTube ID from a URL
     *
     * @param  string $url YouTube URL
     * @return string      YouTube ID
     */
    public static function get_youtube_id_from_url( $url ) {
        $data = self::get_youtube_data_from_url( $url );
        if ( ! empty( $data['id'] ) ) {
            return $data['id'];
        }
        return '';
    }

    /**
     * Get a YouTube playlist ID from a URL
     *
     * @param  string $url YouTube URL
     * @return string      YouTube playlist ID
     */
    public static function get_youtube_list_id_from_url( $url ) {
        $data = self::get_youtube_data_from_url( $url );
        if ( ! empty( $data['list'] ) ) {
            return $data['list'];
        }
        return '';
    }

    /**
     * Get YouTube data from URL
     *
     * @param  string $url YouTube URL
     * @return array
     */
    public static function get_youtube_data_from_url( $url ) {

        $query = [];
        $host = parse_url( $url, PHP_URL_HOST );
        $query_str = str_replace( '&amp;', '&', Utils::parse_url( $url, PHP_URL_QUERY ) );
        parse_str( $query_str, $query_args );

        if ( 'youtu.be' == $host ) {
            $pattern = self::get_embed_type_url_pattern( 'youtube' );
            preg_match( $pattern, $url, $matches );
            if ( ! empty( $matches[1] ) ) {
                $query['id'] = $matches[1];
            } else {
                return '';
            }
        } elseif ( 'www.youtube.com' == $host ) {
            if ( ! empty( $query_args['v'] ) ) {
                $query['id'] = $query_args['v'];
            } else {
                return '';
            }
        } else {
            return '';
        }

        if ( ! empty( $query_args['list'] ) ) {
            $query['list'] = $query_args['list'];
        }

        return $query;

    }

    /**
     * Given an embed type, get the URL pattern for getting embed ID
     *
     * @param  string $embed_type Embed type
     * @return string             Regexp for getting embed ID
     */
    public static function get_embed_type_url_pattern( $embed_type ) {
        switch ( $embed_type ) {
            case 'twitter':
                return '|https?://(www\.)?twitter\.com/(#!/)?@?([^/\?]*)|';
                break;

            case 'instagram':
                return '/https?:\/\/(www\.)?instagr(\.am|am\.com)\/p\/([a-zA-Z0-9-_]+)/i';
                break;

            case 'youtube':
                return '/https?:\/\/youtu\.be\/([a-zA-Z0-9-]+)/i';
                break;
        }
    }

    /**
     * Get the Instagram of the Day for a given date
     *
     * @param  string $date Optional date string. Defaults to current date.
     * @return HTML Rendered template
     */
    public static function get_instagram_of_the_day( string $date = '' ) {
        $date_format = 'Y-m-d';

        if ( $date ) {
            $date = date( $date_format, strtotime( $date ) );
        } else {
            $date = current_time( $date_format );
        }

        $args = [
            'post_type'              => static::$post_type,
            'post_status'            => [ 'publish', 'future' ],
            'posts_per_page'         => 1,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key' => 'daily_insta_date',
                    'value' => strtotime( $date ),
                ],
                [
                    'key' => 'embed_type',
                    'value' => 'instagram',
                ],
            ],
        ];
        $posts = static::get_posts( $args );
        if ( empty( $posts ) ) {
            return false;
        }

        $daily_insta = $posts[0];
        if ( ! $daily_insta instanceof self ) {
            return '';
        }

        $context = \Timber\Timber::get_context();
        $context['item'] = $daily_insta;

        ob_start();
        \Timber\Timber::render( 'partials/daily-insta.twig', $context );
        $html = ob_get_clean();
        return $html;
    }
}
