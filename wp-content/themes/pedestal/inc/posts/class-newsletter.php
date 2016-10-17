<?php

namespace Pedestal\Posts;

use Pedestal\Posts\Entities\Embed;

class Newsletter extends Post {

    use \Pedestal\Posts\Emailable;

    protected static $post_type = 'pedestal_newsletter';

    /**
     * Get CSS classes
     *
     * @return array
     */
    public function get_css_classes() {
        $classes = parent::get_css_classes();
        // @TODO should `entity` really be included as a new css class here, or
        //     should newsletter php class extend entity php class?
        $classes = array_merge( [
            'entity'
        ], $classes );
        return $classes;
    }

    /**
     * Get the subtitle for the newsletter
     *
     * @return string
     */
    public function get_newsletter_subtitle() {
        return sprintf( 'Newsletter for %s, %s',
            $this->get_post_date( 'l' ),
            $this->get_post_date( get_option( 'date_format' ) )
        );
    }

    /**
     * Get the Instagram of the Day for this Newsletter
     *
     * Day is based on the publish date for the Newsletter.
     * @return HTML Rendered template
     */
    public function get_instagram_of_the_day() {
        return Embed::get_instagram_of_the_day( $this->get_post_date( 'Y-m-d' ) );
    }
}
