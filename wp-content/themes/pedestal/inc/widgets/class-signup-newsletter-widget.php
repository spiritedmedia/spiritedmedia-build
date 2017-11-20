<?php

namespace Pedestal\Widgets;

use Timber\Timber;

class Signup_Newsletter_Widget extends \WP_Widget {

    public function __construct() {

        parent::__construct(
            'pedestal_signup_newsletter',
            esc_html__( 'Newsletter Signup', 'pedestal' ),
            [
                'classname'   => 'signup-email--daily signup-email',
                'description' => esc_html__( 'Displays a form where users can sign up for the newsletter.', 'pedestal' ),
            ]
        );

    }

    public function widget( $args, $instance ) {

        $instance = [
            'title' => '',
        ];

        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }

        $context = Timber::get_context();
        $context['gaLocation'] = 'Widget';
        Timber::render( 'partials/signup-daily.twig', $context );

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
