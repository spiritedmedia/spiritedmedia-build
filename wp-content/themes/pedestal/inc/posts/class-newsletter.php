<?php

namespace Pedestal\Posts;

use Pedestal\Posts\Entities\Embed;

use Pedestal\Registrations\Post_Types\{
    Types,
    General_Types
};

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
            'entity',
        ], $classes );
        return $classes;
    }

    /**
     * Get the subtitle for the newsletter
     *
     * @return string
     */
    public function get_newsletter_subtitle() {
        return 'Newsletter for ' . $this->get_newsletter_date_string();
    }

    /**
     * Get the newsletter's date string
     *
     * @return string
     */
    public function get_newsletter_date_string() {
        return $this->get_post_date( 'l' ) . ', ' . $this->get_post_date( get_option( 'date_format' ) );
    }

    /**
     * Get the Instagram of the Day for this Newsletter
     *
     * Day is based on the publish date for the Newsletter.
     * @return HTML Rendered template
     */
    public function get_instagram_of_the_day() {
        return Embed::get_instagram_of_the_day( [
            'date'    => $this->get_post_date( 'Y-m-d' ),
            'context' => 'newsletter',
        ] );
    }

    /**
     * Get the newsletter items
     *
     * @return array|false Array of item data on success, false if failure
     */
    public function get_items() {
        $items = $this->get_meta( 'newsletter_items' );
        $types = General_Types::get_newsletter_item_types();

        if ( empty( $items ) || ! is_array( $items ) || empty( $types ) || ! is_array( $types ) ) {
            return false;
        }

        foreach ( $items as $key => &$item ) {
            if ( empty( $item['type'] ) ) {
                unset( $items[ $key ] );
                continue;
            }
            $type = $item['type'];

            if ( 'post' === $type ) :

                if ( empty( $item['post'] ) ) {
                    unset( $items[ $key ] );
                    continue;
                }

                $post = static::get_by_post_id( $item['post'] );
                if ( ! Types::is_post( $post ) ) {
                    unset( $items[ $key ] );
                    continue;
                }
                $item['post'] = $post;

                if ( 'event' !== $post->get_type() && empty( $item['description'] ) ) {
                    unset( $items[ $key ] );
                    continue;
                }

                if ( empty( $item['post_title'] ) ) {
                    $item['post_title'] = $post->get_the_title();
                }

                if ( empty( $item['url'] ) ) {
                    $item['url'] = $post->get_the_permalink();
                }

                $item['title'] = $item['post_title'];
                unset( $item['post_title'] );

            elseif ( strpos( $type, 'heading_' ) !== false && isset( $types[ $type ] ) ) :

                $item['type'] = 'heading';
                $item['heading_variant'] = str_replace( 'heading_', '', $type );
                $item['title'] = str_replace( esc_html( 'Heading: ', 'pedestal' ), '', $types[ $type ] );

            elseif ( 'heading' === $type ) :

                if ( empty( $item['heading_title'] ) ) {
                    unset( $items[ $key ] );
                    continue;
                }
                $item['title'] = $item['heading_title'];
                unset( $item['heading_title'] );

            endif;
        }// End foreach().

        return $items;
    }
}
