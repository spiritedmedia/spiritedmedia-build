<?php

namespace Pedestal\Widgets;

use Timber\Timber;

use Pedestal\Posts\Post;
use Pedestal\Posts\Clusters\Story;

class In_This_Story_Widget extends \WP_Widget {

    public function __construct() {

        parent::__construct(
            'pedestal_in_this_story',
            esc_html__( 'In This Story', 'pedestal' ),
            [
                'description' => esc_html__( 'Display relative links to story entities on a story page, or absolute links if an entity is a part of a story.', 'pedestal' ),
            ]
        );

    }

    public function widget( $args, $instance ) {

        if ( ! is_singular() ) {
            return;
        }

        $context = Timber::get_context();
        $permalink_filter = false;
        if ( is_singular( 'pedestal_story' ) ) {
            $obj = Story::get_by_post_id( get_queried_object_id() );
            if ( ! $obj ) {
                return;
            }
            $context['current_item'] = $obj;
            $context['items'] = $obj->get_entities( [ 'posts_per_page' => 30 ] );
            $permalink_filter = function( $post_link, $post ) {
                return '#' . $post->post_name;
            };
        } else {
            $obj = Post::get_by_post_id( get_queried_object_id() );
            if ( ! $obj || ! is_subclass_of( $obj, 'Pedestal\Posts\Entities\Entity' ) || ! $obj->has_story() ) {
                return;
            }
            $context['current_item'] = $obj;
            $context['items'] = $obj->get_story()->get_entities( [ 'posts_per_page' => 30 ] );
        }

        $instance = [ 'title' => esc_html__( 'In This Story', 'pedestal' ) ];

        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }

        if ( $permalink_filter ) {
            add_filter( 'post_type_link', $permalink_filter, 10, 2 );
        }

        Timber::render( 'widgets/item-list.twig', $context );

        if ( $permalink_filter ) {
            remove_filter( 'post_type_link', $permalink_filter );
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
    ?>
    <p><?php esc_html_e( 'There are no settings for this widget.', 'pedestal' ); ?></p>
    <?php
    }

    public function update( $new_instance, $old_instance ) {
        return [];
    }
}
