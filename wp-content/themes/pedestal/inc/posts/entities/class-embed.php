<?php

namespace Pedestal\Posts\Entities;

use function Pedestal\Pedestal;
use Pedestal\Utils\Utils;
use Pedestal\Posts\Clusters\Story;
use Pedestal\Objects\{
    Stream,
    YouTube
};

class Embed extends Entity {

    /**
     * Embed type
     *
     * @var string
     */
    protected $embed_type = '';

    protected static $post_type = 'pedestal_embed';

    /**
     * Services whose embeds we support, with labels
     *
     * @var array
     */
    protected static $embeddable_services = [
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
        $embed_type = $this->get_embed_type();
        if ( $embed_type ) {
            $classes = array_merge( [
                'embed-' . $embed_type,
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
     * Get the embeddable services
     *
     * @return array
     */
    public static function get_embeddable_services() {
        return self::$embeddable_services;
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
                $html = sprintf( '<img src="%s" />', esc_url( $this->get_featured_image_url() ) );
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
        $args = [
            'url'           => $this->get_embed_url(),
            'caption'       => $this->get_embed_caption(),
            'display_media' => 'true',
        ];

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

        $embed_type = static::get_embed_type_from_url( $url );
        if ( ! $embed_type ) {
            return '';
        }

        $shortcode = sprintf( '[%s url="%s" ', $embed_type, $url );

        switch ( $embed_type ) {
            case 'twitter':
                $twitter_settings = [
                    'display_media'  => 'true',
                    'exclude_parent' => 'false',
                ];
                foreach ( $twitter_settings as $key => $value ) {
                    if ( isset( $args[ $key ] ) && is_string( $args[ $key ] ) ) {
                        $value = $args[ $key ];
                    }
                    $shortcode .= sprintf( '%s="%s" ', $key, $value );
                }
                break;
        }
        if ( ! empty( $args['caption'] ) ) {
            $shortcode .= sprintf( 'caption="%s"', $args['caption'] );
        }
        $shortcode .= ']';
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
        $oembed_data = static::get_oembed_data( $url );
        if ( is_object( $oembed_data ) && property_exists( $oembed_data, $property ) ) {
            $this->set_meta( 'embed_' . $property, $oembed_data->$property );
        }
    }

    /**
     * Get the oEmbed data for a URL
     *
     * @param  string $url URL to get the oEmbed data for.
     * @return object|false Object of oEmbed data if successful, false if not
     */
    public static function get_oembed_data( string $url ) {
        $cache_key = 'oembed_' . $url;
        $data = wp_cache_get( $cache_key );

        if ( $data ) {
            return $data;
        }

        $wp_oembed = new \WP_oEmbed;
        $data = $wp_oembed->fetch( static::get_oembed_provider_url( $url ), $url );
        wp_cache_set( $cache_key, $data );
        return $data;
    }

    /**
     * Get the oEmbed provider URL for a given URL
     *
     * @param  string $url URL to get the provider URL for.
     * @return string|false Provider URL if successful, false if not
     */
    public static function get_oembed_provider_url( string $url ) {
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
        $embed_type = $this->get_embed_type();
        if ( $embed_type ) {
            $sources = self::get_embeddable_services();
            return $sources[ $embed_type ];
        }
        return '';
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
                $id = YouTube::get_video_id_from_url( $url );
                if ( ! $id ) {
                    break;
                }

                $youtube_url = YouTube::get_url_from_id( $id );
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
                $response = wp_remote_get( $image_url, [
                    'redirection' => 0,
                ] );

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
        }// End switch().

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
        return YouTube::get_video_id_from_url( $this->get_embed_url() );
    }

    /**
     * Get a valid embed type from a URL
     *
     * An embed type is a service that is embeddable as defined in
     * static::$embeddable_services
     *
     * @see Utils::get_service_name_from_url()
     *
     * @param  string       $url URL
     * @return string|false      Embed type / service name
     */
    public static function get_embed_type_from_url( string $url = '' ) {
        if ( ! $url ) {
            return false;
        }

        $service_name = Utils::get_service_name_from_url( $url );
        if ( ! static::is_embeddable_service( $service_name ) ) {
            return false;
        }

        return $service_name;
    }

    /**
     * Is the supplied service's URL embeddable?
     *
     * Embeddable services are defined in static::$embeddable_services
     *
     * @see Utils::get_service_name_from_url()
     *
     * @param  string  $service_name Provider name as returned by
     *     Utils::get_service_name_from_url()
     * @return boolean
     */
    public static function is_embeddable_service( string $service_name ) {
        if ( array_key_exists( $service_name, static::get_embeddable_services() ) ) {
            return true;
        }
        return false;
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
     * @param  array  $options Rendering and query options
     * @return HTML Rendered template
     */
    public static function get_instagram_of_the_day( $options = [] ) {
        $date_format = 'Y-m-d';

        $options = wp_parse_args( $options, [
            'date'              => current_time( $date_format ),
            'fallback_previous' => false,
            'context'           => '',
        ] );
        $date = strtotime( $options['date'] );

        $date_query_args = [
            'key' => 'daily_insta_date',
            'value' => $date,
        ];

        if ( $options['fallback_previous'] ) {
            $date_query_args = [
                'relation' => 'OR',
                $date_query_args,
                [
                    'key' => 'daily_insta_date',
                    'value' => $date - DAY_IN_SECONDS,
                ],
            ];
        }

        $meta_query = [
            'relation' => 'AND',
            [
                'key' => 'embed_type',
                'value' => 'instagram',
            ],
            $date_query_args,
        ];

        $args = [
            'post_type'              => static::$post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'meta_query'             => $meta_query,
            'orderby'                => 'meta_value_num date',
            'paged'                  => 1,
        ];
        if ( 'newsletter' == $options['context'] ) {
            $args['post_status'] = [ 'publish', 'future' ];
        }
        $posts = Stream::get( $args );
        if ( empty( $posts ) ) {
            return false;
        }

        $daily_insta = $posts[0];
        if ( ! $daily_insta instanceof self ) {
            return '';
        }

        $context = \Timber\Timber::get_context();
        $context['item'] = $daily_insta;

        $context['classes'] = '';
        if ( $options['context'] ) {
            $context['classes'] = 'c-daily-insta--' . $options['context'];
        }

        $daily_insta_story = static::get_daily_insta_story();
        if ( $daily_insta_story && $daily_insta_story instanceof Story ) {
            $context['story_url'] = $daily_insta_story->get_permalink();
        }

        $template = 'partials/daily-insta.twig';
        if ( Pedestal()->is_email() ) {
            $template = 'emails/messages/partials/daily-insta.twig';
        }

        ob_start();
        \Timber\Timber::render( $template, $context );
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Get the Instagram of the Day Story object
     *
     * @return Story|false
     */
    public static function get_daily_insta_story() {
        return static::get_by_post_name( 'instagram-of-the-day', [
            'post_type' => 'pedestal_story',
        ] );
    }
}
