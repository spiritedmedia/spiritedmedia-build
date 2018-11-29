<?php

namespace Pedestal;

use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Conversion_Prompt_Admin {

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
        'title'       => 'Default Title',
        'icon_name'   => 'link',
        'body'        => 'This is the default message body',
        'type'        => 'with_button',
        'button_text' => 'Default Label',
        'button_url'  => '#',
        'style'       => 'subtle',
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
            'admin_print_scripts-appearance_page_pedestal_conversion_prompts',
            [ $this, 'action_admin_print_scripts' ]
        );
        add_action( 'update_option_pedestal_conversion_prompts', function() {
            do_action( 'rt_nginx_helper_purge_all' );
        } );
    }

    /**
     * Set up filters
     */
    protected function setup_filters() {
        add_filter( 'fm_element_markup_start', [ $this, 'filter_fm_element_markup_start' ], 10, 2 );
    }

    /**
     * Load the scripts
     */
    public function action_admin_print_scripts() {
        wp_enqueue_script(
            'pedestal-conversion-prompts',
            PEDESTAL_DIST_DIRECTORY_URI . '/js/conversion-prompt-admin.js',
            [ 'jquery', 'backbone', 'wp-api' ],
            PEDESTAL_VERSION,
            true
        );

        $preview_url = home_url() . '/api/component-preview/conversion-prompt/';
        wp_localize_script( 'pedestal-conversion-prompts', 'pedestalPreviewURL', $preview_url );
        wp_localize_script( 'pedestal-conversion-prompts', 'messagePreviewDefaults', self::$model_defaults );
        wp_localize_script( 'pedestal-conversion-prompts', 'PedestalIcons', Icons::get_all_icons_svg() );
    }

    /**
     * Register the admin UI
     */
    public function action_init_after_post_types_registered() {
        $locations = [
            'stream' => 'Homepage and other streams',
            'entity' => 'Article pages, after the end of an article',
        ];

        $target_groups = [
            'unidentified' => 'Unidentified',
            'subscriber'   => 'Subscriber',
            'donor'        => 'Donor',
            'member'       => 'Member',
        ];

        $message_fields = [
            'id'   => new \Fieldmanager_Hidden(),
            'title' => new \Fieldmanager_TextField( 'Message Title', [
                'description'         => 'Use to draw attention (succinctly); use Message to describe',
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'attributes'          => [
                    'placeholder' => 'A compelling title to draw the reader\'s attention',
                    'size'        => 50,
                ],
            ] ),
            'icon_name' => new \Fieldmanager_Radios( 'Icon', [
                'default_value' => 'link',
                'options'       => $this->icon_buttons,
            ] ),
            'body'  => new \Fieldmanager_RichTextArea( 'Message', [
                'editor_settings' => [
                    'media_buttons' => false,
                ],
                'buttons_1' => [ 'bold', 'italic', 'underline', 'link', 'unlink' ],
                'buttons_2' => [],
            ] ),
            'type' => new \Fieldmanager_Radios( 'Include newsletter email signup fields', [
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'default_value'       => 'with_button',
                'options'             => [
                    'with_button'      => 'With call-to-action button',
                    'with_signup_form' => 'With email signup form',
                ],
            ] ),
            'button_text'         => new \Fieldmanager_TextField( 'Button Label', [
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
            'button_url' => new \Fieldmanager_Link( 'Destination URL', [
                'display_if' => [
                    'src'   => 'type',
                    'value' => 'with_button',
                ],
                'validation_rules' => [
                    'required' => true,
                    'url'      => true,
                ],
                'validation_messages' => [
                    'required' => true,
                    'url'      => 'This is not a URL!',
                ],
                'attributes' => [
                    'placeholder' => 'https://',
                    'size'        => 50,
                ],
            ] ),
            'preview' => new \Fieldmanager_TextField( 'Preview', [
                'description'               => 'How the message will look to readers',
                'description_after_element' => false,
                'template'                  => get_template_directory() . '/templates/raw/conversion-prompt.php',
            ] ),
            'preview_model' => new \Fieldmanager_Hidden( false, [
                'sanitize' => [ '\Pedestal\Utils\Utils', 'return_same' ],
            ] ),
            'style' => new \Fieldmanager_Radios( 'Presentation style', [
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'default_value'       => 'subtle',
                'options'             => [
                    'subtle'   => 'Subtle',
                    'emphatic' => 'Emphatic',
                    'screamer' => 'Screamer',
                ],
            ] ),
        ];

        $location_field_groups = [];
        foreach ( $locations as $location_key => $location_label ) {
            $location_field_groups[ $location_key ] = new \Fieldmanager_Group( $location_label, [
                'children' => [
                    'messages' => new \Fieldmanager_Group( 'Message', [
                        'label_macro' => [
                            '%s',
                            'title',
                        ],
                        'field_class'       => 'message fm-group',
                        'add_more_label'    => 'Add New Message',
                        'add_more_position' => 'top',
                        'limit'             => 0,
                        'save_empty'        => false,
                        'extra_elements'    => 0,
                        'collapsible'       => true,
                        'collapsed'         => true,
                        'sortable'          => true,
                        'children'          => $message_fields,
                    ] ),
                ],
            ] );
        }

        $conversion_prompt_children = [];
        foreach ( $target_groups as $target_group_key => $target_group_label ) {
            $conversion_prompt_children[ $target_group_key ] = new \Fieldmanager_Group( $target_group_label, [
                'children' => $location_field_groups,
            ] );
        }

        $conversion_prompts = new \Fieldmanager_Group( false, [
            'name'               => 'pedestal_conversion_prompts',
            'children'           => $conversion_prompt_children,
            'tabbed'             => 'horizontal',
            'persist_active_tab' => true,
        ] );

        $conversion_prompts->add_submenu_page( 'themes.php', 'Conversion Prompts', 'Conversion Prompts', 'manage_conversion_prompts' );
    }

    /**
     * Add a message to the beginning of the conversion prompt UI layout
     *
     * @param string              $out  Field markup
     * @param \Fieldmanager_Field $fm   Field instance
     * @return string
     */
    public function filter_fm_element_markup_start( $out, $fm ) {
        $screen = get_current_screen();
        if ( 'appearance_page_pedestal_conversion_prompts' !== $screen->base ) {
            return $out;
        }
         // If the name attribute of the parent group isn't right then bail
        $fm_tree = $fm->get_form_tree();
        $parent = array_pop( $fm_tree );
        if (
            empty( $parent->name )
            || 'pedestal_conversion_prompts' != $parent->name
        ) {
            return $out;
        }

        echo '<p class="conversion-prompt-admin-explainer">You can target the same location more than once. Drag-and-drop individual messages to override.</p>';

        return $out;
    }

    /**
     * Get the model defaults
     *
     * @return array
     */
    public static function get_model_defaults() {
        return self::$model_defaults;
    }

    /**
     * Get the data for a single prompt by the prompt's ID
     *
     * @param string $query_id
     * @return array Data for a single prompt, or empty array if no results
     */
    public static function get_prompt_data_by_id( $query_id ) {
        if ( ! $query_id || ! is_string( $query_id ) ) {
            return [];
        }
        $raw_data = get_option( 'pedestal_conversion_prompts' ) ?: [];
        foreach ( $raw_data as $audience_key => $locations_by_audience ) {
            foreach ( $locations_by_audience as $messages_by_location ) {
                $messages = $messages_by_location['messages'] ?? false;
                if ( ! $messages ) {
                    continue;
                }
                foreach ( $messages as $message ) {
                    if ( $message['id'] === $query_id ) {
                        $message['target_audience'] = $audience_key;
                        return $message;
                    }
                }
            }
        }
        return [];
    }

    /**
     * For every target audience group, get the data for the first prompt in the
     * specified location
     *
     * @param string $query_location
     * @return array Data for multiple prompts, or empty array if no results
     */
    public static function get_prompt_data_by_location( $query_location ) {
        if ( ! $query_location || ! is_string( $query_location ) ) {
            return [];
        }
        $raw_data = get_option( 'pedestal_conversion_prompts' ) ?: [];
        $prompts = [];
        foreach ( $raw_data as $audience_key => $locations_by_audience ) {
            foreach ( $locations_by_audience as $location_key => $messages_by_location ) {
                if ( $location_key !== $query_location ) {
                    continue;
                }
                $messages = $messages_by_location['messages'] ?? false;
                if ( ! $messages || ! is_array( $messages ) ) {
                    continue;
                }
                $message = reset( $messages );
                $message['target_audience'] = $audience_key;
                $prompts[ $message['id'] ] = $message;
            }
        }
        return $prompts;
    }
}
