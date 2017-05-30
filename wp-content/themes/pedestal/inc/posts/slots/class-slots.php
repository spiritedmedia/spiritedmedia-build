<?php

namespace Pedestal\Posts\Slots;

use Timber\Timber;

use function Pedestal\Pedestal;
use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Slots {

    private static $instance;

    /**
     * Format for comparing days
     *
     * @var string
     */
    private static $day_format = 'Y-m-d';

    /**
     * Keys of the placement date fields
     *
     * @var array
     */
    private static $placement_date_keys = [
        'date_start',
        'date_end',
    ];

    private $placement_required_base_post_meta = [
        'type',
        'date_start',
        'date_end',
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
            'site'  => [
                'single_lead',
                'shortcode',
                'newsletter_item',
                'newsletter_promoted_event',
            ],
            'email' => [
                'shortcode',
                'newsletter_item',
                'newsletter_promoted_event',
            ],
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
        'daily_insta' => [
            'site'  => [ 'component' ],
            'email' => [ 'component' ],
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
        add_filter( 'timber/twig', function( $twig ) {
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

        // Store the date in ISO-8601 date format, compensate for
        // FM's weird date array storage
        $setup_dates = function( &$array ) {
            foreach ( self::$placement_date_keys as $key ) {
                if ( empty( $array[ $key ] ) ) {
                    continue;
                }
                $value = $array[ $key ];
                if ( ! empty( $value['date'] ) && is_string( $value['date'] ) ) {
                    $value = strtotime( $value['date'] );
                }
                $array[ $key ] = date( self::$day_format, $value );
            }
            if ( empty( $array['date_end'] ) ) {
                $array['date_end'] = 0;
            }
        };

        if ( isset( $post->post_status ) && ( 'publish' !== $post->post_status ) ) {
            return;
        }

        $slot_item = Slot_Item::get_by_post_id( $post_id );
        if ( ! $slot_item instanceof Slot_Item ) {
            return;
        }

        // Delete all child posts and start fresh
        foreach ( $slot_item->get_placement_post_ids() as $id ) {
            wp_delete_post( $id, true );
        }

        $placement_defaults = $_POST['slot_item_placement_defaults'];
        $placement_rules = $_POST['slot_item_placement_rules'];

        // Handle missing start/end dates
        if (
            ( empty( $placement_defaults['date_end'] ) )
            || ( isset( $placement_defaults['date_end']['date'] ) && empty( $placement_defaults['date_end']['date'] ) )
        ) {
            $placement_defaults['date_end'] = $placement_defaults['date_start'];
        } elseif (
            ( empty( $placement_defaults['date_end'] ) )
            || ( isset( $placement_defaults['date_start']['date'] ) && empty( $placement_defaults['date_start']['date'] ) )
        ) {
            $placement_defaults['date_start'] = $placement_defaults['date_end'];
        }

        $_POST['slot_item_placement_defaults'] = $placement_defaults;
        $default_placement_data = $placement_defaults + [ 'index' => 0 ];
        $setup_dates( $default_placement_data );
        $this->handle_setup_placement_post( $post_id, $default_placement_data );

        if ( ! empty( $placement_rules ) ) {
            $placement_number = 0;
            foreach ( $placement_rules as &$placement_rule ) {
                $placement_number++;
                if ( empty( $placement_rule['date_end']['date'] ) ) {
                    if ( empty( $placement_rule['date_start']['date'] ) ) {
                        $placement_rule['date_start'] = $placement_defaults['date_start'];
                        $placement_rule['date_end'] = $placement_defaults['date_end'];
                    } else {
                        $placement_rule['date_end'] = $placement_rule['date_start'];
                    }
                } elseif ( empty( $placement_rule['date_start']['date'] ) ) {
                    $placement_rule['date_start'] = $placement_rule['date_end'];
                }
                $placement_rule_data = $placement_rule;
                unset( $placement_rule_data['proto'] );
                $setup_dates( $placement_rule_data );
                $this->handle_setup_placement_rules_posts( $post_id, $default_placement_data, $placement_rule_data, $placement_number );
            }
            $_POST['slot_item_placement_rules'] = $placement_rules;
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
            if ( empty( $placement_rule[ $key ] ) ) {
                continue;
            }
            $placement_data[ $key ] = $placement_rule[ $key ];
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

        $type = $placement_data['type'];
        $short_type = Utils::remove_name_prefix( $type );

        // If the post select field is not set, then quit and delete...
        $expected_post_select_field_name = 'select_' . $short_type;
        if ( in_array( $type, Types::get_post_types() ) && ! isset( $placement_data[ $expected_post_select_field_name ] ) ) {
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
     * Query placements based on provided criteria
     *
     * @param  array  $options Query options
     * @return array|false     Array of Placement objects if successful, false on failure
     */
    private static function get_placements( array $options ) {
        if ( ! $options ) {
            return false;
        }

        $today = date( self::$day_format );
        $today_day_of_week_num = date( 'w' );

        $args = [
            'post_type' => '_slot_item_placement',
            'posts_per_page' => 1000,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => 'date_start',
                    'value'   => $today,
                    'compare' => '<=',
                ],
                [
                    'key'     => 'date_end',
                    'value'   => $today,
                    'compare' => '>=',
                ],
                [
                    'key'   => 'type',
                    'value' => $options['type'],
                ],
            ],
        ];

        if ( ! empty( $options['slot_position'] ) ) {
            $args['meta_query'][] = [
                'key'   => 'positions',
                'value' => $options['slot_position'],
            ];
        }

        $query = new \WP_Query( $args );
        $placements = $query->posts;

        if ( empty( $placements ) ) {
            return false;
        }

        foreach ( $placements as $k => $placement ) {
            $placement_obj = Placement::get_by_post_id( $placement->ID );
            if ( ! $placement_obj instanceof Placement ) {
                unset( $placements[ $k ] );
                continue;
            }

            $placement_type = $placement_obj->get_placement_type();
            $placement_selected_post = $placement_obj->get_selected_post_id();
            $placement_date_start = $placement_obj->get_date_start();
            $placement_date_end = $placement_obj->get_date_end();
            $placement_subrange_days = $placement_obj->get_date_subrange_days();

            // Omit some placements that don't fit within our date / day of week criteria
            if (
                ( empty( $placement_date_start ) || empty( $placement_date_end ) ) ||
                ( ! empty( $placement_subrange_days ) && ! in_array( $today_day_of_week_num, $placement_subrange_days )
                )
            ) {
                unset( $placements[ $k ] );
                continue;
            }

            $placements[ $k ] = $placement_obj;
        }

        if ( empty( $placements ) ) {
            return false;
        }
        return $placements;
    }

    /**
     * Get the post object for a slot
     *
     * @param  string $return_type       Non-prefixed post type to return
     *     instance of, e.g. 'slot_item'
     * @param  array  $placement_options Options for restricting Placements
     * @return Post|false                Post family object on success, false on fail
     */
    public static function get_slot_data( string $return_type, array $placement_options ) {
        if ( ! $return_type ) {
            return false;
        }

        $post_type = 'pedestal_' . $return_type;
        $post_class = Types::get_post_type_class( $post_type );

        if ( 'slot_item' !== $return_type ) {
            $placement_options['type'] = $post_type;
        }

        $placements = static::get_placements( $placement_options );
        if ( empty( $placements ) || ! is_array( $placements ) ) {
            return false;
        }

        foreach ( $placements as $k => $placement ) {
            $post_id = 0;
            $placement_selected_post = $placement->get_selected_post_id();

            if ( 'slot_item' === $return_type ) {
                // Prioritize specific post over other types of placements
                if ( ! empty( $placement_selected_post ) && $placement_options['post_id'] != $placement_selected_post ) {
                    continue;
                }
                $post_id = wp_get_post_parent_id( $placement->get_id() );
            } elseif ( ! empty( $placement_selected_post ) ) {
                $post_id = $placement_selected_post;
            }

            $post_obj = $post_class::get_by_post_id( $post_id );
            if ( ! $post_obj instanceof $post_class ) {
                continue;
            }

            if ( 'publish' !== $post_obj->get_status() ) {
                continue;
            }

            return $post_obj;
        }
    }

    /**
     * Handle the `ped_slot()` Twig function
     *
     * @param  array  $context            Twig context -- provided automatically
     * @param  string $slot_position      Slot position
     * @param  string $placement_type     Placement type
     * @param  array  $placement_options  Options to override defaults
     * @return string                Slot HTML
     */
    public function handle_twig_func_slot( $context,
        string $slot_position = '',
        string $placement_type = '',
        array $placement_options = []
    ) {

        if ( empty( $slot_position ) || empty( $context['item'] ) ) {
            return '';
        }

        $default_options = $data_atts = [];

        $slot_data_return_type = 'slot_item';
        if ( 'newsletter_promoted_event' === $slot_position ) {
            $slot_data_return_type = 'event';
        } else {
            // Get some of the default options from the item in the context
            $item = $context['item'];
            if ( Types::is_post( $item ) ) {
                $default_options = [
                    'post_id' => $item->get_id(),
                    'type'    => $item->get_post_type(),
                ];
            } else {
                return '';
            }
        }
        $placement_options = array_merge( $placement_options, $default_options );

        if ( ! empty( $placement_type ) ) {
            $placement_options['type'] = $placement_type;
            if ( 'component' === $slot_position ) {
                $data_atts['component-type'] = $placement_type;
            }
        }

        if ( false !== strpos( $slot_position, 'lead' ) ) {
            $data_atts['supports-premium'] = '';
            $context['slots']['supports_premium'] = true;
        }

        // Set slot position name according to the scope
        $scope = Pedestal()->is_email() ? 'email' : 'site';
        $scoped_slot_position = $scope . '_' . $slot_position;
        $placement_options['slot_position'] = $scoped_slot_position;

        $slot_data = self::get_slot_data( $slot_data_return_type, $placement_options );
        if ( Types::is_post( $slot_data ) ) {
            $template = $item_type = $additional_classes = '';
            if ( $slot_data instanceof Slot_Item ) {
                $item_type = $slot_data->get_slot_item_type_slug();
                $template = 'partials/slots/' . $item_type . '.twig';
            } elseif ( 'newsletter_promoted_event' === $slot_position ) {
                $context['item'] = $slot_data;
                $item_type = $slot_data->get_type();
                $additional_classes = 'c-stream__item';
                $template = 'partials/slots/' . str_replace( '_', '-', $slot_position ) . '.twig';
            }

            if ( ! $template || ! $item_type ) {
                return '';
            }

            $context['slots']['active'] = $slot_data;

            $data_atts = $data_atts + [
                'position'   => $scoped_slot_position,
                'item-id'    => $slot_data->get_id(),
                'item-type'  => $item_type,
                'item-title' => $slot_data->get_the_title(),
            ];
            $data_atts_str = Utils::array_to_data_atts_str( $data_atts, 'slot' );

            $item_type_class = str_replace( '_', '-', $item_type );
            $html = sprintf( '<div class="c-slot--%s  c-slot %s" %s>',
                $item_type_class,
                $additional_classes,
                $data_atts_str
            );
            ob_start();
            $html .= Timber::render( $template, $context );
            ob_get_clean();
            $html .= '</div>';
            return $html;
        }
        return '';
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

        // Return false if the start date field is undefined
        if ( empty( $defaults['date_start'] ) || empty( $defaults['date_start']['date'] ) ) {
            return false;
        }

        // Return true if all the conditions are met
        return true;
    }
}
