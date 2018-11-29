<?php

namespace Pedestal;

use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Message_Spot {

    /**
     * Icon names and corresponding human-friendly labels for the buttons
     *
     * @var array
     */
    protected $icon_buttons = [
        'calendar-alt'         => 'Calendar (Solid)',
        'envelope'             => 'Envelope (Solid)',
        'heart'                => 'Heart (Solid)',
        'hand-point-right'     => 'Hand pointing right',
        'list-ol'              => 'Ordered list',
        'check'                => 'Checkmark',
        'star'                 => 'Star',
        'sun'                  => 'Sun',
        'calendar-alt-regular' => 'Calendar (Outline)',
        'envelope-o'           => 'Envelope (Outline)',
        'heart-regular'        => 'Heart (Outline)',
        'location-arrow'       => 'Location arrow',
        'comments'             => 'Comment bubbles',
        'coffee'               => 'Coffee cup',
        'link'                 => 'Link',
        'hand-spock'           => 'Spock Hand',
    ];

    /**
     * Default values for the message model
     *
     * Shared between the static PHP preview and the Backbone preview.
     *
     * @var array
     */
    private static $model_defaults = [
        'standard' => [
            'type'         => 'standard',
            'url'          => '#',
            'icon'         => 'link',
            'title'        => 'Default Title',
            'body'         => 'This is the default message body',
            'button_label' => 'Default Label',
        ],
        'override' => [
            'enabled'      => 'false',
            'type'         => 'override',
            'url'          => '#',
            'icon'         => 'bolt-solid',
            'title'        => 'Breaking News',
            'body'         => 'This is the default message body',
        ],
    ];

    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Set up actions
     */
    protected function setup_actions() {
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
        add_action(
            'admin_print_scripts-appearance_page_pedestal_message_spot',
            [ $this, 'action_admin_print_scripts' ]
        );
        add_action( 'update_option_pedestal_message_spot', function() {
            do_action( 'rt_nginx_helper_purge_all' );
        } );
        add_action( 'wp_ajax_pedestal-message-spot-override', [ $this, 'action_wp_ajax_override' ] );
    }

    /**
     * Set up filters
     */
    protected function setup_filters() {
        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
        add_filter( 'pre_update_option_pedestal_message_spot', [ $this, 'filter_pre_update_option' ], 10, 2 );
    }

    /**
     * Load the scripts
     */
    public function action_admin_print_scripts() {
        wp_enqueue_script(
            'pedestal-message-spot',
            PEDESTAL_DIST_DIRECTORY_URI . '/js/message-spot-admin.js',
            [ 'jquery', 'backbone', 'twig' ],
            PEDESTAL_VERSION,
            true
        );

        $preview_url = home_url() . '/api/component-preview/message-spot/';
        wp_localize_script( 'pedestal-message-spot', 'pedestalPreviewURL', $preview_url );
        wp_localize_script( 'pedestal-message-spot', 'messagePreviewDefaults', self::$model_defaults );
        wp_localize_script( 'pedestal-message-spot', 'PedestalIcons', Icons::get_all_icons_svg() );
    }

    /**
     * Register the admin UI
     */
    public function action_init_after_post_types_registered() {
        $override_group = new \Fieldmanager_Group( 'Message Override', [
            'collapsible' => true,
            'children' => [
                'enabled' => new \Fieldmanager_Radios( false, [
                    'description'   => 'The override is shown to <strong>every reader on every page</strong> and suppresses other messages specified below. Use sparingly.',
                    'escape' => [
                        'description' => 'wp_kses_post',
                    ],
                    'default_value' => 'false',
                    'options' => [
                        'false' => 'Override Off',
                        'true' => 'Override On',
                    ],
                ] ),
                'id' => new \Fieldmanager_Hidden( false, [
                    'default_value' => 'override',
                ] ),
                'type' => new \Fieldmanager_Hidden( [
                    'default_value' => 'override',
                ] ),
                'preview' => new \Fieldmanager_TextField( 'Preview', [
                    'template' => get_template_directory() . '/templates/raw/message-spot.php',
                ] ),
                'preview_model' => new \Fieldmanager_Hidden( false, [
                    'sanitize' => [ '\Pedestal\Utils\Utils', 'return_same' ],
                ] ),
                'title' => new \Fieldmanager_TextField( 'Message Title', [
                    'default_value' => 'Breaking News',
                    'description'   => '“Breaking News”, “Developing Story”, etc.',
                ] ),
                'post' => new \Fieldmanager_Autocomplete( 'Article', [
                    'description' => 'Start typing to retrieve a post',
                    'attributes'  => [
                        'placeholder' => '…',
                        'size'        => 75,
                    ],
                    'datasource' => new \Fieldmanager_Datasource_Post( [
                        'query_args' => [
                            'post_type'      => Types::get_post_types(),
                            'posts_per_page' => 15,
                            'post_status'    => [ 'publish' ],
                        ],
                    ] ),
                ] ),
                'post_title' => new \Fieldmanager_Hidden(),
                'body' => new \Fieldmanager_Textfield( 'Headline', [
                    'description' => 'Adjust headline for this message',
                    'attributes'  => [
                        'size' => 75,
                    ],
                ] ),
                'url' => new \Fieldmanager_Hidden(),
                'icon' => new \Fieldmanager_Hidden( [
                    'default_value' => 'bolt-solid',
                ] ),
            ],
        ] );

        $messages_group = new \Fieldmanager_Group( 'Message', [
            'field_class'       => 'message fm-group',
            'add_more_label'    => 'Add New Message',
            'add_more_position' => 'top',
            'sortable'          => true,
            'limit'             => 0,
            'save_empty'        => false,
            'extra_elements'    => 0,
            'collapsible'       => true,
            'collapsed'         => true,
            'label_macro'       => [
                '%s',
                'body',
            ],
            'children'   => [
                'id'   => new \Fieldmanager_Hidden(),
                'type' => new \Fieldmanager_Radios( 'Message type', [
                    'validation_rules'    => 'required',
                    'validation_messages' => 'Required',
                    'default_value'       => 'standard',
                    'options'             => [
                        'standard'    => 'Text Paragraph',
                        'with_title'  => 'With Title',
                        'with_button' => 'With Button',
                    ],
                ] ),
                'preview' => new \Fieldmanager_TextField( 'Preview', [
                    'description'               => 'How the message will look to readers',
                    'description_after_element' => false,
                    'template'                  => get_template_directory() . '/templates/raw/message-spot.php',
                ] ),
                'preview_model' => new \Fieldmanager_Hidden( false, [
                    'sanitize' => [ '\Pedestal\Utils\Utils', 'return_same' ],
                ] ),
                'body'  => new \Fieldmanager_RichTextArea( 'Message (under 90 characters)', [
                    'editor_settings' => [
                        'media_buttons' => false,
                    ],
                    'buttons_1' => [ 'bold', 'italic', 'underline' ],
                    'buttons_2' => [],
                ] ),
                'url' => new \Fieldmanager_Link( 'Destination URL', [
                    'description'         => 'All messages are linked to a destination page',
                    'validation_rules'    => [
                        'required' => true,
                        'url'      => true,
                    ],
                    'validation_messages' => [
                        'required' => 'Required',
                        'url'      => 'This is not a URL!',
                    ],
                    'attributes' => [
                        'placeholder' => 'https://',
                        'size'        => 50,
                    ],
                ] ),
                'title' => new \Fieldmanager_TextField( 'Message Title', [
                    'validation_rules'    => 'required',
                    'validation_messages' => 'Required',
                    'display_if' => [
                        'src'   => 'type',
                        'value' => 'with_title',
                    ],
                    'attributes' => [
                        'placeholder' => '…',
                        'size'        => 50,
                    ],
                ] ),
                'button_label' => new \Fieldmanager_TextField( 'Button Label', [
                    'validation_rules'    => 'required',
                    'validation_messages' => 'Required',
                    'display_if' => [
                        'src'   => 'type',
                        'value' => 'with_button',
                    ],
                    'attributes' => [
                        'placeholder' => '…',
                        'size'        => 50,
                    ],
                ] ),
                'icon' => new \Fieldmanager_Radios( 'Icon', [
                    'default_value'       => 'link',
                    'validation_rules'    => 'required',
                    'validation_messages' => 'Required',
                    'options'             => $this->icon_buttons,
                    'display_if' => [
                        'src'   => 'type',
                        'value' => 'standard,with_title',
                    ],
                ] ),
            ],
        ] );

        $message_spot = new \Fieldmanager_Group( false, [
            'name'     => 'pedestal_message_spot',
            'children' => [
                'override' => $override_group,
                'messages' => $messages_group,
            ],
        ] );
        $message_spot->add_submenu_page( 'themes.php', 'Message Spot', 'Message Spot', 'manage_message_spot' );
    }

    /**
     * Filter the message spot option data before saving to the database
     *
     * @param array $new_value
     * @param array $old_value
     */
    public function filter_pre_update_option( $new_value, $old_value ) {
        $defaults = $this->get_model_defaults();
        if ( 'false' === $new_value['override']['enabled'] ) {
            $new_value['override'] = [
                'enabled'       => 'false',
                'preview'       => '',
                'preview_model' => '',
                'title'         => 'Breaking News',
                'post'          => 0,
                'body'          => '',
                'url'           => '',
            ] + $new_value['override'];
        }
        $new_value['override_previous_settings'] = $old_value['override'];
        return $new_value;
    }

    /**
     * Retrieve data about the post selected in the override section
     */
    public function action_wp_ajax_override() {
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( null, 500 );
            die();
        }

        $post_id = absint( $_POST['post_id'] );
        $ped_post = Post::get( $post_id );
        if ( ! Types::is_post( $ped_post ) ) {
            wp_send_json_error( null, 500 );
            die();
        }

        $value = [
            'title' => $ped_post->get_title(),
            'url' => $ped_post->get_permalink(),
        ];
        wp_send_json_success( $value );
        die();
    }

    /**
     * Add message spot data to Timber context
     *
     * @param array $context
     * @return array
     */
    public function filter_timber_context( $context ) {
        $context['message_spot'] = [];
        if ( ! is_page() ) {
            $message = [];
            $data = get_option( 'pedestal_message_spot' );
            if ( isset( $data['override'] ) && isset( $data['messages'] ) ) {
                if ( isset( $data['messages'][0] ) ) {
                    $message = $data['messages'][0];
                }
                if ( isset( $data['override']['enabled'] ) && 'true' === $data['override']['enabled'] ) {
                    $message = $data['override'];
                }
            }
            $context['message_spot'] = $this->prepare_timber_context( $message );
        }
        return $context;
    }

    /**
     * Prepare message spot data for Timber context
     *
     * @param array $message
     * @return array
     */
    public static function prepare_timber_context( $message ) {
        if ( ! $message || ! is_array( $message ) ) {
            return [];
        }
        $type = str_replace( '_', '-', $message['type'] );
        $message['additional_classes'] = "message-spot--{$type} js-message-spot-{$type}";
        $message['ga_label'] = 'unidentified';
        switch ( $message['type'] ) {
            case 'standard':
                $message['title'] = false;
                $message['button_label'] = false;
                break;
            case 'with_title':
                $message['button_label'] = false;
                break;
            case 'with_button':
                $message['icon'] = false;
                $message['title'] = false;
                break;
            case 'override':
                $message['additional_classes'] .= ' message-spot--with-title js-message-spot-with-title';
                $message['icon'] = 'bolt-solid';
                $message['button_label'] = false;
                $message['ga_label'] = 'override';
                if ( empty( $message['post'] ) ) {
                    return [];
                }
                $post = Post::get( $message['post'] );
                if ( ! Types::is_post( $post ) ) {
                    return [];
                }
                $message['url'] = $post->get_permalink();
                $message['body'] = $message['body'] ?: $post->get_the_title();
                break;
        }
        return $message;
    }

    /**
     * Get the model defaults
     *
     * @return array
     */
    public static function get_model_defaults() {
        return self::$model_defaults;
    }
}
