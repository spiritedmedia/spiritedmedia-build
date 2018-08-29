<?php

namespace Pedestal;

use Timber\Timber;
use Pedestal\Objects\{
    User,
    YouTube
};
use Pedestal\Posts\Attachment;
use Pedestal\Posts\Entities\{
    Embed,
    Event
};
use Pedestal\Registrations\Post_Types\Types;

class Shortcode_Manager {

    private static $instance;

    /**
     * All of the shortcode tags loaded by Shortcake Bakery
     *
     * @var array
     */
    private $shortcake_bakery_tags = [];

    private $shortcodes = [
        'brand-heading'      => [
            'inner_content'  => [
                'label'      => 'Content',
            ],
            'label'          => 'Brand Heading',
            'description'    => 'A section heading featuring the mini site logo next to some text.',
            'listItemImage'  => 'dashicons-list-view',
        ],
        'checklist'          => [],
        'cta-button'         => [
            'attrs'          => [
                'label'      => 'URL',
                'attr'       => 'url',
                'type'       => 'url',
            ],
            'inner_content'  => [
                'label'      => 'Button Text',
            ],
            'label'          => 'Call to Action Button',
            'listItemImage'  => 'dashicons-lightbulb',
        ],
        'donate-form'        => [
            'label'          => 'Donation Form',
            'listItemImage'  => 'dashicons-money',
            'attrs'          => [
                'label'      => 'Submit Button Text',
                'attr'       => 'submit_text',
                'type'       => 'text',
            ],
        ],
        'email-signup-form'  => [],
        'embed'              => [
            'label'          => 'Embed',
            'listItemImage'  => 'dashicons-twitter',
            'attrs'          => [
                'label'      => 'Embed ID',
                'attr'       => 'id',
                'type'       => 'text',
            ],
        ],
        'event'              => [
            'label'          => 'Event',
            'listItemImage'  => 'dashicons-calendar-alt',
            'attrs'          => [
                'label'      => 'Event ID',
                'attr'       => 'id',
                'type'       => 'text',
            ],
        ],
        'user-grid'          => [],
        'search-form'        => [],
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
        add_action( 'init', [ $this, 'action_init_register_documentcloud_shortcode_ui' ] );
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
        add_filter( 'shortcake_bakery_shortcode_callback', [ $this, 'filter_shortcake_bakery_shortcode_callback_instagram_script' ], 10, 2 );
        add_filter( 'shortcake_bakery_shortcode_classes', [ $this, 'filter_shortcake_bakery_shortcode_classes' ], 10, 1 );
        add_filter( 'shortcake_bakery_shortcode_ui_args', [ $this, 'filter_shortcake_bakery_shortcode_ui_args' ], 10, 1 );
        add_filter( 'shortcake_bakery_whitelisted_script_domains', function( $domains ) {
            return [
                'www.nbcphiladelphia.com',
                'player.ooyala.com',
            ];
        }, 10, 1 );
        add_filter( 'shortcake_bakery_facebook_url_patterns', function( $patterns ) {
            return array_merge( $patterns, [
                '#https?:\/\/www?\.facebook\.com\/?#',
            ] );
        } );
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
        add_filter( 'shortcode_atts_img', function( $atts, $thing ) {
            // Instant Articles can't have links wrapped around <img>. It prevents
            // image captions from rendering.
            if ( is_feed( 'fias' ) ) {
                $atts['linkto'] = null;
                $atts['url'] = null;
            }
            return $atts;
        }, 10 , 2 );
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
     * Register UI for DocumentCloud shortcode
     */
    public function action_init_register_documentcloud_shortcode_ui() {
        $ui_arguments = [
            'label'          => 'DocumentCloud',
            'listItemImage'  => 'dashicons-media-document',
            'attrs'          => [
                [
                    'label'      => 'URL to document, page, or note (Required)',
                    'attr'       => 'url',
                    'type'       => 'text',
                ],
            ],
        ];
        shortcode_ui_register_for_shortcode( 'documentcloud', $ui_arguments );
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
            '\Pedestal\Shortcodes\Pullquote',
            '\Pedestal\Shortcodes\Soundcite',
            '\Pedestal\Shortcodes\Twitter',
            '\Pedestal\Shortcodes\Script',
            '\Pedestal\Shortcodes\GoogleDocs',
            '\Pedestal\Shortcodes\Instagram',
            '\Pedestal\Shortcodes\Iframe',
        ];
        $classes = array_merge( $classes, $new );

        foreach ( $classes as $class ) {
            $this->shortcake_bakery_tags[ $class::get_shortcode_tag() ] = '';
        }

        return $classes;
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
     *
     * Wraps most shortcode content in a figure element
     */
    public function filter_shortcake_bakery_shortcode_callback( $output, $shortcode_tag, $attrs, $content ) {

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
                $embed_url = YouTube::get_embeddable_url( $attrs['url'] );
                $embed_url = apply_filters( 'shortcake_bakery_youtube_embed_url', $embed_url, $attrs );
                $output = sprintf( '<iframe class="%s shortcake-bakery-responsive" width="640" height="360" src="%s" frameborder="0"></iframe>', $default_classes, $embed_url );
                break;

            case 'soundcite':
            case 'pullquote':
                // These shortcodes need no additional processing, so just return
                return $output;
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
            $figure_atts['element_figure_wrap'] = 'iframe';
        }

        $figure = new \Pedestal\Objects\Figure( $embed_type, $output, $figure_atts );
        return $figure->get_html();
    }

    /**
     * Enqueue the Instagram embed.js only one time on the page no matter how many
     * Instagram embeds are on the page.
     *
     * @param  string $output        The output to modify
     * @param  string $shortcode_tag The shortcode tag being called
     * @return string                Modified output
     */
    public function filter_shortcake_bakery_shortcode_callback_instagram_script( $output, $shortcode_tag ) {
        if ( 'instagram' != $shortcode_tag ) {
            return $output;
        }
        $output = str_replace( '<script async defer src="https://www.instagram.com/embed.js"></script>', '', $output );
        $deps = [];
        $ver = null;
        $in_footer = true;
        wp_enqueue_script( 'instagram-embed', 'https://www.instagram.com/embed.js', $deps, $ver, $in_footer );
        return $output;
    }

    /**
     * Use `Attachhment->get_html()` for img shortcode
     */
    public function filter_img_shortcode_output_img_tag( $html, $attrs ) {
        $attachment = $attrs['attachment'];
        $size = $attrs['size'];
        $src = $attrs['src'];

        $img_classes = 'c-figure__content';
        $img_atts = [
            'class' => $img_classes,
        ];
        if ( isset( $attrs['ignore_srcset'] ) && 'true' === $attrs['ignore_srcset'] ) {
            $img_atts['srcset'] = '';
            $img_atts['sizes'] = '';
        }
        if ( ! empty( $attachment ) && ! empty( $size ) ) {
            $obj = Attachment::get( (int) $attachment );
            if ( ! Types::is_attachment( $obj ) ) {
                return '';
            }
            if ( 'full' == $size ) {
                $meta = $obj->get_metadata();
                if ( ! empty( $meta['width'] ) && 1024 < $meta['width'] ) {
                    $size = 'large';
                }
            }

            if ( is_feed( 'fias' ) ) {
                $size = 'full';
            }

            return $obj->get_html( $size, $img_atts );
        } elseif ( ! empty( $src ) ) {
            $img_atts['src'] = $src;
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
        $obj = Attachment::get( (int) $attachment_id );
        if ( ! $obj instanceof Attachment ) {
            return $shortcode_attrs;
        }

        $credit = $obj->get_credit();
        $credit_link = $obj->get_credit_link();
        if ( $credit ) {
            $shortcode_attrs = wp_parse_args( [
                'credit' => $credit,
            ], $shortcode_attrs );
        }
        if ( $credit_link ) {
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

    /**
     * Get the shortcode tags loaded by Shortcake Bakery
     *
     * @return array
     */
    public function get_shortcake_bakery_tags() {
        return $this->shortcake_bakery_tags;
    }

    /**
     * Insert a grid of users
     *
     * Attributes:
     *
     * - `ids` : Comma-separated list of user IDs in the desired order
     */
    public function user_grid( $atts, $content ) {

        $out = '';
        if ( empty( $atts['ids'] ) ) {
            return;
        }

        $users = User_Management::get_users_from_csv( $atts['ids'] );

        if ( empty( $users ) ) {
            return;
        }

        $out .= '<div class="user-grid">';
        foreach ( $users['ids'] as $id ) {
            $users_filter = wp_filter_object_list( $users['users'], [
                'ID' => $id,
            ] );
            if ( empty( $users_filter ) ) {
                continue;
            }
            $user = array_shift( $users_filter );

            $user = new \Pedestal\Objects\User( $user );
            $out .= '<div class="user-grid__user">';

            $context = [
                'user'   => $user,
                'format' => 'grid',
            ];
            ob_start();
            Timber::render( 'partials/user-card.twig', $context );
            $out .= ob_get_clean();

            $out .= '</div>';
        }

        $out .= '</div>';

        return $out;
    }

    /*
     * Insert one of our Event post types
     *
     * Attributes:
     *
     * - `id` : Post ID of the Event
     */
    public function event( $attrs, $content ) {

        if ( empty( $attrs['id'] ) ) {
            return '';
        }

        $ped_event = Event::get( $attrs['id'] );
        if ( ! method_exists( $ped_event, 'get_context' ) ) {
            return '';
        }

        $context = $ped_event->get_context( Timber::get_context() );
        $context['slot'] = 'shortcode';

        $html = '<div class="pedestal-shortcode--event pedestal-shortcode">';
        ob_start();
            Timber::render( 'partials/event.twig', $context );
        $html .= ob_get_clean();
        $html .= '</div>';
        return $html;
    }

    /**
     * Insert one of our Embed post types
     *
     * Attributes:
     *
     * - `id` : Post ID of the Embed
     */
    public function embed( $attrs, $content ) {

        if ( empty( $attrs['id'] ) ) {
            return '';
        }

        $ok_statuses = [
            'publish',
            'private',
        ];

        $embed = Embed::get( (int) $attrs['id'] );
        if ( ! $embed instanceof Embed || ! in_array( $embed->get_status(), $ok_statuses ) ) {
            return '';
        }

        $embed_type = $embed->get_embed_type();
        if ( ! Embed::is_embeddable_service( $embed_type ) ) {
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

    /**
     * Do the donation form
     */
    public function donate_form( $attrs, $content ) {
        $attrs = wp_parse_args( $attrs, [
            'submit_text' => 'Support ' . PEDESTAL_BLOG_NAME,
        ] );
        $stripe_logo = get_template_directory() . '/assets/images/membership/stripe-logo-white.svg';
        $stripe_logo = file_get_contents( $stripe_logo );
        $context = [
            'nrh_endpoint_domain' => 'https://checkout.fundjournalism.org',
            'nrh_property'        => PEDESTAL_NRH_PROPERTY,
            'campaign'            => $_GET['campaign'] ?? '',
            'submit_text'         => $attrs['submit_text'],
            'stripe_logo'         => $stripe_logo,
        ];
        $context = apply_filters( 'pedestal_donate_form_context', $context );

        ob_start();
        $out = Timber::render( 'partials/shortcode/donate-form.twig', $context );
        return ob_get_clean();
    }

    /**
     * Do the email signup form
     */
    public function email_signup_form( $attrs ) {
        $context = Timber::get_context();

        ob_start();
        Timber::render( 'partials/shortcode/email-signup.twig', $context );
        return '<div class="signup-email--shortcode">' . ob_get_clean() . '</div>';
    }

    /**
     * Create a CTA button
     *
     * Attributes:
     *
     * - `url` : Specify the URL to link the button to
     */
    public function cta_button( $attrs, $content = '' ) {
        $context = wp_parse_args( $attrs, [
            'url' => '',
        ] );
        $context['content'] = $content;
        ob_start();
        $out = Timber::render( 'partials/shortcode/cta-button.twig', $context );
        return ob_get_clean();
    }

    /**
     * Make a `<ul>` into a checklist
     *
     * Wrap this around a valid `<ul>` and each `<li>` will be preceded by a
     * checkmark.
     *
     * Other shortcodes will be rendered within this one.
     */
    public function checklist( $attrs, $content = '' ) {
        $context = [
            'content' => $content,
        ];
        ob_start();
        $out = Timber::render( 'partials/shortcode/checklist.twig', $context );
        return do_shortcode( ob_get_clean() );
    }

    /**
     * Create a heading preceded by a small site logo
     *
     * Attributes:
     *
     * - `el` : Specify the element of the heading
     */
    public function brand_heading( $attrs, $content = '' ) {
        $context = wp_parse_args( $attrs, [
            'el' => 'h2',
        ] );
        $context['content'] = $content;
        ob_start();
        $out = Timber::render( 'partials/shortcode/brand-heading.twig', $context );
        return ob_get_clean();
    }

    /**
     * Display a site search form
     */
    public function search_form( $attrs, $content = '' ) {
        $context = [
            'site_url'    => get_site_url(),
            'domain_name' => PEDESTAL_DOMAIN_PRETTY,
        ];
        ob_start();
        $out = Timber::render( 'partials/shortcode/search-form.twig', $context );
        return ob_get_clean();
    }
}
