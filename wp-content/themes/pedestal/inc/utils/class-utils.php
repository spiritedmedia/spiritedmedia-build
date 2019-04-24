<?php

namespace Pedestal\Utils;

use Timber\Timber;

/**
 * Utilities
 */
class Utils {

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
        if ( ! isset( self::$instance ) ) {
            self::$instance = new Utils;
        }
        return self::$instance;
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
     * Get a random number for use as a time interval
     *
     * Useful for spacing apart transient expiration times to prevent them from
     * expiring all at once.
     *
     * @link https://codex.wordpress.org/Easier_Expression_of_Time_Constants
     * @param  int $base_interval Time interval upon which calculation is based
     *     -- should be a WordPress Easier Expression of Time Constant
     * @return int                Base interval plus a random fraction of that
     */
    public static function get_fuzzy_expire_time( $base_interval = HOUR_IN_SECONDS ) {
        $fuzz = rand( 0, 10 ) * 0.1;
        return ( 1 + $fuzz ) * $base_interval;
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
        $cur           = 1;
        $stack[1]['l'] = 0;
        $stack[1]['r'] = count( $array ) - 1;

        do {
            $l = $stack[ $cur ]['l'];
            $r = $stack[ $cur ]['r'];
            $cur--;

            do {
                $i   = $l;
                $j   = $r;
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
                        $w           = $array[ $i ];
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
     * parse_url(), fully-compatible with protocol-less URLs and PHP 5.3
     *
     * @param string $url
     * @param int $component
     * @return mixed
     */
    public static function parse_url( $url, $component = -1 ) {
        $added_protocol = false;
        if ( 0 === strpos( $url, '//' ) ) {
            $url            = 'http:' . $url;
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
     * Get a domain name from URL, including non-www subdomain
     *
     * @param string $url
     * @return string
     */
    public static function get_domain_from_url( $url ) {
        if ( ! $url || ! is_string( $url ) ) {
            return '';
        }

        // Make sure the protocol is specified, which is required for parse_url()
        $url = esc_url_raw( $url );

        $url_domain = parse_url( $url, PHP_URL_HOST );
        return str_replace( 'www.', '', $url_domain );
    }

    /**
     * Remove an item from an array by value
     *
     * Returns the original array if the value is not present.
     *
     * @param  mixed $value   The value to remove
     * @param  array $array  The array to edit
     * @return array         The array without the value
     */
    public static function remove_array_item( $value, $array ) {
        $index = array_search( $value, $array );
        if ( false !== $index ) {
            unset( $array[ $index ] );
        }
        return $array;
    }

    /**
     * Flatten an array
     *
     * @param  array  $array Array to flatten
     * @return array         Flattened array
     */
    public static function array_flatten( array $array ) {
        $return = [];
        array_walk_recursive( $array, function( $a ) use ( &$return ) {
            $return[] = $a;
        } );
        return $return;
    }

    /**
     * Format an array as a byline list string
     *
     * @param  array  $items    An array of strings to format
     * @param  array  $args {
     *
     *     @property string $pretext  Text to prepend. Default is 'By'.
     *
     *     @property string $posttext Text to separate last item from previous items.
     *         Default is 'and'.
     *
     *     @property bool   $truncate Should byline string be truncated if 3 or more
     *         items are present? Default is false.
     *
     *     @property string $truncated_str String to substitute when truncated.
     *         Defaults to "{Site Name} Staff".
     *
     * }
     *
     * @return string           String formatted as an English list e.g. 'foo, bar and baz'
     */
    public static function get_byline_list( $items, $args = [] ) {
        $out      = '';
        $args     = wp_parse_args( $args, [
            'pretext'       => '',
            'posttext'      => 'and',
            'truncate'      => false,
            'truncated_str' => get_bloginfo( 'name' ),
        ] );
        $pretext  = esc_html__( $args['pretext'] . ' %s', 'pedestal' );
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
            $val   = array_shift( $parts );
            return $val;
        } else {
            return $str;
        }
    }

    /**
     * Truncate a string to a sentence closest to the specified length
     *
     * @link http://stackoverflow.com/a/10254414
     *
     * @param  string  $str String to truncate
     * @param  int     $len Length to aim for
     * @return string       Truncated string
     */
    public static function str_limit_sentence( string $str, int $len = 150 ) {
        $str_len = strlen( $str );
        if ( $str_len > $len ) {
            $trim_len = strrpos( $str, '. ', $len - $str_len ) + 1;
            $str      = substr( $str, 0, $trim_len );
        }
        return $str;
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

    /**
     * Convert an associative array to an HTML or shortcode attributes string
     *
     * Use Utils::array_to_data_atts_str() for working with data attributes.
     *
     * @param array $atts HTML attribute keys and values
     * @param bool $skip_empty Skip empty values?
     * @return string      HTML attribute string
     */
    public static function array_to_atts_str( $atts, $skip_empty = false ) {
        if ( ! is_array( $atts ) ) {
            return '';
        }
        $atts_str = '';
        foreach ( $atts as $key => $value ) {
            if ( $skip_empty && empty( $value ) ) {
                continue;
            }
            if ( 'class' == $key && is_array( $value ) ) {
                $value = implode( ' ', $value );
            }
            $atts_str .= sprintf( '%s="%s" ', sanitize_key( $key ), esc_attr( $value ) );
        }
        return $atts_str;
    }

    /**
     * `array_merge_recursive()` does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as `array_merge()`` does. I.E., with `array_merge_recursive()``,
     * this happens (documented behavior):
     *
     * ```
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     * ```
     *
     * `Utils::array_merge_recursive()` does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * ```
     * Utils::array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     * ```
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @link http://php.net/manual/en/function.array-merge-recursive.php#92195
     *
     * @param array &$defaults_array
     * @param array &$new_array
     *
     * @return array
     */
    public static function array_merge_recursive( array &$defaults_array, array &$new_array ) {
        $merged = $defaults_array;

        foreach ( $new_array as $key => &$value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                if ( empty( $value ) ) {
                    continue;
                }
                $merged[ $key ] = self::array_merge_recursive( $merged[ $key ], $value );
            } else {
                $merged[ $key ] = $value;
            }
        }

        return $merged;
    }

    /**
     * Standardizes the response from remote API requests
     *
     * @param  array|\WP_Error $response  Response from wp_remote_*
     * @param  string $expected_format   Expected format of the response body (xml, json, or serialize)
     * @return array                     Details from the response
     */
    public static function handle_api_request_response( $response = [], string $expected_format = 'json' ) {
        if ( is_wp_error( $response ) ) {
            return [
                'code'    => 0,
                'body'    => $response->get_error_message(),
                'success' => false,
            ];
        }

        if (
            ! isset( $response['response'] )
            || ! isset( $response['response']['code'] )
            || ! isset( $response['body'] )
        ) {
            return [
                'code'    => 0,
                'body'    => 'No response code or response body set',
                'success' => false,
            ];
        }
        $response_code = $response['response']['code'];
        $response_body = $response['body'];
        $success       = false;
        if ( 200 == $response_code ) {
            $success = true;
        }

        switch ( strtolower( $expected_format ) ) {
            case 'json':
                $response_body = json_decode( $response_body );
                break;
            case 'serialize':
                $response_body = unserialize( $response_body );
                break;
        }
        if ( ! is_object( $response_body ) ) {
            return [
                'code'    => 0,
                'body'    => 'Response body is not an object',
                'success' => false,
            ];
        }
        $result = [
            'code'    => $response_code,
            'body'    => $response_body,
            'success' => $success,
        ];
        return $result;
    }

    /**
     * Does the opposite of WordPress' wpautop() function
     *
     * @see https://wordpress.stackexchange.com/a/10972/2744
     * @param  string $string HTML string to be modified
     * @return string         Modified string
     */
    public static function reverse_wpautop( $string = '' ) {
        $string = str_replace( "\n", '', $string );
        $string = str_replace( '<p>', '', $string );
        $string = str_replace( [ '<br />', '<br>', '<br/>' ], "\n", $string );
        $string = str_replace( '</p>', "\n\n", $string );
        return $string;
    }

    /**
     * Sanitize string for JSON
     *
     * @param  string $val The string to sanitize
     * @return string
     */
    public static function sanitize_string_for_json( $val ) {
        if ( ! is_string( $val ) ) {
            return $val;
        }

        // Remove LF line breaks (*nix)
        $val = str_replace( "\n", '', $val );
        // Remove CR line breaks (Windows)
        $val = str_replace( "\r", '', $val );
        // Remove HTML and PHP tags
        $val = strip_tags( $val );
        // Remove leading/trailing whitespace
        $val = trim( $val );

        return $val;
    }

    /**
     * Load a Twig template's source without rendering it
     *
     * Variables will not be expanded, control statements will be visible, etc.
     *
     * Useful for sharing templates between PHP and JavaScript.
     *
     * @param string $template_path
     * @return string Unrendered Twig template source
     */
    public static function load_template_source( $template_path ) {
        $process = '{{ source("' . $template_path . '") }}';
        return Timber::compile_string( $process );
    }

    /**
     * Return the input parameter
     *
     * Useful as a non-sanitizing sanitization function.
     *
     * @param mixed $input
     * @return mixed Just `$input`
     */
    public static function return_same( $input ) {
        return $input;
    }

    /**
     * Add an ordinal suffix to a given integer
     *
     * @link https://stackoverflow.com/a/3110033/1801260
     * @param int|string $number Integer or numeric string
     * @return string String with ordinal suffix or the original value if not numeric
     */
    public static function add_ordinal_suffix( $number ) {
        if ( ! is_numeric( $number ) ) {
            return $number;
        }
        $ends = [ 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' ];
        if ( ( ( $number % 100 ) >= 11 ) && ( ( $number % 100 ) <= 13 ) ) {
            return $number . 'th';
        }
        return $number . $ends[ $number % 10 ];
    }

    /**
     * Get the regular expression for a given shortcode attribute name
     *
     * @link https://regex101.com/r/8ERUqF/4
     * @param string $attribute_name Shortcode attribute name
     * @return string Regular expression
     */
    public static function get_shortcode_attribute_regex( $attribute_name ) {
        return '/' . $attribute_name . '="(.*?)(("\s[a-zA-Z]*?=")|("\s?\/?\]))/m';
    }

    /**
     * Decode all HTML entities in a string, including numeric entities
     *
     * @link http://php.net/manual/en/function.html-entity-decode.php#104617
     *
     * @param string $string
     * @return string
     */
    public static function decode_html_entities( string $string ) {
        $string = html_entity_decode( $string );
        return preg_replace_callback( '/(&#[0-9]+;)/', function( $match ) {
            return mb_convert_encoding( $match[1], 'UTF-8', 'HTML-ENTITIES' );
        }, $string );
    }

    /**
     * Convert an array to a JavaScript object for output directly in a template
     *
     * Performs the same function as `wp_localize_script()` but doesn't rely on
     * registered script handles. This can be useful if we need to make data
     * available to an inline script.
     *
     * @see `\WP_Scripts::localize()`
     *
     * @param array $array Array of data to process
     * @param string $object_name [''] Optional name of JS object variable to output
     * @return string Sanitized JSON string or JS variable declaration
     */
    public static function encode_for_js( array $array, string $object_name = '' ) {
        foreach ( $array as $key => $value ) {
            if ( ! is_scalar( $value ) ) {
                continue;
            }
            $array[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
        }
        $data = wp_json_encode( $array );
        if ( $object_name ) {
            return "var {$object_name} = {$data};";
        }
        return $data;
    }
}
