<?php

namespace Pedestal\Utils;

class Image_Ratio {

    /**
     * Ratio width and height
     *
     * @var array
     */
    private $ratio;

    /**
     * [constructor]
     *
     * @param integer $ratio_w Ratio width
     * @param integer $ratio_h Ratio height
     */
    public function __construct( int $ratio_w = 16, int $ratio_h = 9 ) {
        $this->ratio = [ $ratio_w, $ratio_h ];
    }

    /**
     * Get the largest possible image size for the given ratio
     *
     * @link http://wordpress.stackexchange.com/questions/212768/add-image-size-where-largest-possible-proportional-size-is-generated
     *
     * @param  int $img_w Original image width
     * @param  int $img_h Original image height
     *
     * @return array New width and height
     */
    public function get_largest_size( $img_w, $img_h ) {
        $inverse = false;

        // Let's try to keep width and calculate new height
        $new_size = round( ( $this->ratio[1] * $img_w ) / $this->ratio[0] );
        if ( $new_size > $img_h ) {
            // If the calculated height is bigger than actual size let's keep
            // current height and calculate new width
            $inverse  = true;
            $new_size = round( ( $this->ratio[0] * $img_h ) / $this->ratio[1] );
        }

        return $inverse ? [ $new_size, $img_h ] : [ $img_w, $new_size ];
    }

    /**
     * Get a size array fitted to a ratio where one of the dimensions is unknown
     *
     * @param array|int $old_dimensions Array of width and height where one of
     *      the dimensions evaluates to false e.g. [ $width, false ]. A single
     *      integer will be evaluated as a width.
     * @return array|false Width and height array
     */
    public function calc_unknown_dimension( $old_dimensions ) {
        if ( is_numeric( $old_dimensions ) ) {
            $old_dimensions = [ $old_dimensions, null ];
        }
        list( $old_width, $old_height ) = $old_dimensions;
        $aspect_ratio                   = $this->ratio[0] / $this->ratio[1];
        if ( $aspect_ratio < 1 ) {
            return false;
        }

        if ( $old_width && is_numeric( $old_width ) ) {
            if ( $old_height ) {
                return false;
            }
            $width  = $old_width;
            $height = $old_width / $aspect_ratio;
        }
        if ( $old_height && is_numeric( $old_height ) ) {
            if ( $old_width ) {
                return false;
            }
            $height = $old_height;
            $width  = $old_height * $aspect_ratio;
        }

        $round_dimensions = function( $dimension ) {
            return round( $dimension, 0, PHP_ROUND_HALF_DOWN );
        };
        return array_map( $round_dimensions, [ $width, $height ] );
    }
}
