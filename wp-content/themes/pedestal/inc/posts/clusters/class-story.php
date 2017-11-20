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
     * Check if title bar has appearance
     *
     * @return boolean
     */
    public function has_story_branding() {
        return (bool) $this->get_fm_field( 'story_branding', 'enabled' );
    }

    /**
     * Check if title bar has icon
     *
     * @return boolean
     */
    public function has_story_bar_icon() {
        return (bool) $this->get_icon_id();
    }

    /**
     * Get the Primary Story bar appearance
     *
     * @return array
     */
    public function get_primary_story_branding() {
        $styles = [];
        if ( $this->has_story_branding() ) {
            $styles['background_color']   = $this->get_primary_story_bar_background_color();
            $styles['foreground_color']   = $this->get_primary_story_bar_foreground_color();

            $styles['panel_border_color'] = $this->get_primary_story_bar_background_color();

            $icon = $this->get_icon_id();
            if ( ! empty( $icon ) ) {
                $attachment = Attachment::get( (int) $icon );
                if ( ! empty( $attachment ) ) {
                    $styles['icon'] = $attachment->get_url();
                    $styles['fallback_icon'] = str_replace( '.svg', '.png', $attachment->get_url() );
                }
            }

            return $styles;
        }
        return false;
    }

    /**
     * Get the Primary Story bar background color
     *
     * @return string|bool
     */
    public function get_primary_story_bar_background_color() {
        return $this->get_fm_field( 'story_branding', 'background_color' ) ?: false;
    }

    /**
     * Get the Primary Story bar foreground color
     *
     * @return string|bool
     */
    public function get_primary_story_bar_foreground_color() {
        return $this->get_fm_field( 'story_branding', 'foreground_color' ) ?: false;
    }

    /**
     * Get the ID of the icon
     *
     * @return int|bool ID
     */
    public function get_icon_id() {
        return $this->get_fm_field( 'story_branding', 'icon' ) ?: false;
    }

    /**
     * Get the Twig context for this post
     *
     * @return array Twig context
     */
    public function get_context() {
        $context = [
            'headline' => $this->get_the_headline(),
        ] + parent::get_context();
        return $context;
    }
}
