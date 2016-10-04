<?php

namespace Pedestal\Registrations\Post_Types;

use \Pedestal\Utils\Utils;

use \Pedestal\Posts\Post;

use Pedestal\Posts\Slots\Slots;

class Slot_Types extends Types {

    /**
     * Slot post types
     *
     * @var array
     */
    protected $post_types = [
        'pedestal_slot_item',
        '_slot_item_placement',
    ];

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Slot_Types;
            self::$instance->setup_actions();
            self::$instance->setup_filters();
            self::$instance->setup_types();
        }
        return self::$instance;

    }

    /**
     * Set up actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
    }

    /**
     * Set up filters
     */
    private function setup_filters() {

    }

    /**
     * Register the custom post types
     */
    public function setup_types() {

        foreach ( $this->post_types as $post_type ) :

            $args = [
                'hierarchical'      => false,
                'public'            => true,
                'show_in_nav_menus' => true,
                'show_ui'           => true,
                'has_archive'       => true,
                'query_var'         => true,
            ];

            switch ( $post_type ) {

                case 'pedestal_slot_item':
                    $singular = esc_html__( 'Slot Item', 'pedestal' );
                    $plural = esc_html__( 'Slot Items', 'pedestal' );
                    $class = 'Posts\\Slots\\Slot_Item';
                    $args['menu_position'] = 61;
                    $args['menu_icon'] = 'dashicons-businessman';
                    $args['supports'] = [
                        'title'
                    ];
                    $args['rewrite'] = false;
                    $args['public'] = false;
                    $args['show_in_menu'] = 'slots';
                    $args['show_in_admin_bar'] = false;
                    $args['has_archive'] = false;
                    break;

                case '_slot_item_placement':
                    $singular = esc_html__( 'Slot Item Placement', 'pedestal' );
                    $plural = esc_html__( 'Slot Item Placements', 'pedestal' );
                    $class = 'Posts\\Slots\\Placement';
                    $args['menu_position'] = 61;
                    $args['menu_icon'] = 'dashicons-businessman';
                    $args['supports'] = [
                        'title'
                    ];
                    $args['rewrite'] = false;
                    $args['public'] = false;
                    $args['show_ui'] = false;
                    $args['show_in_nav_menus'] = false;
                    $args['show_in_admin_bar'] = false;
                    $args['has_archive'] = false;
                    break;

            }

            $post_types[ $post_type ] = compact( 'singular', 'plural', 'class', 'args' );

        endforeach;

        $this->post_types = $post_types;

    }

    /**
     * Register Slot Item fields
     */
    public function action_init_after_post_types_registered() {
        $boxes = [];

        // The Sponsorship / Partnership term ID depends on a predictable
        // slug, so a term with this slug must be created if it doesn't exist
        $term_sponsors = get_term_by( 'slug', 'sponsors-partners', 'pedestal_slot_item_type' );
        if ( ! empty( $term_sponsors ) && is_a( $term_sponsors, 'WP_Term' ) ) {
            $term_sponsors_id = (int) $term_sponsors->term_id;
        } else {
            $term_sponsors_data = wp_insert_term( 'Sponsorship / Partnership', 'pedestal_slot_item_type', [
                'slug' => 'sponsors-partners',
            ] );

            if ( is_array( $term_sponsors_data ) && ! is_wp_error( $term_sponsors_data ) && isset( $term_sponsors_data['term_id'] ) ) {
                $term_sponsors_id = $term_sponsors_data['term_id'];
                if ( ! is_numeric( $term_sponsors_id ) ) {
                    return;
                }
                add_term_meta( $term_sponsors_id, 'plural', 'Sponsorships / Partnerships', true );
            } elseif ( is_wp_error( $term_sponsors_data ) ) {
                trigger_error( $term_sponsors_data->get_error_message(), E_USER_ERROR );
                return;
            } else {
                return;
            }
        }

        $post_type_support = Types::get_post_types_with_label(
            Types::get_post_types_by_supported_feature( 'slots' )
        );

        $label_desc = 'The text to display alongside the image. Defaults to "Sponsored By" if unset.';

        $slot_placement_defaults_desc = 'Set the default placement settings for
        this Slot Item. Additional placements can be created in the Additional
        Placement Rules box below. Each of these additional rules may override
        the defaults set here.';

        $additional_slot_rules_desc = 'Add rules for displaying additional
        placements. All fields are optional and will default to the values set
        in Slot Placement Defaults if left empty. However, you should set at
        least one of these overriding options or else the additional rule will
        do nothing.';

        $days_desc = 'If you have an End Date set, you can select the days of
        the week this slot item should display. These options only take effect
        if you have an end date set. If you want the slot item to display for
        all days of the week during the range, just leave all of these
        unchecked.';

        // Slot Item Type specific fields
        $boxes['type'] = [];
        $boxes['type']['name'] = 'Item Type';
        $boxes['type']['fields'] = new \Fieldmanager_Group( false, [
            'name'           => 'slot_item_type',
            'children'       => [
                'type' => new \Fieldmanager_Select( false, [
                    'name'           => 'type',
                    'description'    => esc_html__( 'Select the type of Slot Item.', 'pedestal' ),
                    'first_empty'    => true,
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                    'datasource'     => new \Fieldmanager_Datasource_Term( [
                        'taxonomy'                    => 'pedestal_slot_item_type',
                        'taxonomy_hierarchical'       => true,
                        'taxonomy_hierarchical_depth' => 0,
                        'append_taxonomy'             => false,
                        'taxonomy_save_to_terms'      => true,
                    ] ),
                ] ),
                'sponsorship' => new \Fieldmanager_Group( false, [
                    'name'           => 'sponsorship',
                    'display_if'  => [
                        'src'   => 'type',
                        'value' => $term_sponsors_id,
                    ],
                    'children'       => [
                        'url' => new \Fieldmanager_Link( esc_html__( 'Link URL', 'pedestal' ), [
                            'required'            => true,
                            'validation_rules'    => 'required',
                            'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                        ] ),
                        'label'  => new \Fieldmanager_TextField( esc_html__( 'Label', 'pedestal' ), [
                            'name'          => 'label',
                            'description'   => $label_desc,
                            'required'            => true,
                            'validation_rules'    => 'required',
                            'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                            'default_value' => esc_html__( 'Sponsored By', 'pedestal' ),
                        ] ),
                        'upload' => new \Fieldmanager_Media( esc_html__( 'Upload Image', 'pedestal' ), [
                            'button_label'       => esc_html__( 'Select an image', 'pedestal' ),
                            'modal_button_label' => esc_html__( 'Select image', 'pedestal' ),
                            'modal_title'        => esc_html__( 'Choose image', 'pedestal' ),
                        ] ),
                    ],
                ] ),
            ],
        ] );

        // Set up a template for all placement fields including defaults and additional rules
        $placement_fields = [
            'type' => new \Fieldmanager_Select( esc_html__( 'Placement Type', 'pedestal' ), [
                'options'     => $post_type_support,
                'first_empty' => true,
            ] ),
        ];

        // Add post selection fields for each supported post type
        foreach ( $post_type_support as $post_type => $post_type_label ) {
            $field_name = 'select_' . Utils::remove_name_prefix( $post_type );
            $placement_fields[ $field_name ] = new \Fieldmanager_Autocomplete( esc_html__( $post_type_label, 'pedestal' ), [
                'name' => $field_name,
                'attributes' => [
                    'placeholder'  => esc_html__( 'Search by post title or leave blank for all/any', 'pedestal' ),
                    'size'         => 50,
                ],
                'display_if'  => [
                    'src'   => 'type',
                    'value' => $post_type,
                ],
                'datasource' => new \Fieldmanager_Datasource_Post( [
                    'query_args' => [
                        'post_type' => $post_type,
                    ],
                ] ),
            ] );
        }

        // Add the date fields after everything else
        $placement_fields['date_start']         = new \Fieldmanager_Datepicker( esc_html__( 'Start Date', 'pedestal' ) );
        $placement_fields['date_end']           = new \Fieldmanager_Datepicker( esc_html__( 'End Date (Optional)', 'pedestal' ) );
        $placement_fields['date_subrange_days'] = new \Fieldmanager_Checkboxes( esc_html__( 'Sub-range Days of Week (Optional)', 'pedestal' ), [
            'description' => esc_html__( $days_desc, 'pedestal' ),
            'options'     => Utils::get_days_of_week(),
        ] );

        // Setup Placement Default fields
        $boxes['placement_defaults'] = [];
        $boxes['placement_defaults']['name'] = 'Slot Placement Defaults';
        $boxes['placement_defaults']['fields'] = new \Fieldmanager_Group( [
            'name'        => 'slot_item_placement_defaults',
            'description' => esc_html__( $slot_placement_defaults_desc, 'pedestal' ),
            'children'    => $placement_fields,
        ] );

        // Setup additional Placement Rules fields
        $boxes['placement_rules'] = [];
        $boxes['placement_rules']['name'] = 'Additional Placement Rules';
        $boxes['placement_rules']['fields'] = new \Fieldmanager_Group( [
            'name'           => 'slot_item_placement_rules',
            'description'    => esc_html__( $additional_slot_rules_desc, 'pedestal' ),
            // 'minimum_count'  => 0,
            'limit'          => 0,
            'save_empty'     => false,
            'extra_elements' => 0,
            'add_more_label' => esc_html__( 'Add Rule', 'pedestal' ),
            'children'       => $placement_fields,
        ] );

        // Create the metaboxes for slot items
        foreach ( $boxes as $v ) {
            $v['fields']->add_meta_box(
                esc_html__( $v['name'], 'pedestal' ),
                [ 'pedestal_slot_item' ],
                'normal',
                'high'
            );
        }
    }
}
