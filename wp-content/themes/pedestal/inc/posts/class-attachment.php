<?php

namespace Pedestal\Posts;

use function Pedestal\Pedestal;
use Pedestal\Utils\Utils;
use Pedestal\Objects\Figure;

/**
 * Base class to represent a WordPress Attachment
 */
class Attachment extends Post {

    protected static $post_type = 'attachment';

    /**
     * Get the URL to the attachment for a given size
     *
     * @param string $size
     * @return string|false
     */
    public function get_url( $size = 'full' ) {
        $img_data = $this->get_src( $size );
        if ( $img_data ) {
            return $img_data[0];
        }
        return false;
    }

    /**
     * Get a standard WP $src array
     *
     * If a full-sized image is requested, then get the URL to the original
     * image without any additional query args. Tachyon adds an additional `fit`
     * query arg.
     *
     * If a number or array of width and height is passed as `$size`, use a
     * `resize` URL argument. Otherwise Tachyon's `fit` arg will provide an
     * uncropped image.
     *
     * @param string|array|int $size ['full'] Image size name, an array of width
     *      and height (in that order), or a single dimension to set both width
     *      and height (resulting in a square image)
     * @return array|false
     */
    private function get_src( $size = 'full' ) {
        if ( is_numeric( $size ) ) {
            $size = [ $size, $size ];
        }
        $src = wp_get_attachment_image_src( $this->get_id(), $size );

        if ( is_array( $size ) || 'full' === $size ) {
            // Get the original URL without query args
            $url_parts = explode( '?', $src[0] );
            $src_url   = $url_parts[0];

            if ( is_array( $size ) ) {
                list( $new_width, $new_height ) = $size;
                $src_url                        = add_query_arg( 'resize', "{$new_width},{$new_height}", $src_url );
                $src[1]                         = $new_width;
                $src[2]                         = $new_height;
            }

            $src[0] = $src_url;
        }
        return $src;
    }

    /**
     * Get an image and caption in Figure format
     *
     * @param array $atts {
     *
     *     @type int    $id           The ID to use for rel between fig/caption.
     *     @type string $caption      Caption to display.
     *     @type string $align        Alignment class.
     *     @type string $content      The figure content I.E. the image.
     *     @type string $credit       The image credit.
     *     @type string $credit_link  The credit link.
     *     @type string $classes      Extra space-separated classes to append to
     *                                the figure element.
     *
     * }
     *
     * @return string|HTML
     */
    public static function get_img_caption_html( $content, $atts = [] ) {
        $atts   = wp_parse_args(
            $atts, [
                'attachment'  => 0,
                'caption'     => '',
                'align'       => '',
                'url'         => '',
                'href'        => '',
                'credit'      => '',
                'credit_link' => '',
                'classes'     => '',
            ]
        );
        $figure = new Figure( 'img', $content, $atts );
        return $figure->get_html();
    }

    /**
     * Get the HTML to represent this attachment
     *
     * Internalized version of wp_get_attachment_image() so we can use our
     * `Attachment::get_src()` method
     *
     * @return string
     */
    public function get_html( $size = 'full', $args = [] ) {
        $image = $this->get_src( $size );
        if ( ! $image ) {
            return '';
        }

        $html                         = '';
        list( $src, $width, $height ) = $image;
        // Apply default classes to the user-specified classes
        $size_str        = is_array( $size ) ? $size[0] . '-' . $size[1] : $size;
        $default_classes = "attachment-$size_str";
        $id              = $this->get_id();

        // Set alt text with fallbacks
        $alt_text = '';
        if ( ! empty( $this->get_alt_text() ) ) {
            $alt_text = $this->get_alt_text(); // Use the alt text
        } elseif ( ! empty( $this->get_caption() ) ) {
            $alt_text = $this->get_caption(); // If not, Use the Caption
        } elseif ( ! empty( $this->get_the_title() ) ) {
            $alt_text = $this->get_the_title(); // Finally, use the title
        }
        $alt_text = trim( strip_tags( $alt_text ) );

        $default_attr = [
            'src'   => $src,
            'class' => $default_classes,
            'alt'   => $alt_text,
        ];
        if ( Pedestal()->is_email() ) {
            $default_attr['border'] = 0;
            if ( is_numeric( $size ) ) {
                $default_attr['width']  = $size;
                $default_attr['height'] = $size;
            }
        } else {
            $default_attr['sizes']  = wp_get_attachment_image_sizes( $id, $size );
            $default_attr['srcset'] = wp_get_attachment_image_srcset( $id, $size );
        }

        if ( empty( $args['sizes'] ) ) {
            unset( $args['sizes'] );
        } else {
            $sizes = $args['sizes'];
            if ( is_array( $sizes ) ) {
                $sizes = implode( ', ', $sizes );
            }

            if ( is_string( $sizes ) ) {
                $args['sizes'] = $sizes;
            } else {
                unset( $args['sizes'] );
            }
        }

        if ( ! function_exists( 'tachyon_url' ) || empty( $args['srcset'] ) ) {
            unset( $args['srcset'] );
        } else {
            if ( is_array( $args['srcset'] ) ) {
                // Accept either a flat array of widths, or a multidimensional
                // array containing a ratio float and an array of widths
                $ratio  = $args['srcset']['ratio'] ?? null;
                $widths = $args['srcset']['widths'] ?? null;
                if ( ! $ratio && ! $widths ) {
                    $widths = $args['srcset'];
                }
                if ( is_numeric( $widths ) ) {
                    $widths = [ $widths ];
                }
                $args['srcset'] = $this->get_srcset_string( $widths, $ratio );
            } else {
                unset( $args['srcset'] );
            }
        }

        $attrs = wp_parse_args( $args, $default_attr );

        return self::get_img_html( $attrs );
    }

    /**
     * Get the <img> HTML based on array of attributes
     *
     * @param  array $attrs Array of attributes
     *
     * @return string        HTML <img> element
     */
    public static function get_img_html( $attrs ) {
        return '<img ' . Utils::array_to_atts_str( $attrs ) . ' />';
    }

    /**
     * Get the attachment's alt text
     *
     * @return string
     */
    public function get_alt_text() {
        return $this->get_meta( '_wp_attachment_image_alt' );
    }

    /**
     * Get the caption for the attachment
     *
     * @return string
     */
    public function get_caption() {
        return $this->get_excerpt();
    }

    /**
     * Get the description for the attachment
     *
     * @return string
     */
    public function get_description() {
        return $this->get_content();
    }

    /**
     * Get the credit link for the attachment
     *
     * @return string
     */
    public function get_credit_link() {
        return $this->get_meta( 'credit_link' );
    }

    /**
     * Get the credit for the attachment
     *
     * @return string
     */
    public function get_credit() {
        return $this->get_meta( 'credit' );
    }

    /**
     * Get a field from the attachment metadata
     *
     * @param string  $field  Name of the field
     * @return string
     */
    protected function get_metadata_field( $field ) {
        $metadata = $this->get_metadata();
        if ( ! empty( $metadata['image_meta'][ $field ] ) ) {
            return $metadata['image_meta'][ $field ];
        }
        return '';
    }

    /**
     * Get attachment metadata
     *
     * @return array
     */
    public function get_metadata() {
        return wp_get_attachment_metadata( $this->get_id() );
    }

    /**
     * Get a `srcset` attribute string from an array of widths
     *
     * @param array $widths Flat array of numeric widths
     * @param integer $aspect_ratio [0] Image aspect ratio specified as a float
     * @return string
     */
    protected function get_srcset_string( $widths, $aspect_ratio = 0 ) {
        if ( ! $widths ) {
            return '';
        }

        list( $orig_url, $orig_width, $orig_height ) = $this->get_src();
        $aspect_ratio                                = $aspect_ratio ?: $orig_width / $orig_height;

        $srcset = [];
        foreach ( $widths as $key => $width ) :
            if ( ! is_numeric( $width ) || ! is_numeric( $key ) ) {
                continue;
            }

            for ( $multiplier = 1; $multiplier < 4; $multiplier++ ) {
                $current_width  = floor( $width * $multiplier );
                $current_height = floor( $current_width / $aspect_ratio );

                if ( $current_width <= $orig_width && $current_height <= $orig_height ) {
                    // Prevent duplicates in a performant way by assigning as key
                    $key            = add_query_arg( 'resize', "{$current_width},{$current_height}", $orig_url ) . " {$current_width}w";
                    $srcset[ $key ] = '';
                }
            }
        endforeach;

        return implode( ', ', array_keys( $srcset ) );
    }
}
