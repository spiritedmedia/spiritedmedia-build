<?php

namespace Pedestal\Audience;

use Timber\Timber;
use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Message_Spot extends Targeted_Messages {

    protected static $model_defaults = [
        'standard' => [
            'type'         => 'standard',
            'url'          => '#',
            'icon'         => 'link',
            'title'        => 'Default Title',
            'body'         => 'This is the default message body',
            'button_label' => 'Default Label',
        ],
        'override' => [
            'enabled' => 'false',
            'type'    => 'override',
            'url'     => '#',
            'icon'    => 'bolt-solid',
            'title'   => 'Breaking News',
            'body'    => 'This is the default message body',
        ],
    ];

    protected static $option_name = 'pedestal_message_spot';

    protected $admin_page_title = 'Message Spot';

    protected $capability = 'manage_message_spot';

    protected $api_component_name = 'message-spot';

    protected $message_label_token = 'body';

    protected function setup_actions() {
        parent::setup_actions();
        add_action( 'wp_ajax_pedestal-message-spot-override', [ $this, 'action_wp_ajax_override' ] );
    }

    protected function setup_filters() {
        parent::setup_filters();
        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
    }

    public function action_admin_print_scripts() {
        wp_enqueue_script(
            'pedestal-message-spot',
            PEDESTAL_DIST_DIRECTORY_URI . '/js/message-spot-admin.js',
            [ 'jquery', 'backbone', 'wp-api' ],
            PEDESTAL_VERSION,
            true
        );

        $preview_url = home_url() . '/api/component-preview/message-spot/';
        wp_localize_script( 'pedestal-message-spot', 'pedestalPreviewURL', $preview_url );
        wp_localize_script( 'pedestal-message-spot', 'messagePreviewDefaults', static::$model_defaults );
        wp_localize_script( 'pedestal-message-spot', 'PedestalIcons', $this->icons_svg );
    }

    /**
     * Get the override message child fields for Fieldmanager
     *
     * @return array [field key] => [Fieldmanager_Field]
     */
    protected function override_fields() {
        return [
            'enabled'       => new \Fieldmanager_Radios( false, [
                'description'   => 'The override is shown to <strong>every reader on every page</strong> and suppresses other messages specified below. Use sparingly.',
                'escape'        => [
                    'description' => 'wp_kses_post',
                ],
                'default_value' => 'false',
                'options'       => [
                    'false' => 'Override Off',
                    'true'  => 'Override On',
                ],
            ] ),
            'id'            => new \Fieldmanager_Hidden( false, [
                'default_value' => 'override',
            ] ),
            'type'          => new \Fieldmanager_Hidden( [
                'default_value' => 'override',
            ] ),
            'preview'       => new \Fieldmanager_TextField( 'Preview', [
                'template' => $this->preview_template,
            ] ),
            'preview_model' => new \Fieldmanager_Hidden( false, [
                'sanitize' => [ '\Pedestal\Utils\Utils', 'return_same' ],
            ] ),
            'title'         => new \Fieldmanager_TextField( 'Message Title', [
                'default_value' => 'Breaking News',
                'description'   => '“Breaking News”, “Developing Story”, etc.',
            ] ),
            'post'          => new \Fieldmanager_Autocomplete( 'Article', [
                'description' => 'Start typing to retrieve a post',
                'attributes'  => [
                    'placeholder' => '…',
                    'size'        => 75,
                ],
                'datasource'  => new \Fieldmanager_Datasource_Post( [
                    'query_args' => [
                        'post_type'      => Types::get_post_types(),
                        'posts_per_page' => 15,
                        'post_status'    => [ 'publish' ],
                    ],
                ] ),
            ] ),
            'post_title'    => new \Fieldmanager_Hidden(),
            'body'          => new \Fieldmanager_Textfield( 'Headline', [
                'description' => 'Adjust headline for this message',
                'attributes'  => [
                    'size' => 75,
                ],
            ] ),
            'url'           => new \Fieldmanager_Hidden(),
            'icon'          => new \Fieldmanager_Hidden( [
                'default_value' => 'bolt-solid',
            ] ),
        ];
    }

    protected function message_fields() {
        return [
            'id'            => new \Fieldmanager_Hidden(),
            'type'          => new \Fieldmanager_Radios( 'Message type', [
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'default_value'       => 'standard',
                'options'             => [
                    'standard'    => 'Text Paragraph',
                    'with_title'  => 'With Title',
                    'with_button' => 'With Button',
                ],
            ] ),
            'preview'       => new \Fieldmanager_TextField( 'Preview', [
                'description'               => 'How the message will look to readers',
                'description_after_element' => false,
                'template'                  => $this->preview_template,
            ] ),
            'preview_model' => new \Fieldmanager_Hidden( false, [
                'sanitize' => [ '\Pedestal\Utils\Utils', 'return_same' ],
            ] ),
            'body'          => new \Fieldmanager_RichTextArea( 'Message (under 90 characters)', [
                'editor_settings' => [
                    'media_buttons' => false,
                ],
                'buttons_1'       => [ 'bold', 'italic', 'underline' ],
                'buttons_2'       => [],
            ] ),
            'url'           => new \Fieldmanager_Link( 'Destination URL', [
                'description'         => 'All messages are linked to a destination page',
                'validation_rules'    => [
                    'required' => true,
                    'url'      => true,
                ],
                'validation_messages' => [
                    'required' => 'Required',
                    'url'      => 'This is not a URL!',
                ],
                'attributes'          => [
                    'placeholder' => 'https://',
                    'size'        => 50,
                ],
            ] ),
            'title'         => new \Fieldmanager_TextField( 'Message Title', [
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'display_if'          => [
                    'src'   => 'type',
                    'value' => 'with_title',
                ],
                'attributes'          => [
                    'placeholder' => '…',
                    'size'        => 50,
                ],
            ] ),
            'button_label'  => new \Fieldmanager_TextField( 'Button Label', [
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'display_if'          => [
                    'src'   => 'type',
                    'value' => 'with_button',
                ],
                'attributes'          => [
                    'placeholder' => '…',
                    'size'        => 50,
                ],
            ] ),
            'icon'          => new \Fieldmanager_Radios( 'Icon', [
                'default_value'       => 'link',
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'options'             => $this->icon_buttons,
                'display_if'          => [
                    'src'   => 'type',
                    'value' => 'standard,with_title',
                ],
            ] ),
        ];
    }

    /**
     * Retrieve data about the post selected in the override section
     */
    public function action_wp_ajax_override() {
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( null, 500 );
            die();
        }

        $post_id  = absint( $_POST['post_id'] );
        $ped_post = Post::get( $post_id );
        if ( ! Types::is_post( $ped_post ) ) {
            wp_send_json_error( null, 500 );
            die();
        }

        $value = [
            'title' => $ped_post->get_title(),
            'url'   => $ped_post->get_permalink(),
        ];
        wp_send_json_success( $value );
        die();
    }

    /**
     * Add message spot data to global Timber context
     *
     * @param array $context
     * @return array
     */
    public function filter_timber_context( $context ) {
        $context['message_spot'] = '';
        if ( ! is_page() ) {
            $context['message_spot'] = static::get_rendered_messages();
        }
        return $context;
    }

    public static function render( $args = [] ) {
        if ( ! $args || ! is_array( $args ) ) {
            return [];
        }

        $context = $args;

        $type                          = str_replace( '_', '-', $context['type'] );
        $target_audience               = 'unidentified';
        $context['additional_classes'] = "message-spot--{$type} js-message-spot-{$type}";
        if ( ! empty( $context['target_audience'] ) ) {
            $target_audience                = sanitize_title( $context['target_audience'] );
            $context['additional_classes'] .= ' show-for-target-audience--' . $target_audience;
        }

        $context['ga_label'] = $target_audience;

        switch ( $context['type'] ) {
            case 'standard':
                $context['title']        = false;
                $context['button_label'] = false;
                break;
            case 'with_title':
                $context['button_label'] = false;
                break;
            case 'with_button':
                $context['icon']  = false;
                $context['title'] = false;
                break;
            case 'override':
                $context['additional_classes'] .= ' message-spot--with-title';
                $context['icon']                = 'bolt-solid';
                $context['button_label']        = false;
                $context['ga_label']            = 'override';
                if ( ! empty( $context['post'] ) ) {
                    $post = Post::get( $context['post'] );
                    if ( Types::is_post( $post ) ) {
                        $context['url']  = $post->get_permalink();
                        $context['body'] = $context['body'] ?: $post->get_the_title();
                    }
                }
                break;
        }

        return Timber::fetch( 'partials/header/message-spot.twig', $context );
    }
}
