<?php

namespace Pedestal\Posts\Clusters;

use Pedestal\Posts\Post;

use Pedestal\Posts\Attachment;

class Story extends Cluster {

    protected static $post_type = 'pedestal_story';

    protected $email_type = 'story updates';

    /**
     * Get the filtered headline for the Story
     *
     * @return string
     */
    public function get_the_headline() {
        return apply_filters( 'the_title', $this->get_meta( 'headline' ) );
    }

    /**
     * Get the (optional) headline for the Story
     *
     * @return string
     */
    public function get_headline() {
        return $this->get_meta( 'headline' );
    }

    /**
     * Get the SEO title for the post
     *
     * @return string
     */
    public function get_seo_title() {
        $title = $this->get_fm_field( 'pedestal_distribution', 'seo', 'title' );
        if ( $title ) {
            return $title;
        }

        $headline = $this->get_headline();
        if ( $headline ) {
            return $headline;
        }

        return $this->get_default_seo_title();
    }

    /**
     * Get the Twig context for this post
     *
     * @param array Existing context to filter
     * @return array Twig context
     */
    public function get_context( $context ) {
        $context = [
            'headline' => $this->get_the_headline(),
        ] + parent::get_context( $context );
        return $context;
    }
}
