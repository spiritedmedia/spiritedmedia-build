<?php

namespace Pedestal\Shortcodes;

use Timber\Timber;

use \Shortcake_Bakery\Shortcodes\Shortcode;

class Pullquote extends Shortcode {

    public static function get_shortcode_ui_args() {
        return [
            'label'          => esc_html__( 'Pullquote', 'pedestal' ),
            'listItemImage'  => 'dashicons-format-quote',
            'attrs'          => [
                [
                    'label'        => esc_html__( 'Pullquote Content', 'pedestal' ),
                    'attr'         => 'content',
                    'type'         => 'textarea',
                    'description'  => esc_html__( "The text to be displayed as the pullquote content. Please omit opening and closing quotation marks (they'll be added automatically)", 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Alignment', 'pedestal' ),
                    'attr'         => 'align',
                    'type'         => 'select',
                    'options'      => [
                        'right'        => esc_html__( 'Right', 'pedestal' ),
                        'left'         => esc_html__( 'Left', 'pedestal' ),
                    ],
                    'value'        => 'right',
                ],
                [
                    'label'        => esc_html__( 'Credit', 'pedestal' ),
                    'attr'         => 'credit',
                    'type'         => 'text',
                    'description'  => esc_html__( 'The source of the pullquote. Optional.', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Credit Link', 'pedestal' ),
                    'attr'         => 'credit_link',
                    'type'         => 'url',
                    'description'  => esc_html__( 'URL to link the credit to. Optional.', 'pedestal' ),
                ],
            ],
        ];
    }

    public static function callback( $attrs, $content = '' ) {

        if ( empty( $attrs['content'] ) ) {
            return '';
        }

        $context = $attrs;

        ob_start();
        $out = Timber::render( 'partials/pullquote.twig', $context );
        ob_get_clean();

        return $out;

    }
}
