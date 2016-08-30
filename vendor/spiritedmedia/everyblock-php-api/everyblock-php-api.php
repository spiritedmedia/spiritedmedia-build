<?php
/**
 * EveryBlock PHP API
 *
 * API Documentation: http://www.everyblock.com/developers/content-api/
 * Class Documentation: https://github.com/spiritedmedia/everyblock-php-api/
 *
 * @author Spirited Media <product@billypenn.com>
 * @copyright (c) 2015 Spirited Media Inc.
 * @version 0.1.0
 * @license http://spirited.mit-license.org/ MIT License
 */

namespace EveryBlock;

require_once dirname( __FILE__ ) . '/class-querystring.php';

class EveryBlock {

    private static $api_url = 'https://api.everyblock.com/content/';

    private $api_key;

    private $metro;

    /**
     * Create a new instance
     *
     * @param string $api_key API key
     * @param string $metro   The short_name of the this metro
     */
    public function __construct( $api_key, $metro ) {
        $this->api_key = $api_key;
        $this->metro = $metro;
    }

    /**
     * Return the news items for the specified location
     * @param string  $location A location's slug. This can be found via locations().
     * @param boolean $events   Return events? Default is false.
     * @param array   $params {
     *     Optional. Parameters for request.
     *
     *     @type mixed  $schema     Filter by a schema's slug, as a string. To
     *                              filter by multiple, use array containing
     *                              unmapped values.
     *     @type string $date       Sort returned news items by date. Value may
     *                              be either 'ascending' or 'descending'.
     *     @type mixed  $source     Filter returned news items by source number
     *                              as an integer. News item results are
     *                              identified by a source number which
     *                              indicates their origin. To filter by
     *                              multiple, use array containing unmapped
     *                              values.
     *     @type mixed  $attribute  Filter the returned news item's associated
     *                              attributes. Specify the desired attribute as
     *                              value. To filter by multiple, use array
     *                              containing unmapped values.
     *     @type string $start_date (Events only) Format: yyyy-mm-dd; Limit
     *                              results to events starting on or after the
     *                              given date.
     *     @type string $end_date   (Events only) Format: yyyy-mm-dd; Limit
     *                              results to events starting on or before the given date.
     * }
     * @return object
     */
    public function timeline( $location, $events = false, $params = null ) {
        if ( $events ) {
            $events = '/events';
        }
        if ( $params ) {
            $qs = new \EveryBlock\QueryString();
            $params = $qs->add_multiple( $params );
            $params = $qs->build();
        }
        $endpoint = 'locations/' . $location . '/timeline' . $events;
        return $this->request( $this->metro, $endpoint, $params );
    }

    /**
     * Return the locations for the specified location type
     *
     * @param string $location_type {
     *     A location type.
     *
     *     @type string $neighborhoods
     *     @type string $wards
     *     @type string $zipcodes
     *     @type string $custom-locations
     * }
     * @return object
     */
    public function locations( $location_type ) {
        return $this->request( $this->metro, $location_type );
    }

    /**
     * Return data for the Popular News Item endpoint.
     *
     * Returns the popular news items for a specific metro. It is the API
     * equivalent to Popular in Chicago. It is limited to 5 days worth of
     * content.
     *
     * @param boolean $events Return events? Default is false.
     * @param array   $params {
     *     Optional. Parameters for request.
     *
     *     @type mixed  $schema     Filter by a schema's slug, as a string. To
     *                              filter by multiple, use array containing
     *                              unmapped values.
     *     @type string $date       Sort returned news items by date. Value may
     *                              be either 'ascending' or 'descending'.
     *     @type mixed  $source     Filter returned news items by source number
     *                              as integer. News item results are identified
     *                              by a source number which indicates their
     *                              origin. To filter by multiple, use array
     *                              containing unmapped values.
     *     @type mixed  $attribute  Filter the returned news item's associated
     *                              attributes by a string value. To filter by
     *                              multiple, use array containing unmapped
     *                              values.
     *     @type string $start_date (Events only) Format: yyyy-mm-dd; Limit
     *                              results to events starting on or after the
     *                              given date.
     *     @type string $end_date   (Events only) Format: yyyy-mm-dd; Limit
     *                              results to events starting on or before the given date.
     * }
     * @return object
     */
    public function topnews( $events = false, $params = null ) {
        if ( $events ) {
            $events = '/events';
        }
        if ( $params ) {
            $qs = new \EveryBlock\QueryString();
            $params = $qs->add_multiple( $params );
            $params = $qs->build();
        }
        $endpoint = 'topnews' . $events;
        return $this->request( $this->metro, $endpoint, $params );
    }

    /**
     * Returns the schemas for a specific metro from the Schema endpoint.
     *
     * @link http://www.everyblock.com/developers/content-api/#metro-endpoint
     * @return array
     */
    public function schemas() {
        return $this->request( $this->metro, 'schemas' );
    }

    /**
     * Return data from the Metro endpoint.
     *
     * Returns a specific EveryBlock metro, with some useful data and related
     * data links within the API.
     *
     * @link http://www.everyblock.com/developers/content-api/#metro-endpoint
     * @return object
     */
    public function metro() {
        return $this->request( $this->metro );
    }

    /**
     * Return data from the Content endpoint.
     *
     * Returns a list of all current EveryBlock metros, with some useful data
     * and related data links within the API. The content endpoint is merely a
     * list of all current EveryBlock metros. See the Metro Endpoint for output
     * details.
     *
     * @link http://www.everyblock.com/developers/content-api/#content-endpoint
     * @return array
     */
    public function content() {
        return $this->request();
    }

    /**
     * Request data from EveryBlock
     *
     * @param string $metro    The metro/city to get data for. Empty $metro will
     *                         return the `/content/` endpoint. Default is null.
     * @param string $endpoint The endpoint URL path. Empty endpoint will fall
     *                         back to `/content/` endpoint. If $metro is not
     *                         set, then $endpoint will have no effect because
     *                         it depends on $metro. Default is null.
     * @param string $params   Any request parameters to append to URL. Handled
     *                         by individual methods. Default is null.
     * @return array API response
     */
    private function request( $metro = null, $endpoint = null, $params = null ) {
        if ( isset( $metro, $endpoint ) ) {
            $endpoint = (string) $metro . '/' . (string) $endpoint . '/';
        } else if ( isset( $metro ) && empty( $endpoint ) ) {
            $endpoint = (string) $metro . '/';
        } else {
            // Note that $endpoint will be set to null even if $endpoint was
            // defined as an argument without setting $metro - $endpoint cannot
            // function without $metro
            $endpoint = null;
        }

        if ( isset( $params ) && is_string( $params ) ) {
            $params = '?' . $params;
        } else {
            $params = null;
        }

        $call = self::$api_url . $endpoint . $params;

        $header = [
            'Accept: application/json',
            'Authorization: Token ' . $this->api_key,
        ];

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $call );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 20 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 90 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        $data = curl_exec( $ch );
        if ( false === $data ) {
            // @todo handle error
            // return new WP_Error( 'Error: call() - cURL error: ' . curl_error( $ch ) );
        }
        curl_close( $ch );

        return json_decode( $data );
    }
}
