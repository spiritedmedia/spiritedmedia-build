<?php

namespace Pedestal\Posts\Entities\Originals;

use Pedestal\Posts\Entities\Entity;
use Pedestal\Utils\Utils;

/**
 * Original Content
 */
abstract class Original extends Entity {

    /**
     * Is the post Original?
     *
     * @var boolean
     */
    protected static $original = true;

    /**
     * Generated footnotes for the post
     *
     * @var array
     */
    protected $footnotes_generated_notes = [];

    /**
     * Generated footnotes start number
     *
     * @var integer
     */
    protected $footnotes_generated_start = 1;

    /**
     * Setup data attributes
     */
    public function set_data_atts() {
        parent::set_data_atts();
        $atts = parent::get_data_atts();
        $new_atts = [
            'original' => '',
        ];
        $this->data_attributes = array_merge( $atts, $new_atts );
        unset( $this->data_attributes['source-external'] );
    }

    /**
     * Get the name of the icon for this entity's source
     *
     * @return string
     */
    public function get_source_icon_name() {
        return 'bp-logo-head';
    }

    /**
     * Get the filtered footnotes for this post
     *
     * Includes the generated footnotes from the main post content.
     *
     * @return string
     */
    public function get_the_footnotes() {
        $footnotes = apply_filters( 'the_content', $this->get_footnotes() );

        /**
         * Filter the output of the footnotes field
         *
         * @param string $footnotes Footnotes field content
         * @param int    $post_id   Post ID
         */
        $footnotes = apply_filters( 'the_footnotes', $footnotes, $this->get_id() );
        return $footnotes;
    }

    /**
     * Get the footnotes for this post
     *
     * Does not include the generated footnotes from the main post content.
     *
     * @return string
     */
    public function get_footnotes() {
        return $this->get_meta( 'footnotes' );
    }

    /**
     * Get the start offset of the generated footnotes
     *
     * @return int Start offset number. Usually is 1.
     */
    public function get_footnotes_generated_start() {
        return $this->footnotes_generated_start;
    }

    /**
     * Get the generated footnotes array
     *
     * @return array Generated footnotes
     */
    public function get_footnotes_generated_notes() {
        return $this->footnotes_generated_notes;
    }

    /**
     * Set up the generated footnotes
     *
     * @param array $notes Notes
     * @param int   $start Start offset
     */
    public function set_footnotes_generated( array $notes, int $start ) {
        if ( empty( $notes ) ) {
            return $notes;
        }
        $this->footnotes_generated_notes = $notes;
        $this->footnotes_generated_start = $start;
    }

    /**
     * Are ads in Instant Articles placed automatically?
     *
     * @return string true|false
     */
    public function fias_use_automatic_ad_placement() {
        if ( empty( $this->fias_use_automatic_ad_placement ) ) {
            return 'true';
        }
        return $this->fias_use_automatic_ad_placement;
    }

    /**
     * Nasty hack to get a live canonical URL for FIAs
     *
     * Replaces the home URL with the live site URL constant.
     *
     * Allows us to test FIAs on a site with a different URL than the URL
     * registered for the Facebook Page.
     *
     * @return string Live canonical URL
     */
    public function get_fias_canonical_url() {
        $url = $this->get_permalink();
        return str_replace( home_url( '/' ), $site_config['site_live_domain'], $url );
    }

    /**
     * Get the Twig context for this post
     *
     * @param array Existing context to filter
     * @return array Twig context
     */
    public function get_context( $context ) {
        $context = [
            'content_classes' => [ 'js-original-content-body' ],
            'footnotes'       => $this->get_the_footnotes(),
        ] + parent::get_context( $context );
        return $context;
    }
}
