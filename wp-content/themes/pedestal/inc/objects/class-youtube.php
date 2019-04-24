<?php

namespace Pedestal\Objects;

use Pedestal\Utils\Services;
use Pedestal\Utils\Utils;
use Pedestal\Posts\Entities\Embed;

/**
 * YouTube Data API and utility methods
 *
 * The API key constant needs to be added to wp-config.php
 * See https://console.developers.google.com/apis/credentials?project=spirited-media&organizationId=664564141935
 * define( 'YOUTUBE_DATA_API_KEY', '<api key goes here>' );
 */
class YouTube {

    /**
     * Transient key for the latest site channel video ID
     *
     * @var string
     */
    private $transient_key_latest_channel_video = 'yt_site_channel_latest_video';

    /**
     * Check that we have the proper API credentials set up
     */
    public function __construct() {
        if ( ! defined( 'YOUTUBE_DATA_API_KEY' ) ) {
            wp_die( '<a href="https://console.developers.google.com/apis/credentials?project=spirited-media&organizationId=664564141935">YouTube Data API credentials</a> are missing.' );
        }
    }

    /**
     * Get the YouTube ID of the latest video posted to the site's channel
     *
     * @link https://developers.google.com/youtube/v3/docs/search/list
     * @see PEDESTAL_YOUTUBE_CHANNEL_ID
     *
     * @return string|false YouTube ID or false on failure
     */
    public function get_latest_site_channel_video_id() {
        $transient_key = $this->transient_key_latest_channel_video;
        $video_id      = get_transient( $transient_key );

        if ( $video_id ) {
            return $video_id;
        }

        $response = $this->get_api_request( 'search', [
            'order'      => 'date',
            'channelId'  => PEDESTAL_YOUTUBE_CHANNEL_ID,
            'maxResults' => 1,
        ] );
        $data     = $this->get_data_from_response( $response );

        if ( empty( $data ) || empty( $data->id ) || empty( $data->id->videoId ) ) {
            return false;
        }

        $video_id = $data->id->videoId;
        set_transient( $transient_key, $video_id, DAY_IN_SECONDS );
        return $video_id;
    }

    /**
     * Get an array of src and srcset data for video thumbnails
     *
     * The `src` key contains the URL to the highest-resolution thumbnail
     * available @ 640px width or less.
     *
     * The `srcset` key contains an array of widths => URLs.
     *
     * @link https://developers.google.com/youtube/v3/docs/thumbnails
     *
     * @param  string $url YouTube video URL
     * @return array|false
     */
    public function get_video_thumbnails( string $url ) {
        $thumbnails    = [
            'src'    => '',
            'srcset' => [],
        ];
        $response_data = $this->get_single_video_data( $url );
        if ( empty( $response_data ) || empty( $response_data->snippet ) ) {
            return false;
        }
        $snippet = $response_data->snippet;
        if ( empty( $snippet->thumbnails ) ) {
            return false;
        }
        $response_thumbnails = $snippet->thumbnails;

        // Set up srcset attributes for all thumbnail sizes
        foreach ( $response_thumbnails as $data ) {
            if ( empty( $data->url ) || empty( $data->width ) ) {
                continue;
            }
            $thumbnails['srcset'][ (string) $data->width ] = $data->url;
        }

        // Set up the src attribute with the largest thumbnail that makes
        // sense on the site -- no embed will be wider than 640px
        foreach ( array_reverse( $thumbnails['srcset'] ) as $width => $url ) {
            if ( empty( $thumbnails['src'] ) && (int) 640 >= $width ) {
                $thumbnails['src'] = $url;
                break;
            }
        }

        return $thumbnails;
    }

    /**
     * Get data for a single video using cache if available
     *
     * @param  string $url  YouTube video URL
     * @return object|false Object of YouTube snippet data or false on failure
     */
    public function get_single_video_data( string $url ) {
        $id = static::get_video_id_from_url( $url );
        if ( ! $id ) {
            return false;
        }
        $cache_key = 'yt_video_data_' . $id;
        $response  = get_site_option( $cache_key );
        if ( ! $response || empty( $response['success'] ) ) {
            $response = $this->get_api_request( 'videos', [
                'id' => $id,
            ] );
            if ( ! $response || empty( $response['success'] ) ) {
                return false;
            }
            update_site_option( $cache_key, $response );
        }
        return $this->get_data_from_response( $response );
    }

    /**
     * Get the video snippet data from within the API response body
     *
     * @link https://developers.google.com/youtube/v3/docs/videos/list
     * @see Utils::handle_api_request_response()
     *
     * @param  array  $response Response details via Utils::handle_api_request_response()
     * @return object|false     Object of YouTube snippet data or false on failure
     */
    private function get_data_from_response( array $response ) {
        if ( empty( $response['success'] ) || empty( $response['body'] ) || empty( $response['body']->items ) ) {
            return false;
        }

        if ( ! is_array( $response['body']->items ) ) {
            return false;
        }

        return $response['body']->items[0];
    }

    /**
     * Flush the latest channel video ID transient
     */
    public function flush_latest_channel_video() {
        delete_transient( $this->transient_key_latest_channel_video );
    }

    /**
     * Flush the stored data for a single video
     *
     * @param string $id YouTube video URL
     */
    public function flush_single_video_data( string $url ) {
        $id = static::get_video_id_from_url( $url );
        if ( ! $id ) {
            return;
        }
        $cache_key = 'yt_video_data_' . $id;
        delete_site_option( $cache_key );
    }

    /**
     * Handle a YouTube API request
     *
     * @param  string $endpoint Desired endpoint
     * @param  array  $args     Request arguments
     * @return array            Response details
     */
    private function get_api_request( string $endpoint, array $args = [] ) {
        $args        = wp_parse_args( $args, [
            'key'  => YOUTUBE_DATA_API_KEY,
            'part' => 'snippet',
        ] );
        $url         = 'https://www.googleapis.com/youtube/v3/' . $endpoint;
        $request_url = add_query_arg( $args, $url );
        $response    = wp_remote_get( $request_url, [] );
        return Utils::handle_api_request_response( $response );
    }

    /**
     * Get a watch URL from an ID
     *
     * Simply adds the ID as a query arg to the base YouTube watch URL.
     *
     * @param  string $id YouTube video ID
     * @return string     YouTube watch URL
     */
    public static function get_url_from_id( string $id ) {
        if ( empty( $id ) ) {
            return '';
        }
        return add_query_arg( 'v', $id, 'https://www.youtube.com/watch' );
    }

    /**
     * Get a video ID from a URL
     *
     * @param  string $url YouTube URL
     * @return string      YouTube ID
     */
    public static function get_video_id_from_url( $url ) {
        $data = static::get_data_from_url( $url );
        if ( ! empty( $data['id'] ) ) {
            return $data['id'];
        }
        return '';
    }

    /**
     * Get a playlist ID from a URL
     *
     * @param  string $url YouTube URL
     * @return string      YouTube playlist ID
     */
    public static function get_list_id_from_url( $url ) {
        $data = static::get_data_from_url( $url );
        if ( ! empty( $data['list'] ) ) {
            return $data['list'];
        }
        return '';
    }

    /**
     * Get general data from URL
     *
     * @param  string $url YouTube URL
     * @return array
     */
    public static function get_data_from_url( $url ) {
        $query     = [];
        $host      = parse_url( $url, PHP_URL_HOST );
        $query_str = str_replace( '&amp;', '&', Utils::parse_url( $url, PHP_URL_QUERY ) );
        parse_str( $query_str, $query_args );

        if ( 'youtu.be' == $host ) {
            $pattern = Services::get_service_url_pattern( 'youtube' );
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
     * Get an embeddable URL from a standard video URL
     *
     * @param  string $url URL of the YouTube video
     * @return string      Embeddable URL
     */
    public static function get_embeddable_url( string $url ) {
        $embed_id = static::get_video_id_from_url( $url );
        $list_id  = static::get_list_id_from_url( $url );

        if ( empty( $embed_id ) ) {
            return '';
        }

        // ID is always the second part to the path
        $embed_url = 'https://youtube.com/embed/' . $embed_id;
        if ( ! empty( $list_id ) ) {
            $embed_url = add_query_arg( 'list', $list_id, $embed_url );
        }

        return $embed_url;
    }
}
