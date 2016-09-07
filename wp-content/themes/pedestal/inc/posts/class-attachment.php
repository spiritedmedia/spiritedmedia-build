<?php

namespace Pedestal\Posts;

use \Pedestal\Utils\Utils;

use \Pedestal\Objects\Figure;

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
        } else {
            return false;
        }
    }

    /**
     * Get the standard WP $src array, but potentially resized
     *
     * @param string $size
     * @param array $args
     * @return array|false
     */
    private function get_src( $size, $args ) {
        $src = wp_get_attachment_image_src( $this->get_id(), $size );
        return $this->maybe_resize_image_src( $src, $args );
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
            'linkto'      => '',
            'href'        => '',
            'credit'      => '',
            'credit_link' => '',
            'classes'     => '',
        ] );

        // @TODO
        // @codingStandardsIgnoreStart
        extract( $atts );
        // @codingStandardsIgnoreEnd

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

        $image = $this->get_src( $size, $args );
        if ( ! $image ) {
            return '';
        }

        $html = '';
        list( $src, $width, $height ) = $image;

        // Apply default classes to the user-specified classes
        $size_str = is_array( $size ) ? $size[0] . '-' . $size[1] : $size;
        $default_classes = "attachment-$size_str";

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
        $attrs = array_map( 'esc_attr', $attrs );
        $html = '<img';
        foreach ( $attrs as $name => $value ) {
            $html .= " $name=" . '"' . $value . '"';
        }
        $html .= ' />';
        return $html;
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
        return $this->get_metadata_field( 'credit_link' );
    }

    /**
     * Get the credit for the attachment
     *
     * @return string
     */
    public function get_credit() {
        return $this->get_metadata_field( 'credit' );
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
        } else {
            return '';
        }
    }

    /**
     * Get attachment metadata
     *
     * @return array
     */
    protected function get_metadata() {
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
}
