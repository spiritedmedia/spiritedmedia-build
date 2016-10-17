<?php

namespace Pedestal;

use Timber\Timber;

use \Pedestal\Objects\User;

use \Pedestal\Posts\Attachment;
use \Pedestal\Posts\Entities\Embed;

class Shortcode_Manager {

    private static $instance;

    private $shortcodes = [
        'section-header' => [
            'label'          => 'Section Header',
            'listItemImage'  => 'dashicons-editor-italic',
        ],
        'user-card'          => false,
        'user-grid'          => false,
        'event'              => [
            'label'          => 'Event',
            'listItemImage'  => 'dashicons-calendar-alt',
            'attrs'          => [
                'label'      => 'Event ID',
                'attr'       => 'id',
                'type'       => 'text',
            ],
        ],
        'embed'              => [
            'label'          => 'Embed',
            'listItemImage'  => 'dashicons-twitter',
            'attrs'          => [
                'label'      => 'Embed ID',
                'attr'       => 'id',
                'type'       => 'text',
            ],
        ],
    ];

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new Shortcode_Manager;
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Set up shortcode-related actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_shortcodes' ] );
        add_action( 'init', [ $this, 'action_init_register_tablepress_shortcode_ui' ] );
    }

    /**
     * Set up shortcode-related filters
     */
    private function setup_filters() {
        $this->setup_shortcake_bakery_filters();
        $this->setup_image_shortcode_filters();

        add_filter( 'tablepress_table_render_options', [ $this, 'filter_tablepress_table_render_options' ] );
        add_filter( 'tablepress_table_output', [ $this, 'filter_tablepress_table_output_auto_url_conversion' ], 10, 3 );
        add_filter( 'tablepress_shortcode_table_default_shortcode_atts', [ $this, 'filter_tablepress_shortcode_table_default_shortcode_atts' ] );
    }

    /**
     * Setup filters for Shortcake Bakery
     */
    private function setup_shortcake_bakery_filters() {
        add_filter( 'shortcake_bakery_shortcode_callback', [ $this, 'filter_shortcake_bakery_shortcode_callback' ], 10, 4 );
        add_filter( 'shortcake_bakery_shortcode_classes', [ $this, 'filter_shortcake_bakery_shortcode_classes' ], 10, 1 );
        add_filter( 'shortcake_bakery_shortcode_ui_args', [ $this, 'filter_shortcake_bakery_shortcode_ui_args' ], 10, 1 );
        add_filter( 'shortcake_bakery_whitelisted_script_domains', function( $domains ) {
            return [
                'www.nbcphiladelphia.com',
                'player.ooyala.com',
            ];
        }, 10, 1 );
    }

    /**
     * Setup filters for image shortcode
     */
    private function setup_image_shortcode_filters() {
        add_filter( 'img_caption_shortcode', [ $this, 'filter_img_caption_shortcode' ], 10, 3 );
        add_filter( 'img_shortcode_output_img_tag', [ $this, 'filter_img_shortcode_output_img_tag' ], 10, 2 );
        add_filter( 'img_shortcode_send_to_editor_attrs', [ $this, 'filter_img_shortcode_send_to_editor_attrs' ], 10, 4 );
        add_filter( 'img_shortcode_ui_args', [ $this, 'filter_img_shortcode_ui_args' ], 10, 1 );
        add_filter( 'img_shortcode_output_after_captionify', [ $this, 'filter_img_shortcode_output_after_captionify' ], 10, 2 );
    }

    /**
     * Register shortcodes
     */
    public function action_init_register_shortcodes() {
        foreach ( $this->shortcodes as $shortcode => $ui_arguments ) {
            add_shortcode( 'pedestal-' . $shortcode, [ $this, str_replace( '-', '_', $shortcode ) ] );
            if ( $ui_arguments ) {
                shortcode_ui_register_for_shortcode( 'pedestal-' . $shortcode, $ui_arguments );
            }
        }
    }

    public function action_init_register_tablepress_shortcode_ui() {
        $ui_arguments = [
            'label'          => esc_html__( 'Table', 'pedestal' ),
            'listItemImage'  => 'dashicons-media-spreadsheet',
            'attrs'          => [
                [
                    'label'        => esc_html__( 'ID', 'pedestal' ),
                    'attr'         => 'id',
                    'type'         => 'number',
                    'description'  => esc_html__( 'Table ID. Required.', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Responsive Mode', 'pedestal' ),
                    'attr'         => 'responsive',
                    'type'         => 'select',
                    'options'      => [
                        'scroll'   => esc_html__( 'Scroll', 'pedestal' ),
                        'collapse' => esc_html__( 'Collapse', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Select the responsive mode. In most cases Scroll mode will suffice.', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Column Widths', 'pedestal' ),
                    'attr'         => 'column_widths',
                    'type'         => 'text',
                    'description'  => esc_html__( 'String with column widths, separated by the |-symbol (pipe). E.G. "40px|50px|30px|40px" or "20%|60%|20%"', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Alternating Row Colors', 'pedestal' ),
                    'attr'         => 'alternating_row_colors',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether the table will get alternating row colors (“zebra striping”)', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Row Hover Effect', 'pedestal' ),
                    'attr'         => 'row_hover',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether table rows will be highlighted with a different background color if the mouse hovers over them', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Table Header', 'pedestal' ),
                    'attr'         => 'table_head',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether the first row will be treated as a header', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'First Column Header', 'pedestal' ),
                    'attr'         => 'first_column_th',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether the first column will be treated as a header', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Table Footer', 'pedestal' ),
                    'attr'         => 'table_foot',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether the last row will be treated as a footer', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Display Table Name', 'pedestal' ),
                    'attr'         => 'print_name',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether the name of the table shall be printed above/below the table', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Table Name Position', 'pedestal' ),
                    'attr'         => 'print_name_position',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'above'  => esc_html__( 'Above', 'pedestal' ),
                        'below' => esc_html__( 'Below', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Position for displaying table name, if it is set to display', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Display Table Description', 'pedestal' ),
                    'attr'         => 'print_description',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether the description of the table shall be printed above/below the table', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Table Description Position', 'pedestal' ),
                    'attr'         => 'print_description_position',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'above'  => esc_html__( 'Above', 'pedestal' ),
                        'below' => esc_html__( 'Below', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Position for displaying table description, if it is set to display', 'pedestal' ),
                ],
                [
                    'label'        => esc_html__( 'Use DataTables JS Library', 'pedestal' ),
                    'attr'         => 'use_datatables',
                    'type'         => 'select',
                    'options'      => [
                        'empty' => '',
                        'true'  => esc_html__( 'True', 'pedestal' ),
                        'false' => esc_html__( 'False', 'pedestal' ),
                    ],
                    'description'  => esc_html__( 'Whether the DataTables JavaScript library will be used with this table (will only work if the first row is treated as a header, either by the setting on the table’s “Edit” screen or by setting manually here)', 'pedestal' ),
                ],
            ],
        ];
        shortcode_ui_register_for_shortcode( 'tablepress', $ui_arguments );
    }

    /**
     * Remove unwanted or overridden Bakery shortcodes
     */
    public function filter_shortcake_bakery_shortcode_classes( $classes ) {
        $remove = [
            'Shortcake_Bakery\Shortcodes\ABC_News',
            'Shortcake_Bakery\Shortcodes\Guardian',
            'Shortcake_Bakery\Shortcodes\Livestream',
            'Shortcake_Bakery\Shortcodes\PDF',
            'Shortcake_Bakery\Shortcodes\Playbuzz',
            'Shortcake_Bakery\Shortcodes\Rap_Genius',
            'Shortcake_Bakery\Shortcodes\Silk',
            'Shortcake_Bakery\Shortcodes\Videoo',
            'Shortcake_Bakery\Shortcodes\Vimeo',
        ];
        $override = [
            'Shortcake_Bakery\Shortcodes\Iframe',
            'Shortcake_Bakery\Shortcodes\Twitter',
            'Shortcake_Bakery\Shortcodes\Script',
            'Shortcake_Bakery\Shortcodes\GoogleDocs',
            'Shortcake_Bakery\Shortcodes\Instagram',
        ];
        $classes = array_diff( $classes, $remove, $override );
        $new = [
            // Order matters here.
            // Iframe class should be last as it is more generic then other embed code.
            '\Pedestal\Shortcodes\Soundcite',
            '\Pedestal\Shortcodes\Twitter',
            '\Pedestal\Shortcodes\Script',
            '\Pedestal\Shortcodes\GoogleDocs',
            '\Pedestal\Shortcodes\Instagram',
            '\Pedestal\Shortcodes\Iframe',
        ];
        return array_merge( $classes, $new );
    }

    /**
     * Add more UI fields to every Shortcake Bakery shortcode
     */
    public function filter_shortcake_bakery_shortcode_ui_args( $ui_args ) {
        $ui_args['attrs'][] = [
            'label'       => __( 'Caption', 'pedestal' ),
            'attr'        => 'caption',
            'type'        => 'textarea',
            'description' => __( 'A caption to describe the embed', 'pedestal' ),
        ];
        return $ui_args;
    }

    /**
     * Filter the output HTML of Bakery shortcodes
     */
    public function filter_shortcake_bakery_shortcode_callback( $output, $shortcode_tag, $attrs, $content ) {

        if ( 'soundcite' === $shortcode_tag ) {
            return $output;
        }
        $default_classes = 'pedestal-shortcode shortcake-bakery-shortcode shortcake-bakery-shortcode-' . $shortcode_tag;
        $embed_type = 'embed';

        switch ( $shortcode_tag ) {
            case 'instagram':
                if ( empty( $attrs['url'] ) ) {
                    return '';
                }
                $embed_type = 'script';

                break;

            case 'youtube':
                if ( empty( $attrs['url'] ) ) {
                    return '';
                }

                $embed_id = Embed::get_youtube_id_from_url( $attrs['url'] );
                $list_id = Embed::get_youtube_list_id_from_url( $attrs['url'] );

                if ( empty( $embed_id ) ) {
                    return '';
                }

                // ID is always the second part to the path
                $embed_url = 'https://youtube.com/embed/' . $embed_id;
                if ( ! empty( $list_id ) ) {
                    $embed_url = add_query_arg( 'list', $list_id, $embed_url );
                }
                $embed_url = apply_filters( 'shortcake_bakery_youtube_embed_url', $embed_url, $attrs );
                $output = sprintf( '<iframe class="%s shortcake-bakery-responsive" width="640" height="360" src="%s" frameborder="0"></iframe>', $default_classes, $embed_url );
                break;

            default:
                break;
        }

        $figure_atts = [
            'caption' => isset( $attrs['caption'] ) ? $attrs['caption'] : '',
        ];

        $figure_atts['classes'] = 'op-interactive';

        // Protocol relative URLs should be replaced with https
        $output = str_replace( 'src="//', 'src="https://', $output );

        if ( is_feed( 'fias' ) ) {
            $figure_atts['element_wrap'] = 'iframe';
        }

        $figure = new \Pedestal\Objects\Figure( $embed_type, $output, $figure_atts );
        return $figure->get_html();
    }

    /**
     * Use `Attachhment->get_html()` for img shortcode
     */
    public function filter_img_shortcode_output_img_tag( $html, $attrs ) {
        $attachment = $attrs['attachment'];
        $size = $attrs['size'];

        $img_classes = 'c-figure__content';
        $img_atts = [
            'class' => $img_classes,
        ];
        if ( isset( $attrs['ignore_srcset'] ) && 'true' === $attrs['ignore_srcset'] ) {
            $img_atts['srcset'] = '';
            $img_atts['sizes'] = '';
        }
        if ( ! empty( $attachment ) && ! empty( $size ) ) {
            $obj = Attachment::get_by_post_id( (int) $attachment );

            if ( is_feed( 'fias' ) ) {
                $size = 'full';
            }

            return $obj->get_html( $size, $img_atts );
        } elseif ( ! empty( $src ) ) {
            $img_atts['src'] = $attrs['src'];
            $img_atts['alt'] = $attrs['alt'];
            return Attachment::get_img_html( $img_atts );
        } else {
            return '';
        }
    }

    /**
     * Add additional attributes to image caption shortcode
     */
    public function filter_img_shortcode_send_to_editor_attrs( $shortcode_attrs, $html, $attachment_id, $attachment ) {
        $obj = Attachment::get_by_post_id( (int) $attachment_id );
        if ( $credit = $obj->get_credit() ) {
            $shortcode_attrs = wp_parse_args( [
                'credit' => $credit,
            ], $shortcode_attrs );
        }
        if ( $credit_link = $obj->get_credit_link() ) {
            $shortcode_attrs = wp_parse_args( [
                'credit_link' => $credit_link,
            ], $shortcode_attrs );
        }
        return $shortcode_attrs;
    }

    public function filter_img_shortcode_output_after_captionify( $image_html, $attr ) {
        return Attachment::get_img_caption_html( $image_html, $attr );
    }

    /**
     * Filter caption output
     *
     * @return text HTML content describing embedded figure
     */
    public function filter_img_caption_shortcode( $val, $attr, $content = null ) {
        return $content;
    }

    /**
     * Add new fields to img shortcode ui
     */
    public function filter_img_shortcode_ui_args( $shortcode_ui_args ) {
        $shortcode_ui_args['attrs'][] = [
            'label'       => esc_html__( 'Credit', 'pedestal' ),
            'attr'        => 'credit',
            'type'        => 'text',
            'placeholder' => esc_attr__( 'Credit for the image', 'pedestal' ),
            'description' => esc_html__( 'Quote marks and HTML tags are not allowed', 'pedestal' ),
        ];
        $shortcode_ui_args['attrs'][] = [
            'label'       => esc_html__( 'Credit Link', 'pedestal' ),
            'attr'        => 'credit_link',
            'type'        => 'url',
            'placeholder' => esc_attr__( 'URL to link the credit', 'pedestal' ),
            'description' => esc_html__( 'Must be a valid URL', 'pedestal' ),
        ];
        $shortcode_ui_args['attrs'][] = [
            'label'       => esc_html__( 'Ignore srcset', 'pedestal' ),
            'attr'        => 'ignore_srcset',
            'type'        => 'checkbox',
        ];
        return $shortcode_ui_args;
    }

    public function filter_tablepress_table_render_options( $render_options ) {
        $disable = [
            'datatables_paginate',
            'datatables_lengthchange',
            'datatables_filter',
        ];
        foreach ( $disable as $disable_option ) {
            $render_options[ $disable_option ] = false;
        }
        return $render_options;
    }

    public function filter_tablepress_table_output_auto_url_conversion( $output, $table, $render_options ) {
        if ( $render_options['automatic_url_conversion'] ) {
            $output = make_clickable( $output );
        }

        if ( $render_options['automatic_url_conversion_new_window'] && $render_options['automatic_url_conversion_rel_nofollow'] ) {
            $output = str_replace( '<a href="http', '<a target="_blank" rel="nofollow" href="http', $output );
        } elseif ( $render_options['automatic_url_conversion_new_window'] ) {
            $output = str_replace( '<a href="http', '<a target="_blank" href="http', $output );
        } elseif ( $render_options['automatic_url_conversion_rel_nofollow'] ) {
            $output = str_replace( '<a href="http', '<a rel="nofollow" href="http', $output );
        }

        return $output;
    }

    public function filter_tablepress_shortcode_table_default_shortcode_atts( $default_atts ) {
        return array_merge( $default_atts, [
            'automatic_url_conversion'              => false,
            'automatic_url_conversion_new_window'   => false,
            'automatic_url_conversion_rel_nofollow' => false,
        ] );
    }

    public function user_card( $atts, $content ) {

        // @TODO
        // @codingStandardsIgnoreStart
        extract( shortcode_atts( [
            'id'       => '',
            'format'   => 'extended',
            'twitter'  => 1,
            'float'    => 1,
            'img_size' => 'thumbnail',
        ], $atts ) );
        // @codingStandardsIgnoreEnd

        $out = '';
        if ( empty( $id ) ) {
            return $out;
        }

        // @TODO
        // @codingStandardsIgnoreStart
        extract( User_Management::get_users_from_csv( $id ) );
        // @codingStandardsIgnoreEnd

        if ( empty( $users ) ) {
            return $out;
        }
        $user = new \Pedestal\Objects\User( $users[0] );

        $context = [
            'options' => [
                'format'  => $format,
                'twitter' => (bool) $twitter,
                'float'   => (bool) $float,
            ],
            'user' => $user,
        ];

        $out .= '<div class="pedestal-shortcode  pedestal-shortcode--user-card">';

        ob_start();
        Timber::render( 'partials/shortcode/user-card.twig', $context );
        $out .= ob_get_clean();
        $out .= '</div>';
        return $out;

    }

    /**
     * Do the user-grid shortcode
     */
    public function user_grid( $atts, $content ) {

        $out = '';
        if ( empty( $atts['ids'] ) ) {
            return $out;
        }

        // @TODO
        // @codingStandardsIgnoreStart
        extract( User_Management::get_users_from_csv( $atts['ids'] ) );
        // @codingStandardsIgnoreEnd

        if ( empty( $users ) ) {
            return $out;
        }

        $out .= '<ul class="pedestal-shortcode user-grid">';
        foreach ( $ids as $id ) {
            $users_filter = wp_filter_object_list( $users, [ 'ID' => $id ] );
            if ( empty( $users_filter ) ) {
                continue;
            }
            $user = array_shift( $users_filter );

            $user = new \Pedestal\Objects\User( $user );
            $out .= '<li class="user-grid__user">';

            $context = [ 'user' => $user ];
            ob_start();
            Timber::render( 'partials/shortcode/user-card-grid.twig', $context );
            $out .= ob_get_clean();

            $out .= '</li>';
        }

        $out .= '</ul>';

        return $out;
    }

    /**
     * Generate section headers
     */
    public function section_header( $atts, $content ) {
        return '<div class="' . esc_attr( 'pedestal-shortcode  section-header  [ o-rule--pedal  o-rule ]' ) . '"></div>';
    }

    /*
     * Do the event shortcode
     */
    public function event( $attrs, $content ) {

        if ( empty( $attrs['id'] ) ) {
            return '';
        }

        $obj = \Pedestal\Posts\Entities\Event::get_by_post_id( (int) $attrs['id'] );
        if ( ! $obj || 'event' !== $obj->get_type() ) {
            return '';
        }

        $context = array_merge( Timber::get_context(), [
            'item'             => $obj,
            'datetime_format'  => PEDESTAL_DATETIME_FORMAT,
        ] );

        ob_start();
        $out = Timber::render( 'partials/shortcode/event.twig', $context );
        ob_get_clean();
        return $out;
    }

    /**
     * Do the embed shortcode
     */
    public function embed( $attrs, $content ) {

        if ( empty( $attrs['id'] ) ) {
            return '';
        }

        $ok_statuses = [
            'publish',
            'private',
        ];

        $embed = Embed::get_by_post_id( (int) $attrs['id'] );
        if ( ! $embed instanceof Embed || ! in_array( $embed->get_status(), $ok_statuses ) ) {
            return '';
        }

        $embed_type = $embed->get_embed_type();
        if ( ! array_key_exists( $embed_type, Embed::get_providers() ) ) {
            return '';
        }

        $context = [
            'item'               => $embed,
            'datetime_format'    => PEDESTAL_DATETIME_FORMAT,
        ];
        $files = [
            'partials/shortcode/embed-' . $embed_type . '.twig',
            'partials/shortcode/embed.twig',
        ];
        ob_start();
        $out = Timber::render( $files, $context );
        ob_get_clean();
        return $out;
    }
}
