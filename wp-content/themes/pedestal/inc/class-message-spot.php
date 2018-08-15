<?php

namespace Pedestal;

use Pedestal\Utils\Utils;

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
        'calendar-alt-regular' => 'Calendar (Outline)',
        'envelope-o'           => 'Envelope (Outline)',
        'heart-regular'        => 'Heart (Outline)',
        'location-arrow'       => 'Location arrow',
        'comments'             => 'Comment bubbles',
        'coffee'               => 'Coffee cup',
        'link'                 => 'Link',
    ];

    /**
     * Default values for the message model
     *
     * Shared between the static PHP preview and the Backbone preview.
     *
     * @var array
     */
    private static $model_defaults = [
        'type'         => 'standard',
        'url'          => '#',
        'icon'         => 'link',
        'title'        => 'Default Title',
        'body'         => 'This is the default message body',
        'button_label' => 'Default Button Label',
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
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
        add_action(
            'admin_print_scripts-appearance_page_pedestal_message_spot',
            [ $this, 'action_admin_print_scripts' ]
        );
    }

    /**
     * Set up filters
     */
    protected function setup_filters() {
        add_filter( 'query_vars', function( $query_vars ) {
            $query_vars[] = 'pedestal-api';
            $query_vars[] = 'component-name';
            $query_vars[] = 'component-id';
            return $query_vars;
        });
        add_filter( 'template_include', [ $this, 'filter_template_include' ] );
        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
        add_filter( 'fm_element_markup_start', [ $this, 'filter_fm_element_markup_start' ], 10, 2 );
    }

    /**
     * Load the scripts
     */
    public function action_admin_print_scripts() {
        wp_enqueue_script(
            'pedestal-message-spot',
            get_template_directory_uri() . '/assets/dist/js/message-spot.js',
            [ 'jquery', 'backbone', 'twig' ],
            PEDESTAL_VERSION,
            true
        );

        $icons = Icons::get_all_icons_svg();
        wp_localize_script( 'pedestal-message-spot', 'PedestalIcons', $icons );
        $preview_url = home_url() . '/api/component-preview/message-spot/';
        wp_localize_script( 'pedestal-message-spot', 'pedestalPreviewURL', $preview_url );
        wp_localize_script( 'pedestal-message-spot', 'messagePreviewModelDefaults', self::$model_defaults );
    }

    /**
     * Set up rewrites for the component preview
     */
    public function action_init_register_rewrites() {
        add_rewrite_rule(
            'api/component-preview/([^/]+)/([^/]+)/?$',
            'index.php?pedestal-api=component-preview&component-name=$matches[1]&component-id=$matches[2]',
            'top'
        );
    }

    /**
     * Register the admin UI
     */
    public function action_init_after_post_types_registered() {
        $message_spot = new \Fieldmanager_Group( 'Message', [
            'name'              => 'pedestal_message_spot',
            'field_class'       => 'message fm-group',
            'add_more_label'    => 'Add New Message',
            'add_more_position' => 'top',
            'sortable'          => true,
            'limit'             => 0,
            'save_empty'        => false,
            'extra_elements'    => 0,
            'collapsible'       => true,
            'label_macro'    => [
                '%s',
                'body',
            ],
            'children'   => [
                'id'   => new \Fieldmanager_Hidden(),
                'type' => new \Fieldmanager_Radios( 'Message type', [
                    'validation_rules'    => 'required',
                    'validation_messages' => 'Required',
                    'default_value'       => 'standard',
                    'options'           => [
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
                'preview_model' => new \Fieldmanager_Hidden(),
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
        $message_spot->add_submenu_page( 'themes.php', 'Message Spot', 'Message Spot', 'manage_spotlight' );
    }

    /**
     * Load the component preview template
     *
     * @param string $template
     * @return string Template path (maybe modified)
     */
    public function filter_template_include( $template ) {
        if ( 'component-preview' == get_query_var( 'pedestal-api' ) ) {
            $template_path = sprintf(
                'component-previews/%s.php',
                get_query_var( 'component-name' )
            );
            $new_template = locate_template( $template_path );
            if ( ! empty( $new_template ) ) {
                return $new_template;
            };
        }
        return $template;
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
            $data = get_option( 'pedestal_message_spot' );
            $context['message_spot'] = $this->prepare_timber_context( $data[0] );
        }
        return $context;
    }

    /**
     * Add some helpful description text above the repeating message groups
     */
    public function filter_fm_element_markup_start( $out, $fm ) {
        $screen = get_current_screen();
        if ( 'appearance_page_pedestal_message_spot' !== $screen->base ) {
            return $out;
        }

        $fm_tree = $fm->get_form_tree();
        $parent = array_pop( $fm_tree );
        if (
            empty( $parent->name )
            || 'pedestal_message_spot' != $parent->name
        ) {
            return $out;
        }

        echo '<div class="js-message-spot-prompt">';
            echo '<p class="message-spot-prompt__no-messages">No message specified, so nothing will appear. Add a new message!</p>';
            echo '<p class="message-spot-prompt__has-messages">The first message in the list will appear on the frontend. Drag the messages around to display a different one.</p>';
        echo '</div>';

        return $out;
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
