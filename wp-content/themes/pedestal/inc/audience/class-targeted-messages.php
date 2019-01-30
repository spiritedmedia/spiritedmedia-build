<?php

namespace Pedestal\Audience;

use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Icons;
use Pedestal\Page_Cache;

abstract class Targeted_Messages {

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
     * Default values for the message preview model
     *
     * Shared between the static PHP preview and the Backbone preview.
     *
     * @var array
     */
    protected static $model_defaults;

    /**
     * Option name to store in the database
     *
     * @var string
     */
    protected static $option_name;

    /**
     * Human-friendly title to display in admin sidebar and settings page
     *
     * @var string
     */
    protected $admin_page_title;

    /**
     * Capability required to manage the settings for these messages
     *
     * @var string
     */
    protected $capability = 'manage_options';

    protected $api_component_name;

    /**
     * Locations where messages can appear
     *
     * If value remains `false`, then we'll assume only a single location exists
     * and adjust the data structure and UI accordingly.
     *
     * @var array|false
     */
    protected $locations = false;

    /**
     * Available target groups
     *
     * @var array name => label
     */
    // phpcs:disable
    protected static $target_groups = [
        'unidentified' => [
            'label'       => 'Unidentified',
            'description' => 'People we know nothing about. They might be a new
                reader, or somebody using the site in an in-app browser.
                <em>Ask them to subscribe</em>.',
        ],
        'frequent-reader' => [
            'label'       => 'Frequent Reader',
            'description' => 'People who have read more than <strong>five</strong>
                articles in the last 30 days, and do not subscribe to our
                daily newsletter. <em>Ask them for money</em>.',
        ],
        'subscriber' => [
            'label'       => 'Subscriber',
            'description' => 'People who get our daily newsletter. <em>Ask them for money</em>.',
        ],
        'donor' => [
            'label'       => 'Donor',
            'description' => 'People who have given us money, but are not
                members. <em>Ask them for more money</em>.',
        ],
        'member' => [
            'label'       => 'Member',
            'description' => 'People who are members. <em>Ask them for more
                money. Or thank them. Or invite them to an event. Have fun.</em>',
        ],
    ];
    // phpcs:enable

    /**
     * Field to base the message field group's label upon in the admin UI
     *
     * The field with the key specified here will be used to populate the
     * message field group label which is visible when the message is collapsed
     * in the UI.
     *
     * Note that if using a field based off TinyMCE, the message label won't be
     * updated dynamically. The settings must be saved and the page reloaded to
     * see the updated label.
     *
     * Equivalent to `Fieldmanager_Group->label_token`.
     *
     * @var string
     */
    protected $message_label_token = 'title';

    /**
     * Standard message child fields
     *
     * @var array The return value of the `message_fields()` method
     */
    protected $message_fields = [];

    /**
     * Override message child fields
     *
     * Will be set to the
     *
     * @var array The return value of the `override_fields()` method, if defined
     */
    protected $override_fields = [];

    /**
     * Raw SVGs of all available icons
     *
     * @var array
     */
    protected $icons_svg = [];

    /**
     * Path to the message preview template
     *
     * Although the markup itself may be simple and easy to store in a string
     * here, Fieldmanager expects a path to an actual file...
     *
     * @var string
     */
    protected $preview_template;

    /**
     * [get_instance()]
     */
    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();

            $instance->preview_template = get_template_directory() . '/templates/message-preview.php';

            $instance->setup_actions();
            $instance->setup_filters();

            $instance->message_fields = $instance->message_fields();
            if ( method_exists( $instance, 'override_fields' ) ) {
                $instance->override_fields = $instance->override_fields();
            }

            $instance->icons_svg = Icons::get_all_icons_svg();
        }
        return $instance;
    }

    /**
     * Set up actions
     */
    protected function setup_actions() {
        $option_name = static::$option_name;
        $after_post_types_registered = 11;
        add_action( 'init', [ $this, 'action_init_setup_fields' ], $after_post_types_registered );
        add_action(
            "admin_print_scripts-appearance_page_{$option_name}",
            [ $this, 'action_admin_print_scripts' ]
        );
        add_action( "update_option_{$option_name}", function() {
            Page_Cache::purge_all();
        } );
        add_action( 'rest_api_init', function() {
            register_rest_route( PEDESTAL_API_NAMESPACE, "/{$this->api_component_name}/render", [
                'methods'  => 'GET',
                'callback' => [ $this, 'handle_render_endpoint' ],
            ] );
        } );
    }

    /**
     * Set up filters
     */
    protected function setup_filters() {
        $option_name = static::$option_name;
        add_filter( "pre_update_option_{$option_name}", [ $this, 'filter_pre_update_option' ], 10, 2 );
    }

    /**
     * Load the scripts
     */
    abstract public function action_admin_print_scripts();

    /**
     * Get the standard message child fields for Fieldmanager
     *
     * @return array [field key] => [Fieldmanager_Field]
     */
    abstract protected function message_fields();

    /**
     * Render a single message
     *
     * @param  array  $args Arguments to modify how the message is rendered
     * @return string       Rendered message
     */
    abstract public static function render();

    /**
     * Register the admin UI
     */
    public function action_init_setup_fields() {

        $location_field_groups = [];
        if ( empty( $this->locations ) ) {
            $this->locations = [ 'default' => false ];  // phpcs:ignore
        }
        foreach ( $this->locations as $location_key => $location_label ) {
            // A `$location_label` value of `false` will render the fields
            // without a metabox, which is desirable if we only have one
            // location in use
            $location_field_groups[ $location_key ] = new \Fieldmanager_Group( $location_label, [
                'children' => [
                    'messages' => new \Fieldmanager_Group( 'Message', [
                        'label_macro' => [
                            '%s',
                            $this->message_label_token,
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
                        'children'          => $this->message_fields,
                    ] ),
                ],
            ] );
        }

        $target_group_field_groups = [];
        foreach ( static::get_target_groups() as $target_group_key => $target_group_data ) {
            $target_group_field_groups[ $target_group_key ] = new \Fieldmanager_Group( $target_group_data['label'], [
                'description'               => $target_group_data['description'],
                'description_after_element' => false,
                'escape'                    => [ 'description' => 'wp_kses_post' ], // phpcs:ignore
                'children'                  => $location_field_groups,
            ] );
        }

        // The override fields must be first in the layout, so we set its
        // position ahead of time and determine its actual value later
        $targeted_messages_children = [
            'override_message' => false,
            'standard_messages' => new \Fieldmanager_Group( false, [
                'children'           => $target_group_field_groups,
                'tabbed'             => 'horizontal',
                'persist_active_tab' => true,
            ] ),
        ];

        if ( $this->override_fields ) {
            $targeted_messages_children['override_message'] = new \Fieldmanager_Group( 'Override Message', [
                'collapsible' => true,
                'children'    => $this->override_fields,
            ] );
        }

        // Some sort of variation of `Fieldmanager_Field` must be used here --
        // we can't just use `false` or an empty array -- so we just make it a
        // hidden field. Alternatively, we could check to see if the `override`
        // key exists when processing the data, but making sure `override` is
        // always set seems simpler
        if ( empty( $targeted_messages_children['override_message'] ) ) {
            $targeted_messages_children['override_message'] = new \Fieldmanager_Hidden;
        }

        $targeted_messages_option = new \Fieldmanager_Group( false, [
            'name'     => static::$option_name,
            'children' => $targeted_messages_children,
        ] );

        $targeted_messages_option->add_submenu_page(
            'themes.php',
            $this->admin_page_title,
            $this->admin_page_title,
            $this->capability
        );
    }

    /**
     * Filter the message spot option data before saving to the database
     *
     * @param array $new_value
     * @param array $old_value
     */
    public function filter_pre_update_option( $new_value, $old_value ) {
        $new_override_data = $new_value['override_message'] ?? null;
        if ( $new_override_data ) {
            // Clear out the override data if turning off the override
            if ( 'false' === $new_override_data['enabled'] ) {
                $new_value['override_message'] = static::$model_defaults['override'];
            }
        }
        return $new_value;
    }

    /**
     * Handle the `/render` API endpoint
     *
     * Simple wrapper for `render()`.
     *
     * @param \WP_REST_Request $request
     * @return string
     */
    public static function handle_render_endpoint( \WP_REST_Request $request ) {
        $context = $request->get_params();
        return static::render( $context );
    }

    /**
     * Get the model defaults
     *
     * @return array
     */
    public static function get_model_defaults() {
        return static::$model_defaults;
    }

    /**
     * Get the target groups
     *
     * @return array Group key => group data (label, description)
     */
    public static function get_target_groups() {
        return static::$target_groups;
    }

    /**
     * Get the data for a single message by the message's ID
     *
     * To get an override message, pass a `$query_id` of `override`.
     *
     * If a `$query_id` is not supplied, then try to use the `component-id`
     * global query var before bailing.
     *
     * @param string $query_id Message ID
     * @param bool   $fallback_to_defaults [true] Fall back to the default values?
     * @return array Data for a single message, or empty array if no results
     */
    public static function get_message_data_by_id( $query_id, $fallback_to_defaults = true ) {
        if ( ! $query_id || ! is_string( $query_id ) ) {
            return [];
        }

        $raw_data = get_option( static::$option_name ) ?: [];

        // The override doesn't override anything here, it's just structured
        // more simply so we handle it differently
        $override_message = $raw_data['override_message'] ?? [];
        if ( $override_message && 'true' === $override_message['enabled'] ) {
            if ( $override_message['id'] === $query_id ) {
                $override_message['target_audience'] = 'override';
                return $override_message;
            }
        }

        $standard_messages = $raw_data['standard_messages'] ?? [];
        foreach ( $standard_messages as $audience_key => $locations_by_audience ) {
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

        if ( ! $fallback_to_defaults ) {
            return [];
        }

        $defaults = static::get_model_defaults();
        if ( $query_id === 'override' ) {
            return $defaults['override'];
        } else {
            return $defaults['standard'];
        }
    }

    /**
     * For each target audience group, get the data for the first message in the
     * specified location
     *
     * If the override is active, it will be used instead of any other messages,
     * and the returned array will only contain data for the single override
     * message.
     *
     * E.G. If there are five target audience groups then the returned array
     * will include a maximum of five items.
     *
     * @param string $query_location ['default']
     * @return array Data for multiple messages, or empty array if no results
     */
    public static function get_message_data( $query_location = 'default' ) {
        if ( ! $query_location || ! is_string( $query_location ) ) {
            return [];
        }

        $return_message_data = [];
        $raw_data = get_option( static::$option_name ) ?: [];

        // Return the override message if active, regardless of queried location
        if (
            ! empty( $raw_data['override_message']['enabled'] )
            && 'true' === $raw_data['override_message']['enabled']
        ) {
            $return_message_data['override_message'] = $raw_data['override_message'];
            return $return_message_data;
        }

        if ( empty( $raw_data['standard_messages'] ) ) {
            return [];
        }

        // For the queried location, prepare an array of messages consisting of
        // one message per target audience group, indexed by the message ID
        foreach ( $raw_data['standard_messages'] as $audience_key => $locations_by_audience ) {
            foreach ( $locations_by_audience as $location_key => $messages_by_location ) {
                if ( $location_key !== $query_location ) {
                    continue;
                }

                $messages = $messages_by_location['messages'] ?? false;
                if ( ! $messages || ! is_array( $messages ) ) {
                    continue;
                }

                // Just work with the first message in the array so we only
                // return one message per target audience group
                $message = reset( $messages );
                $message['target_audience'] = $audience_key;
                $return_message_data[ $message['id'] ] = $message;
            }
        }

        return $return_message_data;
    }

    /**
     * Get all of the rendered messages for a given location
     *
     * @param string $location Location name
     * @param array $args
     * @return string Rendered messages
     */
    public static function get_rendered_messages( $location = 'default', $args = [] ) {
        $output = '';
        $messages = static::get_message_data( $location );
        foreach ( $messages as $message_data ) {
            $message_data = wp_parse_args( $args, $message_data );
            $output .= static::render( $message_data );
        }
        return $output;
    }
}
