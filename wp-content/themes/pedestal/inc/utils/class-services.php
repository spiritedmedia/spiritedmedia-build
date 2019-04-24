<?php

namespace Pedestal\Utils;

/**
 * Services
 */
class Services {

    /**
     * Map of common third party service domains and URLS and the name of the service
     *
     * @var array
     */
    private static $domain_map = [
        'docs.google.com'   => 'googledocs',
        'documentcloud.org' => 'documentcloud',
        'facebook.com'      => 'facebook',
        'flickr.com'        => 'flickr',
        'giphy.com'         => 'giphy',
        'infogr.am'         => 'infogram',
        'instagr.am'        => 'instagram',
        'instagram.com'     => 'instagram',
        'linkedin.com'      => 'linkedin',
        'scribd.com'        => 'scribd',
        'soundcloud.com'    => 'soundcloud',
        'twitter.com'       => 'twitter',
        'vine.co'           => 'vine',
        'youtu.be'          => 'youtube',
        'youtube.com'       => 'youtube',
    ];

    /**
     * Service labels
     *
     * @var array Service name => Service label
     */
    private static $labels = [
        'documentcloud' => 'DocumentCloud',
        'facebook'      => 'Facebook',
        'flickr'        => 'Flickr',
        'giphy'         => 'Giphy',
        'googledocs'    => 'Google Docs',
        'infogram'      => 'Infogram',
        'instagram'     => 'Instagram',
        'linkedin'      => 'LinkedIn',
        'scribd'        => 'Scribd',
        'soundcloud'    => 'SoundCloud',
        'twitter'       => 'Twitter',
        'vine'          => 'Vine',
        'youtube'       => 'YouTube',
    ];

    /**
     * Services whose embeds we support
     *
     * @var array
     */
    protected static $embeddable_services = [
        'documentcloud' => '',
        'facebook'      => '',
        'flickr'        => '',
        'giphy'         => '',
        'googledocs'    => '',
        'infogram'      => '',
        'instagram'     => '',
        'scribd'        => '',
        'soundcloud'    => '',
        'twitter'       => '',
        'vine'          => '',
        'youtube'       => '',
    ];

    /**
     * Instance
     *
     * @var object
     */
    private static $instance;

    /**
     * Setup instance
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * Get the name of a social media or embed service from a URL
     *
     * Not limited to services that provide embeddable content; also includes
     * other commonly referenced sites.
     *
     * @param string $url URL
     * @return string|false
     */
    public static function get_service_name_from_url( string $url ) {
        if ( ! $url ) {
            return false;
        }

        $url_domain = Utils::get_domain_from_url( $url );
        $services   = static::$domain_map;
        if ( ! isset( $services[ $url_domain ] ) ) {
            return false;
        }

        return $services[ $url_domain ];
    }

    /**
     * Get the label for a service
     *
     * @see Services::$labels
     * @param string $service_name
     * @return string
     */
    public static function get_service_label( $service_name ) {
        if ( ! is_string( $service_name ) ) {
            return '';
        }
        return static::$labels[ $service_name ] ?? '';
    }

    /**
     * Get the embeddable services
     *
     * @param boolean $labels [true] Include labels as values?
     * @return array Name => Label or empty string
     */
    public static function get_embeddable_services( $labels = true ) {
        $services = self::$embeddable_services;
        if ( $labels ) {
            array_walk( $services, function( &$value, $key ) {
                $value = static::get_service_label( $key );
            } );
        }
        return $services;
    }

    /**
     * Is the supplied service's URL embeddable?
     *
     * Embeddable services are defined in static::$embeddable_services
     *
     * @param  string  $service_name Service name
     * @return boolean
     */
    public static function is_embeddable_service( $service_name ) {
        if ( ! is_string( $service_name ) ) {
            return false;
        }
        $with_labels = false;
        $embeddable  = static::get_embeddable_services( $with_labels );
        return ! empty( $embeddable[ $service_name ] );
    }

    /**
     * Given a service name, get the pattern for getting an ID from a service URL
     *
     * @param  string $service_name Service name
     * @return string Regexp for getting ID
     */
    public static function get_service_url_pattern( $service_name ) {
        switch ( $service_name ) {
            case 'twitter':
                // https://stackoverflow.com/a/4138539/1801260
                // 0: Full URL
                // 1: Username
                // 2: Hashbang? (modern Twitter URLs won't have this)
                // 3: Status ID
                return '/^https?:\/\/twitter\.com\/(?:#!\/)?(\w+)\/status(es)?\/(\d+)$/';

            case 'instagram':
                // 0: Full URL
                // 1: www?
                // 2: TLD
                // 3: ID
                return '/https?:\/\/(www\.)?instagr(\.am|am\.com)\/p\/([a-zA-Z0-9-_]+)/i';

            case 'youtube':
                // 0: Full URL
                // 1: ID
                return '/https?:\/\/youtu\.be\/([a-zA-Z0-9-]+)/i';

            default:
                return '';
        }
    }

    /**
     * Get a Twitter username from a URL
     *
     * @param  string $url Twitter URL
     * @return string      Twitter username
     */
    public static function get_twitter_username_from_url( $url ) {
        $pattern = self::get_service_url_pattern( 'twitter' );
        preg_match( $pattern, $url, $matches );
        return isset( $matches[1] ) ? $matches[1] : '';
    }

    /**
     * Get a Twitter status ID from a URL
     *
     * @param  string $url Twitter URL
     * @return int         Twitter status ID
     */
    public static function get_twitter_status_id_from_url( $url ) {
        $pattern = self::get_service_url_pattern( 'twitter' );
        preg_match( $pattern, $url, $matches );
        return isset( $matches[3] ) ? $matches[3] : 0;
    }

    /**
     * Get an Instagram ID from a URL
     *
     * @param  string $url Instagram post URL
     * @return string      Instagram ID
     */
    public static function get_instagram_id_from_url( $url ) {
        $pattern = self::get_service_url_pattern( 'instagram' );
        preg_match( $pattern, $url, $matches );
        if ( ! empty( $matches[3] ) ) {
            return $matches[3];
        } else {
            return '';
        }
    }

    /**
     * Get an Instagram username from a URL
     *
     * @param string $url URL to an Instagram post or user profile
     * @return string
     */
    public static function get_instagram_username_from_url( $url ) {
        $handle = str_ireplace( 'https://www.instagram.com/', '', $url );
        // Strip whitespace characters and / from both ends of the string
        $handle = trim( $handle, " \t\n\r\0\x0B\/" );
        return $handle;
    }

}
