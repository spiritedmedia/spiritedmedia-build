<?php

namespace Pedestal\Posts\Slots;

use Timber\Timber;

use \Pedestal\Utils\Utils;

use Pedestal\Posts\Post;

class Slots {

    private static $instance;

    /**
     * Format for comparing days
     *
     * @var string
     */
    private static $day_format = 'Y-m-d';

    private $placement_required_base_post_meta = [
        'type',
        'date',
    ];

    /**
     * Slot Positions
     *
     * @var array
     */
    private static $slot_positions = [
        'newsletter' => [
            'site'  => [ 'single_lead' ],
            'email' => [ 'lead' ],
        ],
        'event' => [
            'site'  => [ 'single_lead', 'shortcode' ],
            'email' => [ 'shortcode' ],
        ],
        'article' => [
            'site'  => [ 'single_lead', 'shortcode' ],
            'email' => [],
        ],
        'embed' => [
            'site'  => [ 'single_lead', 'shortcode' ],
            'email' => [],
        ],
        'factcheck' => [
            'site'  => [ 'single_lead', 'shortcode' ],
            'email' => [],
        ],
    ];

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new Slots;
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Set up Slot actions
     */
    private function setup_actions() {

        // Handle updating of Slot Item Placements
        add_action( 'save_post_pedestal_slot_item', [ $this, 'action_save_post_update_placements' ], 10, 3 );
        // add_action( 'admin_notices', [ $this, 'action_admin_notice_update_placements' ] );

    }

    /**
     * Set up Slot filters
     */
    private function setup_filters() {

        // Set up the `ped_slot()` Twig function
        add_filter( 'get_twig', function( $twig ) {
            $twig->addFunction( new \Twig_SimpleFunction( PEDESTAL_PREFIX . 'slot',
                [ $this, 'handle_twig_func_slot' ],
                [ 'needs_context' => true ]
            ) );
            return $twig;
        }, 99 );

    }

    /**
     * Update the Placement posts upon saving the Slot Item posts
     */
    public function action_save_post_update_placements( $post_id, $post, $update ) {

        if ( isset( $post->post_status ) && ( 'publish' !== $post->post_status ) ) {
            return;
        }

        // Delete all child posts and start fresh
        $slot_item = Slot_Item::get_by_post_id( $post_id );
        foreach ( $slot_item->get_placement_post_ids() as $id ) {
            wp_delete_post( $id, true );
        }

        // Create a single placement post based on the default settings
        $placement_defaults = $_POST['slot_item_placement_defaults'];

        // For some reason FM stores date in an array, and we have to convert
        // its value to a numeric date format
        $placement_defaults['date'] = $this->convert_date_to_day( $placement_defaults['date']['date'] );

        // Set the index to zero for the default post
        $default_placement_data = $placement_defaults + [ 'index' => 0 ];

        // Create the default placement post
        $this->handle_setup_placement_post( $post_id, $default_placement_data );

        // Set up placement rules from the repeating fields
        $placement_rules = $_POST['slot_item_placement_rules'];
        // FM creates an extra key in repeating fields, let's get rid of that
        unset( $placement_rules['proto'] );

        // Loop through the placement rules and create a post for each
        if ( ! empty( $placement_rules ) ) {
            $placement_number = 0;
            foreach ( $placement_rules as $placement_rule ) {
                // Count the field index
                $placement_number++;

                // Store the date in ISO-8601 date format, compensate for
                // FM's weird date array storage
                $placement_rule['date'] = $placement_rule['date']['date'];
                if ( ! empty( $placement_rule['date'] ) ) {
                    $placement_rule['date'] = $this->convert_date_to_day( $placement_rule['date'] );
                }

                // Create the placement rules posts
                $this->handle_setup_placement_rules_posts( $post_id, $placement_defaults, $placement_rule, $placement_number );
            }
        }
    }

    /**
     * Set up Placement posts for a Slot Item's Placement Rules
     *
     * @param  int    $slot_item_id       ID of the parent Slot Item
     * @param  array  $placement_defaults Array of default Slot Item post meta
     * @param  array  $placement_rule     Array of this Placement's specific data
     * @param  mixed  $placement_number   Optional index number to associate with each Placement for tracing back
     *                                    to the repeating field
     */
    private function handle_setup_placement_rules_posts( $slot_item_id, $placement_defaults, $placement_rule, $placement_number = false ) {
        $placement_data = [];

        foreach ( $placement_defaults as $key => $value ) {
            $placement_data[ $key ] = $placement_defaults[ $key ];
            if ( ! empty( $placement_rule[ $key ] ) ) {
                $placement_data[ $key ] = $placement_rule[ $key ];
            }
        }

        // Set up the placement number post meta if provided -- helps with debugging
        if ( false !== $placement_number ) {
            $placement_data['index'] = $placement_number;
        }

        // Create the placement post and add its post meta based on the data we set up here
        $this->handle_setup_placement_post( $slot_item_id, $placement_data );
    }

    /**
     * Set up a Placement post and its meta based on supplied Placement data
     *
     * @param  int   $slot_item_id   ID of the Slot Item
     * @param  array $placement_data Array of post meta data
     */
    private function handle_setup_placement_post( $slot_item_id, $placement_data ) {
        $placement_post_id = $this->handle_create_placement_post( $slot_item_id );
        $this->handle_add_placement_post_meta( $placement_post_id, $placement_data );
    }

    /**
     * Handle the adding of post meta fields to a Placement post
     *
     * @param  int   $placement_post_id Placement post ID
     * @param  array $placement_data Post meta to add
     */
    private function handle_add_placement_post_meta( $placement_post_id, $placement_data ) {
        if ( ! is_numeric( $placement_post_id ) ) {
            return;
        }

        // If required fields have no values, then quit and delete the placement post...
        foreach ( $placement_data as $field => $values ) {
            if ( empty( $values ) ) {
                if ( in_array( $field, $this->placement_required_base_post_meta ) ) {
                    wp_delete_post( $placement_post_id, true );
                    return;
                }
            }
        }

        $short_type = Utils::remove_name_prefix( $placement_data['type'] );

        // If the post select field is not set, then quit and delete...
        $expected_post_select_field_name = 'select_' . $short_type;
        if ( ! isset( $placement_data[ $expected_post_select_field_name ] ) ) {
            wp_delete_post( $placement_post_id, true );
            return;
        }

        $placement_data['positions'] = [];
        foreach ( self::get_slot_position_data( $short_type ) as $context => $positions ) {
            foreach ( $positions as $position ) {
                $placement_data['positions'][] = $context . '_' . $position;
            }
        }

        foreach ( $placement_data as $field => $values ) {
            if ( ! is_array( $values ) ) {
                $values = [ $values ];
            }

            // Add the placement fields
            foreach ( $values as $value ) {
                add_post_meta( $placement_post_id, $field, $value );
            }
        }
    }

    /**
     * Handle the creation of a Placement post
     *
     * @param  int $slot_item_id ID of the parent Slot Item
     * @return obj|bool $placement_post_id Placement object or false on fail
     */
    private function handle_create_placement_post( $slot_item_id ) {
        $placement_post_id = wp_insert_post( [
            'post_status' => 'publish',
            'post_type'   => '_slot_item_placement',
            'post_parent' => $slot_item_id,
        ], true );
        if ( is_wp_error( $placement_post_id ) ) {
            trigger_error( $placement_post_id->get_error_message(), E_USER_ERROR );
            return false;
        }
        return $placement_post_id;
    }

    /**
     * Handle the `ped_slot()` Twig function
     *
     * @param  array $context        Twig context -- provided automatically
     * @param  string $slot_position Slot position
     * @param  array  $options       Options to override defaults
     * @return string                Slot HTML
     */
    public function handle_twig_func_slot( $context, $slot_position = '', $options = [] ) {
        if ( empty( $slot_position ) || ! is_string( $slot_position ) || ! is_array( $options ) ) {
            return '';
        }

        if ( ! isset( $context['is_email'] ) || empty( $context['item'] ) ) {
            return '';
        }

        // Get some of the default options from the item in the context
        $item = $context['item'];
        if ( is_a( $item, 'Pedestal\Posts\Post' ) ) {
            $default_options = [
                'post_id'   => $item->get_id(),
                'post_type' => 'pedestal_' . $item->get_type(),
            ];
            $options = array_merge( $options, $default_options );
        } else {
            return '';
        }

        // Set slot position name according to the scope
        $scope = 'site';
        if ( ! empty( $context['is_email'] ) && $context['is_email'] ) {
            $scope = 'email';
        }
        $slot_position = $scope . '_' . $slot_position;

        $slot_item = self::get_slot_data( $slot_position, $options );
        if ( $slot_item && is_a( $slot_item, 'Pedestal\Posts\Slots\Slot_Item' ) ) {
            $slot_item_type = $slot_item->get_slot_item_type_slug();

            $context['slots']['active'] = $slot_item;

            $data_atts = [
                'position'   => $slot_position,
                'item-id'    => $slot_item->get_id(),
                'item-type'  => $slot_item_type,
                'item-title' => $slot_item->get_the_title(),
            ];
            $data_atts_str = Utils::array_to_data_atts_str( $data_atts, 'slot' );

            $slot_item_type_class = str_replace( '_', '-', $slot_item_type );
            $html = sprintf( '<div class="c-slot--%s  c-slot  js-slot-%s" %s>',
                $slot_item_type_class,
                $slot_item_type_class,
                $data_atts_str
            );
            ob_start();
            $html .= Timber::render( 'partials/slots/' . $slot_item_type . '.twig', $context );
            ob_get_clean();
            $html .= '</div>';
            return $html;
        } else {
            return '';
        }
    }

    /**
     * Get the names of the slot positions for a placement type
     *
     * @param string $type Placement type
     * @return array Array of position names for the given type
     */
    public static function get_slot_position_data( $type ) {
        return self::$slot_positions[ $type ];
    }

    /**
     * Get the types of slot placements
     *
     * @return array Placement type names
     */
    public static function get_placement_types() {
        return array_keys( self::$slot_positions );
    }

    /**
     * Get the single most recent slot item for a slot
     *
     * If the slot item's rules are met, then return the
     *
     * @param  string $slot_position Slot name
     * @return Post
     */
    private static function get_slot_data( $slot_position, $options ) {
        $data = [];

        $args = [
            'post_type' => '_slot_item_placement',
            'posts_per_page' => 1000,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'   => 'positions',
                    'value' => $slot_position,
                ],
                [
                    'key'   => 'date',
                    'value' => date( self::$day_format ),
                ],
                [
                    'key'   => 'type',
                    'value' => $options['post_type'],
                ],
            ],
        ];

        $query = new \WP_Query( $args );
        $placements = $query->posts;

        if ( empty( $placements ) ) {
            return false;
        }

        $placement_id = 0;
        foreach ( $placements as $placement ) {
            $placement = Placement::get_by_post_id( $placement->ID );
            $placement_type = $placement->get_placement_type();
            $placement_selected_post = $placement->get_selected_post_id();

            // Prioritize specific post over other types of placements
            if ( ! empty( $placement_selected_post )
                && $options['post_id'] == $placement_selected_post
                ) {
                $placement_id = $placement->get_id();
                break;
            } elseif ( empty( $placement_selected_post ) ) {
                // By default, the first queried placement will be used
                $placement_id = $placements[0]->ID;
            }
        }

        // Return the Slot Item object based on the Placement's parent ID
        return Slot_Item::get_by_post_id( wp_get_post_parent_id( $placement_id ) );
    }

    /**
     * Check to make sure the required Placement defaults are set up
     *
     * @param  array  $post_data Post data from a POST request
     * @return boolean
     */
    public static function is_required_data_set_up( $post_data ) {
        // Return null if not a Slot Item or if the defaults are missing
        // entirely -- this doesn't just indicate missing data, this most likely
        // indicates an issue registering the fields
        if ( ! isset( $post_data['slot_item_placement_defaults'] ) || 'pedestal_slot_item' !== $post_data['post_type'] ) {
            return null;
        }

        $defaults = $post_data['slot_item_placement_defaults'];

        // Return false if the placement type is undefined
        if ( empty( $defaults['type'] ) ) {
            return false;
        }

        // Return false if the date is undefined
        if ( empty( $defaults['date'] ) || empty( $defaults['date']['date'] ) ) {
            return false;
        }

        // Return true if all the conditions are met
        return true;
    }

    /**
     * Convert a date string to another date string in YYYY-MM-DD format
     *
     * @param  string $date Date string to convert
     * @return string       YYYY-MM-DD date string
     */
    private function convert_date_to_day( $date ) {
        return date( self::$day_format, strtotime( $date ) );
    }
}
