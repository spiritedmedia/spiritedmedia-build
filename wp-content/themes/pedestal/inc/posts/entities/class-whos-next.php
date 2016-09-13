<?php

namespace Pedestal\Posts\Entities;

use \Pedestal\Posts\Clusters\Person;

use \Pedestal\Posts\Attachment;

use \Pedestal\Posts\Clusters\Story;

class Whos_Next extends Entity {

    use \Pedestal\Posts\EditorialContent;

    /**
     * Use automatic placement of ads in Instant Articles?
     *
     * @var string true|false
     */
    private $fias_use_automatic_ad_placement = 'false';

    protected static $post_type = 'pedestal_whosnext';

    /**
     * Get the CSS classes for the Content in string form
     *
     * @return string String of classes
     */
    public function css_classes_content() {
        return implode( '  ', [
            'c-whos-next',
        ] );
    }

    /**
     * Get the type of Who's Next post
     *
     * @return string
     */
    public function get_whosnext_type() {
        return $this->get_fm_field( 'whosnext_details', 'type' );
    }

    /**
     * Get the items set up in this Whos Next
     *
     * @return array
     */
    public function get_items() {
        $items = $this->get_fm_field( 'whosnext_details', 'items' );

        if ( empty( $items ) || 'nomination' === $this->get_whosnext_type() ) {
            return [];
        }

        // Clean up the items array
        foreach ( $items as &$item ) {
            if ( empty( $item['description'] ) || empty( $item['people'] ) || empty( $item['img'] ) ) {
                $item = null;
                continue;
            }

            $_people = $item['people'];
            $item['people'] = [];
            foreach ( $_people as $person_k => $person_v ) {
                $person = Person::get_by_post_id( $person_v['person'] );
                if ( ! $person instanceof Person ) {
                    continue;
                }
                $item['people'][] = $person;
            }

            // Sort the people by last name / first name within this item
            usort( $item['people'], function( $a, $b ) {
                return strcasecmp( $a->get_full_name( true ), $b->get_full_name( true ) );
            } );

            $_img = Attachment::get_by_post_id( $item['img'] );
            $item['img'] = '';
            if ( ! $_img instanceof Attachment ) {
                continue;
            }

            $atts = [
                'caption'                => $_img->get_caption(),
                'credit'                 => $_img->get_credit(),
                'credit_link'            => $_img->get_credit_link(),
                'omit_presentation_mode' => true,
            ];
            $item['img'] = $_img::get_img_caption_html( $_img->get_html( 'large' ), $atts );
        }

        // Remove empty/null items
        $items = array_filter( $items );

        // Sort all of the items by the name of the first listed person in each
        usort( $items, function( $a, $b ) {
            return strcasecmp( $a['people'][0]->get_full_name( true ), $b['people'][0]->get_full_name( true ) );
        } );

        return $items;
    }

    /**
     * Get all of the previous Who's Next posts
     *
     * Includes both posts of this post type and the deprecated Who's Next posts
     * of the article type.
     *
     * @return array Posts
     */
    public function get_archive_items() {
        $whosnext_posts = self::get_posts( [
            'posts_per_page' => 50,
            'post_type' => self::$post_type,
        ] );

        $whosnext_story = self::get_whosnext_story();
        if ( empty( $whosnext_story ) ) {
            return $whosnext_posts;
        }

        $whosnext_story_entities = $whosnext_story->get_entities( [
            'post_type' => 'pedestal_article',
        ] );

        return array_merge( $whosnext_posts, $whosnext_story_entities );
    }

    /**
     * Get the Who's Next Story by slug
     *
     * @return obj|bool Story if successful, else false
     */
    public static function get_whosnext_story() {
        $whosnext_story = self::get_posts( [
            'posts_per_page' => 1,
            'post_type'      => 'pedestal_story',
            'name'           => 'whos-next',
        ] );
        if ( empty( $whosnext_story ) ) {
            return false;
        }
        $whosnext_story = $whosnext_story[0];
        if ( ! $whosnext_story instanceof Story ) {
            return false;
        }
        return $whosnext_story;
    }
}
