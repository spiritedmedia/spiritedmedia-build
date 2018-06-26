<?php

namespace Pedestal\Widgets\DFP;

use Timber\Timber;

class Rail_Right_Widget extends \WP_Widget {

    public function __construct() {

        parent::__construct(
            'pedestal_dfp_rail_right',
            esc_html__( 'DFP: Right Rail', 'pedestal' ),
            [
                'description' => esc_html__( 'Display the specified DFP ad unit.', 'pedestal' ),
            ]
        );

    }

    public function widget( $args, $instance ) {

        $context = Timber::get_context();

        echo $args['before_widget'];

        switch ( $instance['type'] ) {
            case 'dfp_rail_right_medrect':
                Timber::render( 'widgets/dfp-rail-right-medrect.twig', $context );
                break;

            case 'dfp_rail_right_skyscraper':
                Timber::render( 'widgets/dfp-rail-right-skyscraper.twig', $context );
                Timber::render( 'partials/adverts/sidebar-ad.twig' );
                break;

            case 'dfp_rail_right_skyscraper_alt':
                Timber::render( 'widgets/dfp-rail-right-skyscraper-alt.twig', $context );
                break;

            default:
                break;
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {

        $instance = array_merge( [
            'title' => esc_html__( 'DFP Ad Units: Right Rail', 'pedestal' ),
            'type'  => 'dfp_rail_right_medrect',
        ], $instance );

        $ad_types = [
            'dfp_rail_right_medrect'        => esc_html__( 'Medium Rectangle (300x250)', 'pedestal' ),
            'dfp_rail_right_skyscraper'     => esc_html__( 'Skyscraper (300x600)', 'pedestal' ),
            'dfp_rail_right_skyscraper_alt' => esc_html__( 'Skyscraper (300x600) [Alternate]', 'pedestal' ),
        ];

        ob_start();
        ?>

        <p>
            <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Ad unit:' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'type' ); ?>" id="<?php echo $this->get_field_id( 'type' ); ?>">
                <?php foreach ( $ad_types as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $instance['type'] ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <?php
        echo ob_get_clean();

    }

    public function update( $new_instance, $old_instance ) {
        $instance = [];
        $instance['type'] = sanitize_key( $new_instance['type'] );
        return $instance;
    }
}
