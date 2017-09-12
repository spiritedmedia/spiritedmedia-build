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
     * @param array $args
     * @return string|false
     */
    public function get_url( $size = 'full', $args = [] ) {
        $img_data = $this->get_src( $size, $args );
        if ( $img_data ) {
            return $img_data[0];
        }
        return false;
    }

    /**
     * Get the standard WP $src array
     *
     * @param string $size
     * @return array|false
     */
    private function get_src( $size ) {
        if ( is_numeric( $size ) ) {
            $size = [ $size, $size ];
        }
        return wp_get_attachment_image_src( $this->get_id(), $size );
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
        $atts = wp_parse_args( $atts, [
            'attachment'  => 0,
            'caption'     => '',
            'align'       => '',
            'url'         => '',
            'href'        => '',
            'credit'      => '',
            'credit_link' => '',
            'classes'     => '',
        ] );
        $figure = new Figure( 'img', $content, $atts );
        return $figure->get_html();
    }

    /**
     * Get the HTML to represent this attachment
     * Internalized version of wp_get_attachment_image() so we can use our ::get_src() method
     *
     * @return string
     */
    public function get_html( $size = 'full', $args = [] ) {
        $size = $this->maybe_tweak_image_size( $size );
        $image = $this->get_src( $size, $args );
        if ( ! $image ) {
            return '';
        }

        $html = '';
        list( $src, $width, $height ) = $image;
        // Apply default classes to the user-specified classes
        $size_str = is_array( $size ) ? $size[0] . '-' . $size[1] : $size;
        $default_classes = "attachment-$size_str";
        $id = $this->get_id();

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
            'src'    => $src,
            'class'  => $default_classes,
            'alt'    => $alt_text,
        ];
        if ( Pedestal()->is_email() ) {
            $default_attr['border'] = 0;
            if ( is_numeric( $size ) ) {
                $default_attr['width'] = $size;
                $default_attr['height'] = $size;
            }
        } else {
            $default_attr['sizes'] = wp_get_attachment_image_sizes( $id, $size );
            $default_attr['srcset'] = wp_get_attachment_image_srcset( $id, $size );
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
     * Get the attachment's FIAS presentation mode
     *
     * @param  boolean $allow_fullscreen Allow fullscreen mode if attachment is
     *     large enough? Default is false.
     *
     * @return string                    `data-mode` attribute for `<img>`
     */
    public function get_fias_presentation_mode( $allow_fullscreen = false ) {
        $mode = 'non-interactive';
        $data = $this->get_metadata();
        if ( $data && 1024 <= $data['width'] && 1024 <= $data['height'] ) {
            $mode = 'aspect-fit';
            if ( $allow_fullscreen ) {
                $mode = 'fullscreen';
            }
        }
        return sprintf( 'data-mode="%s"', $mode );
    }

    /**
     * Gracefully fall back to the appropriate image size name if the attachment
     * doesn't have the image size being requested. This helps srcset work better while
     * maintaining desired aspect ratios
     *
     * @param  string $size Name of image size
     * @return string       The modified image size
     */
    public function maybe_tweak_image_size( $size = '' ) {
        global $_wp_additional_image_sizes;

        // No $size, so bail
        if ( ! $size ) {
            return '';
        }

        // The requested $size isn't one of our custom sizes so bail
        if ( empty( $_wp_additional_image_sizes[ $size ] ) ) {
            return $size;
        }

        $meta = $this->get_metadata();
        // If we don't have image meta data for a particular size
        // then fill it in with dummy values to prevent a PHP warning
        if ( empty( $meta['sizes'][ $size ] ) ) {
            $meta['sizes'][ $size ] = [
                'width' => 0,
                'height' => 0,
                'crop' => false,
            ];
        }
        $size_meta = $meta['sizes'][ $size ];
        $current_width = $size_meta['width'];
        $size_attributes = $_wp_additional_image_sizes[ $size ];
        $desired_width = $size_attributes['width'];
        $desired_aspect_ratio = round( $size_attributes['width'] / $size_attributes['height'], 4 );
        $maintain_aspect_ratio = $size_attributes['crop'];

        // If we have a match there is nothing left to do...
        if ( $current_width == $desired_width ) {
            return $size;
        }

        // Keep track of widths as we cycle through the custom image sizes
        $biggest_width = 0;
        foreach ( $_wp_additional_image_sizes as $size_name => $size_props ) {
            // If we don't have meta data for the current image size then skip to the next size
            if ( empty( $meta['sizes'][ $size_name ] ) ) {
                continue;
            }
            $meta_props = $meta['sizes'][ $size_name ];

            if ( $maintain_aspect_ratio ) {
                // Can't divide by zero so move on...
                if ( $size_props['height'] <= 0 || $meta_props['height'] <= 0 ) {
                    continue;
                }

                // Check if the current size aspect ratio matches the
                // desired aspect ratio
                $size_aspect_ratio = round( $size_props['width'] / $size_props['height'], 4 );
                if ( $size_aspect_ratio != $desired_aspect_ratio ) {
                    continue;
                }

                // Check if the image meta data aspect ratio matches the
                // desired aspect ratio
                $meta_aspect_ratio = round( $meta_props['width'] / $meta_props['height'], 4 );
                if ( $meta_aspect_ratio != $size_aspect_ratio ) {
                    continue;
                }
            }

            // If the meta data width is bigger than our $biggest_width then
            // we have a new image size to use
            if ( $meta_props['width'] > $biggest_width ) {
                $biggest_width = $meta_props['width'];
                $size = $size_name;
            }
        }

        return $size;
    }
}
