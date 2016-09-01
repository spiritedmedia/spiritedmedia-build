<?php

namespace Pedestal\Utils;

class Utils {

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Utils;
        }
        return self::$instance;

    }

    /**
     * Get image size data
     */
    public static function get_image_sizes( $size = '' ) {

        global $_wp_additional_image_sizes;

        $sizes = [];
        $get_intermediate_image_sizes = get_intermediate_image_sizes();

        // Create the full array with sizes and crop info
        foreach ( $get_intermediate_image_sizes as $_size ) {

            if ( in_array( $_size, [ 'thumbnail', 'medium', 'large' ] ) ) {

                $sizes[ $_size ]['width'] = (int) get_option( $_size . '_size_w' );
                $sizes[ $_size ]['height'] = (int) get_option( $_size . '_size_h' );
                $sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );

            } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

                $sizes[ $_size ] = [
                    'width' => (int) $_wp_additional_image_sizes[ $_size ]['width'],
                    'height' => (int) $_wp_additional_image_sizes[ $_size ]['height'],
                    'crop' => $_wp_additional_image_sizes[ $_size ]['crop'],
                ];

            }
        }

        // Get only 1 size if found
        if ( $size ) {

            if ( isset( $sizes[ $size ] ) ) {
                return $sizes[ $size ];
            } else {
                return false;
            }
        }

        return $sizes;
    }

    /**
     * Get the current request URI
     *
     * @return string
     */
    public static function get_request_uri() {
        // Accommodate subdirectory installs
        return str_replace( parse_url( home_url(), PHP_URL_PATH ), '', $_SERVER['REQUEST_URI'] );
    }

    /**
     * Determine whether Photon is available
     *
     * @return bool
     */
    public static function is_photon_available() {
        if ( function_exists( 'jetpack_photon_url' ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the Unix timestamp for the New York timezone
     *
     * @param  integer $time Timestamp to offset with timezone. Defaults to current UTC time.
     * @return integer
     */
    public static function get_time( $time = 0 ) {
        if ( empty( $time ) ) {
            $time = time();
        }
        $timezone = new \DateTime( 'now', new \DateTimeZone( 'America/New_York' ) );
        $offset = $timezone->getOffset();
        return $time + $offset;
    }

    /**
     * Remove Pedestal name prefix from string
     *
     * @param  string $type The string to remove the 'pedestal_' prefix from
     * @return string
     */
    public static function remove_name_prefix( $type ) {
        return str_replace( 'pedestal_', '', $type );
    }

    /**
     * Sanitize name without dashes
     *
     * @param  string $string The string to sanitize
     * @return string
     */
    public static function sanitize_name( $string ) {
        if ( is_string( $string ) ) {
            return str_replace( '-', '_', sanitize_title( $string ) );
        } else {
            return $string;
        }
    }

    /**
     * Sort one array of objects by one of the objects' properties
     *
     * @link http://stackoverflow.com/questions/1462503/sort-array-by-object-property-in-php
     * @link http://www.frandieguez.com/blog/2011/02/sort-an-array-of-objects-by-one-of-the-object-property-with-php/
     *
     * @param  array $array     The array of objects
     * @param  string $property The property to sort by
     * @return array
     */
    public static function sort_obj_array_by_prop( $array, $property ) {
        $cur = 1;
        $stack[1]['l'] = 0;
        $stack[1]['r'] = count( $array ) - 1;

        do {
            $l = $stack[ $cur ]['l'];
            $r = $stack[ $cur ]['r'];
            $cur--;

            do {
                $i = $l;
                $j = $r;
                $tmp = $array[ (int) ( ( $l + $r ) / 2 ) ];

                // split the array into parts
                // first: objects with "smaller" property $property
                // second: objects with "bigger" property $property
                do {
                    while ( $array[ $i ]->{ $property } < $tmp->{ $property } ) {
                        $i++;
                    }
                    while ( $tmp->{ $property } < $array[ $j ]->{ $property } ) {
                        $j--;
                    };

                    // Swap elements of two parts if necesary
                    if ( $i <= $j ) {
                        $w = $array[ $i ];
                        $array[ $i ] = $array[ $j ];
                        $array[ $j ] = $w;

                        $i++;
                        $j--;
                    }
                } while ( $i <= $j );

                if ( $i < $r ) {
                    $cur++;
                    $stack[ $cur ]['l'] = $i;
                    $stack[ $cur ]['r'] = $r;
                }

                $r = $j;

            } while ( $l < $r );

        } while ( 0 != $cur );

        return $array;

    } // end sort_obj_array_by_prop()

    /**
     * Get the days of the week ordered from Sunday to Saturday
     *
     * @return array
     */
    public static function get_days_of_week() {
        $days_of_week = [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        ];
        return $days_of_week;
    }

    /**
     * Wrapper for `file_get_contents()` using our HTTP auth credentials
     *
     * @param  string $path Path to the file
     */
    public static function file_get_contents_with_auth( $path ) {
        $context = stream_context_create( [
            'http' => [
                'header' => 'Authorization: Basic ' . base64_encode( PEDESTAL_DEV_AUTH ),
            ],
        ] );
        return file_get_contents( $path, false, $context );
    }

    /**
     * parse_url(), fully-compatible with protocol-less URLs and PHP 5.3
     *
     * @param string $url
     * @param int $component
     * @return mixed
     */
    public static function parse_url( $url, $component = -1 ) {
        $added_protocol = false;
        if ( 0 === strpos( $url, '//' ) ) {
            $url = 'http:' . $url;
            $added_protocol = true;
        }
        $ret = parse_url( $url, $component );
        if ( $added_protocol && $ret ) {
            if ( -1 === $component && isset( $ret['scheme'] ) ) {
                unset( $ret['scheme'] );
            } elseif ( PHP_URL_SCHEME === $component ) {
                $ret = '';
            }
        }
        return $ret;
    }

    /**
     * Remove an item from an array by value
     *
     * @param  mixed $item   The value to remove
     * @param  array $array  The array to edit
     * @return array         The array without the value
     */
    public static function remove_array_item( $item, $array ) {
        $index = array_search( $item, $array );
        if ( false !== $index ) {
            unset( $array[ $index ] );
        }
        return $array;
    }

    /**
     * Format an array as a byline list string
     *
     * @param  array  $items    An array of strings to format
     * @param  array  $args {
     *
     *     @type string $pretext  Text to prepend. Default is 'By'.
     *
     *     @type string $posttext Text to separate last item from previous items.
     *         Default is 'and'.
     *
     *     @type bool   $truncate Should byline string be truncated if 3 or more
     *         items are present? Default is false.
     *
     *     @type string $truncated_str String to substitute when truncated.
     *         Defaults to "{Site Name} Staff".
     *
     * }
     *
     * @return string           String formatted as an English list e.g. 'foo, bar and baz'
     */
    public static function get_byline_list( $items, $args = [] ) {
        $out = '';
        $args = wp_parse_args( $args, [
            'pretext'       => 'By',
            'posttext'      => 'and',
            'truncate'      => false,
            'truncated_str' => sprintf( '%s Staff', get_bloginfo( 'name' ) ),
        ] );
        $pretext = esc_html__( $args['pretext'] . ' %s', 'pedestal' );
        $posttext = esc_html__( ' ' . $args['posttext'] . ' ', 'pedestal' );

        if ( 1 == count( $items ) ) {
            $out = sprintf( $pretext, $items[0] );
        } elseif ( 2 == count( $items ) ) {
            $out = sprintf( $pretext, $items[0] ) . $posttext . $items[1];
        } elseif ( count( $items ) >= 3 ) {
            if ( $args['truncate'] ) {
                $out .= sprintf( $pretext, esc_html__( $args['truncated_str'], 'pedestal' ) );
            } else {
                foreach ( $items as $i => $item ) {
                    if ( 0 == $i ) {
                        $out .= sprintf( $pretext, $item ) . ', ';
                    } elseif ( count( $items ) == ( $i + 1 ) ) {
                        $out .= $posttext . ' ' . $item;
                    } else {
                        $out .= $item . ', ';
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Truncate a string if greater than length
     *
     * @param  string $str String to truncate
     * @param  int    $len Cutoff length
     * @param  string $end Character to end on. Defaults to PHP_EOL
     *
     * @return string      Truncated string
     */
    public static function str_limit( $str, $len, $end = PHP_EOL ) {
        if ( strlen( $str ) > $len ) {
            $parts = wordwrap( $str, $len, $end );
            $parts = explode( $end, $parts );
            $val = array_shift( $parts );
            return $val;
        } else {
            return $str;
        }
    }

    /**
     * Convert an associative array to an HTML data attributes string
     *
     * @param  array  $data_atts HTML data attribute keys and values
     * @param  string $prefix    Optional additional prefix for each of the attributes
     * @return string           HTML data attribute string
     */
    public static function array_to_data_atts_str( $data_atts, $prefix = '' ) {
        if ( ! is_array( $data_atts ) ) {
            return '';
        }

        $atts_str = '';
        foreach ( $data_atts as $key => $value ) {
            if ( ! empty( $prefix ) ) {
                $key = $prefix . '-' . $key;
            }
            $atts_str .= sprintf( 'data-%s="%s" ', $key, $value );
        }
        return $atts_str;
    }
}
