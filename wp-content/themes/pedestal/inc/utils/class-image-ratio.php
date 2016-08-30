<?php

namespace Pedestal\Utils;

class Image_Ratio {

    private $ratio;

    public function __construct( $ratio_w, $ratio_h ) {
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
            $inverse = true;
            $new_size = round( ( $this->ratio[0] * $img_h ) / $this->ratio[1] );
        }

        return $inverse ? [ $new_size, $img_h ] : [ $img_w, $new_size ];
    }
}
