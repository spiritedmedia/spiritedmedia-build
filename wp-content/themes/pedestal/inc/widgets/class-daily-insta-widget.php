<?php

namespace Pedestal\Widgets;

use Timber\Timber;
use Pedestal\Posts\Entities\Embed;

class Daily_Insta_Widget extends \WP_Widget {

    public function __construct() {
        $desc = "Today's Instagram of the Day — uses yesterday's image until
        today's has been set.";

        parent::__construct( 'pedestal-widget-daily-insta',
            esc_html( 'Instagram of the Day' ),
            [
                'description' => esc_html( $desc ),
            ]
        );
    }

    public function widget( $args, $instance ) {
        $daily_insta = Embed::get_instagram_of_the_day( [
            'fallback_previous' => true,
            'context'           => 'widget',
        ] );

        if ( empty( $daily_insta ) ) {
            return false;
        }

        echo $args['before_widget'];
        echo $daily_insta;
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        echo '<p>' . esc_html( 'There are no settings for this widget.' ) . '</p>';
    }

    public function update( $new_instance, $old_instance ) {
        return [];
    }
}
