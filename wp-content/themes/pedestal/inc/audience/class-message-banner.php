<?php

namespace Pedestal\Audience;

use Timber\Timber;
use Pedestal\Page_Cache;

class Message_Banner {

    public $option_name = 'pedestal_message_banner';

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into WordPress via actions
     */
    public function setup_actions() {
        $after_post_types_registered = 11;
        add_action( 'init', [ $this, 'action_init_setup_fields' ], $after_post_types_registered );
        add_action( "update_option_{$this->option_name}", function() {
            Page_Cache::purge_all();
        } );
    }

    /**
     * Hook into WordPress via filters
     */
    public function setup_filters() {
        add_filter( 'timber_context', [ $this, 'filter_timber_context' ], 11 );
    }

    /**
     * Setup sub-menu and fields for the data
     */
    public function action_init_setup_fields() {
        $targeted_messages_option = new \Fieldmanager_Group( false, [
            'name'     => $this->option_name,
            'children' => [
                'enabled'     => new \Fieldmanager_Checkbox( 'Enable message banner.', [
                    'description' => 'When enabled, the message banner will replace the normal message spot, for all target groups.',
                ] ),
                'url'         => new \Fieldmanager_Link( 'URL', [
                    'validation_rules'    => [
                        'url' => true,
                    ],
                    'validation_messages' => [
                        'url' => 'This is not a URL!',
                    ],
                    'attributes'          => [
                        'placeholder' => 'https://',
                        'size'        => 50,
                    ],
                ] ),
                'title'       => new \Fieldmanager_TextField( 'Title' ),
                'button_text' => new \Fieldmanager_TextField( 'Button Text' ),
                'body'        => new \Fieldmanager_RichTextArea( 'Body', [
                    'editor_settings' => [
                        'editor_height' => 225,
                        'media_buttons' => false,
                    ],
                    'buttons_1'       => [ 'bold', 'italic', 'underline' ],
                    'buttons_2'       => [],
                ] ),
            ],
        ] );

        $targeted_messages_option->add_submenu_page(
            'themes.php',
            'Message Banner',
            'Message Banner',
            'manage_message_spot'
        );
    }

    /**
     * Override the message_spot if the message_banner has data
     *
     * @param array $context
     * @return array
     */
    public function filter_timber_context( $context ) {
        if ( is_page() ) {
            return $context;
        }
        $data = $this->get_data();
        if ( $data['enabled'] ) {
            $context['message_spot'] = $this->render();
        }
        return $context;
    }

    /**
     * Get the saved data from the option
     *
     * @return array The saved data
     */
    public function get_data() {
        $data = get_option( $this->option_name );
        if ( ! $data ) {
            return [];
        }
        $output_data = [
            'enabled'     => $data['enabled'] ?? false,
            'url'         => $data['url'] ?? '',
            'title'       => $data['title'] ?? '',
            'button_text' => $data['button_text'] ?? '',
            'body'        => $data['body'] ?? '',
        ];
        // See http://fieldmanager.org/docs/fields/checkbox/
        if ( '1' === $output_data['enabled'] ) {
            $output_data['enabled'] = true;
        } else {
            $output_data['enabled'] = false;
        }
        return $output_data;
    }

    /**
     * Get a rendered message banner
     *
     * @return string Rendered HTML of the message banner or empty string
     */
    public function render() {
        $data = $this->get_data();
        if ( ! $data['url'] ) {
            return '';
        }
        $context = [
            'url'         => $data['url'],
            'title'       => apply_filters( 'the_title', $data['title'] ),
            'button_text' => apply_filters( 'the_title', $data['button_text'] ),
            'body'        => apply_filters( 'the_content', $data['body'] ),
        ];
        return Timber::fetch( 'partials/header/message-banner.twig', $context );
    }
}
