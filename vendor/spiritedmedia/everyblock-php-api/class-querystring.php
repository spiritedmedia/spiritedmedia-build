<?php

namespace EveryBlock;

/**
 * Build a querystring allowing for duplicate keys
 *
 * @link https://stackoverflow.com/questions/17161114/php-http-build-query-with-two-array-keys-that-are-same/17161284#17161284
 */
class QueryString {

    private $parts = [];

    /**
     * Add multiple parts to querystring via array.
     *
     * This method makes it possible to add duplicate keys to the querystring by
     * setting the value to a subarray of strings.
     *
     * For example: `[ 'schema' => [ 'crime-posts', 'service-requests' ] ]` will
     * output 'schema=crime-posts&schema=service-requests'.
     *
     * @param [type] $multi [description]
     */
    public function add_multiple( $multi ) {
        if ( is_array( $multi ) ) {
            foreach ( $multi as $key => $value ) {
                if ( is_array( $value ) ) {
                    foreach ( $value as $dupe_value ) {
                        if ( is_string( $dupe_value ) ) {
                            $this->add( $key, $dupe_value );
                        }
                    }
                } else {
                    $this->add( $key, $value );
                }
            }
        } else {
            return null;
        }
    }

    public function add( $key, $value ) {
        $this->parts[] = [
            'key'   => $key,
            'value' => $value
        ];
    }

    public function build( $separator = '&', $equals = '=' ) {
        $qs = [];

        foreach( $this->parts as $part ) {
            $qs[] = urlencode( $part['key'] ) . $equals . urlencode( $part['value'] );
        }

        return implode( $separator, $qs );
    }
}
