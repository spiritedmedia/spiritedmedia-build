<?php

namespace Pedestal\Registrations\Post_Types;

use Timber\Timber;

use Pedestal\Utils;

use \Pedestal\Registrations\Taxonomies\Taxonomies;

use \Pedestal\Posts\Post;
use \Pedestal\Posts\Clusters\Geospaces\Geospace;
use \Pedestal\Posts\Clusters\Geospaces\Localities\Locality;

class Cluster_Types extends Types {

    /**
     * Types of geospatial relationships
     *
     * @var array
     */
    private $geospatial_rels = [
        'contained_in',
        'intersects',
        'borders',
        'same_as',
    ];

    /**
     * Cluster post types
     *
     * @var array
     */
    protected $post_types = [
        'pedestal_story',
        'pedestal_topic',
        'pedestal_person',
        'pedestal_org',
        'pedestal_place',
        'pedestal_locality',
    ];

    /**
     * Cluster connection types
     *
     * @var array
     */
    protected $connection_types = [];

    /**
     * Entity-to-Cluster connection types
     *
     * @var array
     */
    protected $connection_types_entities = [];

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Cluster_Types;
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

        add_action( 'p2p_init', [ $this, 'action_p2p_init_register_connections' ] );
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
        add_action( 'edit_form_after_title', [ $this, 'action_edit_form_after_title' ] );
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ] );

    }

    /**
     * Set up filters
     */
    private function setup_filters() {

        add_filter( 'p2p_connectable_args', [ $this, 'filter_p2p_connectable_args' ], 10, 3 );
        add_filter( 'p2p_candidate_title', [ $this, 'filter_p2p_item_title' ], 10, 3 );
        add_filter( 'p2p_connected_title', [ $this, 'filter_p2p_item_title' ], 10, 3 );
        add_filter( 'post_type_link', [ $this, 'filter_post_type_link' ], 10, 2 );

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

                case 'pedestal_story':
                    $singular = esc_html__( 'Story', 'pedestal' );
                    $plural = esc_html__( 'Stories', 'pedestal' );
                    $class = 'Posts\\Clusters\\Story';
                    $args['menu_position'] = 101;
                    $args['menu_icon'] = 'dashicons-book';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                        'editor',
                        'excerpt',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'stories',
                    ];
                    break;

                case 'pedestal_topic':
                    $singular = esc_html__( 'Topic', 'pedestal' );
                    $plural = esc_html__( 'Topics', 'pedestal' );
                    $class = 'Posts\\Clusters\\Topic';
                    $args['menu_position'] = 102;
                    $args['menu_icon'] = 'dashicons-pressthis';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'topics',
                        'with_front' => false,
                    ];
                    // Prevent this cluster from displaying publicly
                    $args['publicly_queryable'] = false;
                    break;

                case 'pedestal_person':
                    $singular = esc_html__( 'Person', 'pedestal' );
                    $plural = esc_html__( 'People', 'pedestal' );
                    $class = 'Posts\\Clusters\\Person';
                    $args['menu_position'] = 103;
                    $args['menu_icon'] = 'dashicons-id-alt';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'people',
                        'with_front' => false,
                    ];
                    // Prevent this cluster from displaying publicly
                    $args['publicly_queryable'] = false;
                    break;

                case 'pedestal_org':
                    $singular = esc_html__( 'Organization', 'pedestal' );
                    $plural = esc_html__( 'Organizations', 'pedestal' );
                    $class = 'Posts\\Clusters\\Org';
                    $args['menu_position'] = 104;
                    $args['menu_icon'] = 'dashicons-admin-multisite';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'organizations',
                        'with_front' => false,
                    ];
                    // Prevent this cluster from displaying publicly
                    $args['publicly_queryable'] = false;
                    break;

                case 'pedestal_place':
                    $singular = esc_html__( 'Place', 'pedestal' );
                    $plural = esc_html__( 'Places', 'pedestal' );
                    $class = 'Posts\\Clusters\\Geospaces\\Place';
                    $args['menu_position'] = 105;
                    $args['menu_icon'] = 'dashicons-location';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'locations',
                        'with_front' => false,
                    ];
                    // Prevent this cluster from displaying publicly
                    $args['publicly_queryable'] = false;
                    break;

                case 'pedestal_locality':
                    $singular = esc_html__( 'Locality', 'pedestal' );
                    $plural = esc_html__( 'Localities', 'pedestal' );
                    $class = 'Posts\\Clusters\\Geospaces\\Localities\\Locality';
                    $args['menu_position'] = 106;
                    $args['menu_icon'] = 'dashicons-location-alt';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                        'editor',
                        'excerpt',
                    ];
                    $args['rewrite'] = [
                        'slug' => '%locality_type%',
                        'with_front' => false,
                    ];
                    $args['has_archive'] = false;
                    $args['publicly_queryable'] = true;
                    break;

            }

            $post_types[ $post_type ] = compact( 'singular', 'plural', 'class', 'args' );

        endforeach;

        $this->post_types = $post_types;

    }

    /**
     * Register Post to Post connections
     */
    public function action_p2p_init_register_connections() {

        foreach ( $this->post_types as $post_type => $settings ) :

            $labels = self::get_post_type_labels( $post_type );
            $sanitized_labels = self::get_sanitized_post_type_labels( $post_type );

            // Connect all clusters to entities
            $connection_entities_to = 'entities_to_' . $sanitized_labels['name'];
            p2p_register_connection_type( [
                'name'           => $connection_entities_to,
                'from'           => self::get_entity_post_types(),
                'to'             => $post_type,
                'sortable'       => 'from',
                'admin_box'      => [
                    'context' => 'advanced',
                ],
                'title'          => [
                    'from' => $labels['name'],
                    'to'   => __( 'Entities', 'pedestal' ),
                ],
                'from_labels'      => [
                    'singular_name' => __( 'Entity', 'pedestal' ),
                    'search_items'  => __( 'Search entities', 'pedestal' ),
                    'not_found'     => __( 'No entities found.', 'pedestal' ),
                    'create'        => __( 'Create Connections', 'pedestal' ),
                ],
            ] );
            $this->connection_types[] = $connection_entities_to;
            $this->connection_types_entities[] = $connection_entities_to;

            // Connect all clusters to users
            p2p_register_connection_type( [
                'name'           => $sanitized_labels['name'] . '_to_users',
                'from'           => $post_type,
                'to'             => 'user',
                'admin_box'      => [
                    'show' => false,
                ],
            ]);
            $this->connection_types[] = $sanitized_labels['name'] . '_to_users';

            // Connect non-story clusters to Stories
            if ( 'pedestal_story' != $post_type ) {
                p2p_register_connection_type( [
                    'name'           => 'stories_to_' . $sanitized_labels['name'],
                    'from'           => 'pedestal_story',
                    'to'             => $post_type,
                    'sortable'       => 'from',
                    'admin_box'      => [
                        'context' => 'advanced',
                    ],
                    'title'          => [
                        'from' => $labels['name'],
                        'to'   => __( 'Stories', 'pedestal' ),
                    ],
                ] );
                $this->connection_types[] = 'stories_to_' . $sanitized_labels['name'];
            }

            // Connect non-Topic and non-Story clusters to Topics
            if ( 'pedestal_topic' != $post_type && 'pedestal_story' != $post_type ) {
                p2p_register_connection_type( [
                    'name'           => 'topics_to_' . $sanitized_labels['name'],
                    'from'           => 'pedestal_topic',
                    'to'             => $post_type,
                    'sortable'       => 'from',
                    'admin_box'      => [
                        'context' => 'advanced',
                    ],
                    'title'          => [
                        'from' => $labels['name'],
                        'to'   => __( 'Topics', 'pedestal' ),
                    ],
                ] );
                $this->connection_types[] = 'topics_to_' . $sanitized_labels['name'];
            }

            if ( in_array( $post_type, self::get_geospace_post_types() ) ) {
                // Connect geospaces to themselves, with metadata
                p2p_register_connection_type( [
                    'name'           => $sanitized_labels['name'] . '_to_' . $sanitized_labels['name'],
                    'from'           => $post_type,
                    'to'             => $post_type,
                    'admin_box'      => [
                        'context' => 'advanced',
                        'show'    => 'from',
                    ],
                    'title'          => [
                        'from'          => __( sprintf( 'Connected %s: Active', $labels['name'] ), 'pedestal' ),
                    ],
                    'fields'         => [
                        'rel' => [
                            'title'   => 'Relation',
                            'type'    => 'select',
                            'values'  => $this->geospatial_rels,
                            'default' => 'contained_in',
                        ],
                    ],
                ] );
                $this->connection_types[] = $sanitized_labels['name'] . '_to_' . $sanitized_labels['name'];
            }

        endforeach;

        // Connect People to Organizations
        p2p_register_connection_type( [
            'name'           => 'people_to_organizations',
            'from'           => 'pedestal_person',
            'to'             => 'pedestal_org',
            'sortable'       => 'from',
            'admin_box'      => [
                'context' => 'advanced',
            ],
            'title'          => [
                'from' => __( 'Organizations', 'pedestal' ),
                'to'   => __( 'People', 'pedestal' ),
            ],
        ] );
        $this->connection_types[] = 'people_to_organizations';

        // Connect Organizations to Places
        p2p_register_connection_type( [
            'name'           => 'organizations_to_places',
            'from'           => 'pedestal_org',
            'to'             => 'pedestal_place',
            'sortable'       => 'from',
            'admin_box'      => [
                'context' => 'advanced',
            ],
            'title'          => [
                'from' => __( 'Places', 'pedestal' ),
                'to'   => __( 'Organizations', 'pedestal' ),
            ],
        ] );
        $this->connection_types[] = 'organizations_to_places';

        // Connect Localities to Places with metadata
        p2p_register_connection_type( [
            'name'           => 'places_to_localities',
            'from'           => 'pedestal_place',
            'to'             => 'pedestal_locality',
            'sortable'       => 'from',
            'admin_box'      => [
                'context' => 'advanced',
            ],
            'title'          => [
                'from' => __( 'Localities', 'pedestal' ),
                'to'   => __( 'Places', 'pedestal' ),
            ],
            'fields'         => [
                'rel' => [
                    'title'   => 'Relation',
                    'type'    => 'select',
                    'values'  => $this->geospatial_rels,
                    'default' => 'contained_in',
                ],
            ],
        ] );
        $this->connection_types[] = 'places_to_localities';

    }

    /**
     * Register cluster fields
     */
    public function action_init_after_post_types_registered() {
        $this->register_cluster_connection_fields();
        $this->register_cluster_fields();
        $this->register_story_fields();
        $this->register_person_fields();
        $this->register_organization_fields();
        $this->register_geospace_fields();
        $this->register_place_fields();
        $this->register_locality_fields();

        // @TODO https://github.com/alleyinteractive/wordpress-fieldmanager/issues/435
        // $this->register_hood_fields();
    }

    /**
     * Do whatever below the title field
     */
    public function action_edit_form_after_title() {

        if ( 'pedestal_story' == get_current_screen()->post_type ) {
            $this->pedestal_headline_context->render_meta_box( get_post( get_the_ID() ) );
        }

    }

    /**
     * Render the metabox for listing indeterminate Geospace connections
     */
    public function action_add_meta_boxes() {
        foreach ( self::get_geospace_post_types() as $post_type ) {
            $labels = self::get_post_type_labels( $post_type );
            $sanitized_labels = self::get_sanitized_post_type_labels( $post_type );
            $connection_type = $sanitized_labels['name'] . '_to_' . $sanitized_labels['name'];
            $metabox_id = "pedestal-metabox-p2p-connections-{$connection_type}";
            $metabox_label = sprintf( 'Connected %s: Passive', $labels['name'] );
            add_meta_box(
                $metabox_id,
                $metabox_label,
                [ $this, 'render_meta_box_p2p_connections_geospaces_to_geospaces' ],
                $post_type
            );
        }
    }

    /**
     * Filter post type links
     */
    public function filter_post_type_link( $link, $post ) {
        $post_id = $post->ID;
        if ( 'pedestal_locality' === get_post_type( $post ) ) {
            $locality = Locality::get_by_post_id( $post_id );
            $terms = wp_get_object_terms( $post_id, 'pedestal_locality_type' );
            if ( ! empty( $terms ) ) {
                return str_replace( '%locality_type%', $terms[0]->slug, $link );
            }
        }
        return $link;
    }

    /**
     * Filter the P2P query to get connectable posts
     */
    public function filter_p2p_connectable_args( $args, $ctype, $post_id ) {
        $args['p2p:per_page'] = 10;

        // @TODO https://github.com/spiritedmedia/spiritedmedia/issues/1326
        // if ( in_array( $ctype->name, $this->connection_types ) ) {
        //     $args['orderby'] = 'title';
        //     $args['order'] = 'asc';
        // }

        return $args;
    }

    /**
     * Filter the P2P candidate or connected post title
     */
    public function filter_p2p_item_title( $title, $post, $ctype ) {
        if ( 'pedestal_locality' === get_post_type( $post ) ) {
            $locality = Locality::get_by_post_id( $post->ID );
            $title .= ' (' . $locality->get_locality_type_name() . ')';
        }
        return $title;
    }

    /**
     * Register fields for stories
     */
    private function register_story_fields() {

        $headline = new \Fieldmanager_TextField( false, [
            'name'         => 'headline',
            'attributes'   => [
                'placeholder'       => esc_html__( 'Headline (optional)', 'pedestal' ),
            ],
        ] );
        $this->pedestal_headline_context = new \Fieldmanager_Context_Post( esc_html__( 'Headline', 'pedestal' ), [ 'pedestal_story' ], 'edit_form_after_title', 'default', $headline );

        $story_branding = new \Fieldmanager_Group( [
            'name'     => 'story_branding',
            'children' => [
                'enabled' => new \Fieldmanager_Radios( false, [
                    'name'              => 'enabled',
                    'default_value'     => 0,
                    'options'           => [
                        'data' => [
                            esc_html__( 'Off', 'pedestal' ) => 0,
                            esc_html__( 'On', 'pedestal' )  => 1,
                        ],
                    ],
                    'sanitize'          => 'intval',
                ] ),
                'background_color' => new \Fieldmanager_TextField( 'Background Color', [
                    'name'          => 'background_color',
                    'default_value' => '#1d3557',
                    'attributes'    => [
                        'class'       => 'js-background-color',
                        'placeholder' => esc_html__( 'Background Color (optional)', 'pedestal' ),
                    ],
                ] ),
                'foreground_color'  => new \Fieldmanager_TextField( 'Text Color', [
                    'name'          => 'foreground_color',
                    'default_value' => '#fff',
                    'attributes'    => [
                        'class'       => 'js-foreground-color',
                        'placeholder' => esc_html__( 'Foreground Color (optional)', 'pedestal' ),
                    ],
                ] ),
                'icon'              => new \Fieldmanager_Media( 'Icon Upload', [
                    'name'        => 'icon',
                    'description' => esc_html__( 'Each story should use a unique file. Files should be SVGs with a square artboard.', 'pedestal' ),
                    'attributes'  => [
                        'class'       => 'js-icon-upload',
                        'placeholder' => esc_html__( 'Icon Upload', 'pedestal' ),
                    ],
                ]),
            ],
        ] );
        $story_branding->add_meta_box( 'Branding', 'pedestal_story', 'advanced', 'low' );

    }

    /**
     * Register the fields for People
     */
    private function register_person_fields() {

        // Name fields
        $name_children = [
            'prefix' => new \Fieldmanager_Textfield( esc_html__( 'Prefix', 'pedestal' ), [
                'name' => 'prefix',
            ] ),
            'first' => new \Fieldmanager_Textfield( esc_html__( 'First Name', 'pedestal' ), [
                'name' => 'first',
                'required' => true,
                'validation_rules' => 'required',
                'validation_messages' => esc_html__( 'Required', 'pedestal' ),
            ] ),
            'middle' => new \Fieldmanager_Textfield( esc_html__( 'Middle Name', 'pedestal' ), [
                'name' => 'middle',
            ] ),
            'nickname' => new \Fieldmanager_Textfield( esc_html__( 'Nickname', 'pedestal' ), [
                'name' => 'nickname',
            ] ),
            'last' => new \Fieldmanager_Textfield( esc_html__( 'Last Name', 'pedestal' ), [
                'name' => 'last',
                'required' => true,
                'validation_rules' => 'required',
                'validation_messages' => esc_html__( 'Required', 'pedestal' ),
            ] ),
            'suffix' => new \Fieldmanager_Textfield( esc_html__( 'Suffix', 'pedestal' ), [
                'name' => 'suffix',
            ] ),
        ];
        $name = new \Fieldmanager_Group( false, [
            'name'           => 'person_name',
            'children'       => $name_children,
            'serialize_data' => false,
        ] );

        // Biographical Details fields
        $details_children = [
            'known_for' => new \Fieldmanager_Textfield( esc_html__( 'Known For', 'pedestal' ), [
                'name' => 'known_for',
            ] ),
            'url' => new \Fieldmanager_Link( esc_html__( 'Website URL', 'pedestal' ), [
                'name' => 'url',
                'required' => false,
            ] ),
            'age' => new \Fieldmanager_Textfield( esc_html__( 'Age', 'pedestal' ), [
                'name' => 'age',
            ] ),
        ];
        $details = new \Fieldmanager_Group( false, [
            'name'           => 'person_details',
            'children'       => $details_children,
            'serialize_data' => false,
        ] );

        // Social media fields
        $social_children = [
            'twitter' => new \Fieldmanager_Link( esc_html__( 'Twitter', 'pedestal' ), [
                'name' => 'twitter',
            ] ),
            'instagram' => new \Fieldmanager_Textfield( esc_html__( 'Instagram Username', 'pedestal' ), [
                'name' => 'instagram',
            ] ),
        ];
        $social = new \Fieldmanager_Group( false, [
            'name'           => 'person_social',
            'children'       => $social_children,
            'serialize_data' => false,
        ] );

        $name->add_meta_box( esc_html__( 'Name', 'pedestal' ), [ 'pedestal_person' ], 'normal', 'high' );
        $details->add_meta_box( esc_html__( 'Biographical Details', 'pedestal' ), [ 'pedestal_person' ], 'normal', 'high' );
        $social->add_meta_box( esc_html__( 'Social Media URLs', 'pedestal' ), [ 'pedestal_person' ], 'normal', 'high' );

    }

    /**
     * Register the fields for Organizations
     */
    private function register_organization_fields() {

        $details_children = [
            'url' => new \Fieldmanager_Link( esc_html__( 'URL', 'pedestal' ), [
                'name'     => 'url',
                'required' => false,
            ] ),
            'full_name' => new \Fieldmanager_Textfield( esc_html__( 'Full Name', 'pedestal' ), [
                'name'        => 'full_name',
                'description' => esc_html__( 'The full name of the organization, if it\'s not the common name / title.', 'pedestal' ),
            ] ),
            'num_employees' => new \Fieldmanager_Textfield( esc_html__( 'Number of Employees', 'pedestal' ), [
                'name' => 'num_employees',
            ] ),
            'founding_date' => new \Fieldmanager_Textfield( esc_html__( 'Founding Date', 'pedestal' ), [
                'name' => 'founding_date',
            ] ),
        ];
        $details = new \Fieldmanager_Group( false, [
            'name'           => 'org_details',
            'children'       => $details_children,
            'serialize_data' => false,
        ] );
        $details->add_meta_box( esc_html__( 'Details', 'pedestal' ), [ 'pedestal_org' ], 'normal', 'high' );

    }

    private function register_geospace_fields() {
        $details_children = [
            'full_name' => new \Fieldmanager_Textfield( esc_html__( 'Full Name', 'pedestal' ), [
                'name'        => 'full_name',
                'description' => esc_html__( 'The full name of the cluster, if it\'s not the common name / title.', 'pedestal' ),
            ] ),
            'url' => new \Fieldmanager_Link( esc_html__( 'URL', 'pedestal' ), [
                'name'     => 'url',
                'required' => false,
            ] ),
            'map_url' => new \Fieldmanager_Link( esc_html__( 'Map URL', 'pedestal' ), [
                'name'        => 'map_url',
                'required'    => false,
                'description' => esc_html__( 'A URL to a map of the place. Preferably Google Maps. OpenStreetMap is okay too.', 'pedestal' ),
            ] ),
        ];
        $details = new \Fieldmanager_Group( false, [
            'name'           => 'geospace_details',
            'children'       => $details_children,
            'serialize_data' => false,
        ] );
        $details->add_meta_box( esc_html__( 'Geospace Details', 'pedestal' ), self::get_geospace_post_types(), 'normal', 'high' );
    }

    /**
     * Register the fields for Places
     */
    private function register_place_fields() {

        $address_children = [
            'street_01' => new \Fieldmanager_Textfield( esc_html__( 'Street Address', 'pedestal' ), [
                'name' => 'street_01',
            ] ),
            'street_02' => new \Fieldmanager_Textfield( esc_html__( 'Street Address (Line 2)', 'pedestal' ), [
                'name' => 'street_02',
            ] ),
            'po_box' => new \Fieldmanager_Textfield( esc_html__( 'P.O. Box Number', 'pedestal' ), [
                'name'        => 'po_box',
                'description' => esc_html__( 'Include number only (no "P.O." needed).', 'pedestal' ),
            ] ),
            'postal_code' => new \Fieldmanager_Textfield( esc_html__( 'Postal Code', 'pedestal' ), [
                'name' => 'postal_code',
            ] ),
        ];
        $address = new \Fieldmanager_Group( false, [
            'name'           => 'place_address',
            'children'       => $address_children,
            'description'    => esc_html__( 'Street address only. City info is entered in the Locality box below.', 'pedestal' ),
            'serialize_data' => false,
        ] );

        $address->add_meta_box( esc_html__( 'Street Address', 'pedestal' ), [ 'pedestal_place' ], 'normal', 'high' );

    }

    private function register_locality_fields() {
        $locality_type = new \Fieldmanager_Select( false, [
            'name'           => 'locality_type',
            'description'    => esc_html__( 'Select the type of Locality.', 'pedestal' ),
            'first_empty'    => true,
            'datasource'     => new \Fieldmanager_Datasource_Term( [
                'taxonomy'                    => 'pedestal_locality_type',
                'taxonomy_hierarchical'       => true,
                'taxonomy_hierarchical_depth' => 0,
                'append_taxonomy'             => false,
                'taxonomy_save_to_terms'      => true,
            ] ),
        ] );
        $locality_type->add_meta_box( esc_html__( 'Locality Type', 'pedestal' ), [ 'pedestal_locality' ] );
    }

    /**
     * Register the fields for Neighborhoods
     */
    private function register_hood_fields() {

        $postcards = new \Fieldmanager_Media( [
            'name'               => 'postcard',
            'label'              => false,
            'description'        => esc_html__( 'Select a postcard image to associate with this Place. Keep in mind that a postcard is not necessarily your featured image.', 'pedestal' ),
            'button_label'       => esc_html__( 'Select a postcard image', 'pedestal' ),
            'modal_button_label' => esc_html__( 'Select postcard', 'pedestal' ),
            'modal_title'        => esc_html__( 'Choose postcard', 'pedestal' ),
            // 'display_if'         => [
            //     'src'   => 'locality_type',
            //     'value' => Taxonomies::get_locality_type_id( 'neighborhoods' ),
            // ],
        ] );
        $postcards->add_meta_box( esc_html__( 'Postcard', 'pedestal' ), [ 'pedestal_locality' ], 'normal', 'high' );

    }

    /**
     * Register fields for multiple clusters
     */
    private function register_cluster_fields() {

        $types = [];
        foreach ( self::get_cluster_post_types() as $post_type ) {
            if ( ! post_type_supports( $post_type, 'editor' ) ) {
                $types[] = $post_type;
            }
        }

        $description = new \Fieldmanager_RichTextArea( false, [
            'name'     => 'description',
            'required' => false,
        ] );
        $description->add_meta_box( esc_html__( 'Description', 'pedestal' ), $types, 'normal', 'default' );

    }

    /**
     * Register a skeleton field group for cluster/entity connections
     *
     * Post to post connection metaboxes are moved to the appropriate tabs via
     * JavaScript.
     */
    private function register_cluster_connection_fields() {

        // Standard cluster groups
        $group_entities_to_clusters = new \Fieldmanager_Group( '', [
            'name'        => 'pedestal_entities_to_clusters_connections',
            'tabbed'      => true,
        ] );
        $group_stories_to_clusters = new \Fieldmanager_Group( '', [
            'name'        => 'pedestal_stories_to_clusters_connections',
            'tabbed'      => true,
        ] );

        // Locality cluster groups
        $group_entities_to_localities = new \Fieldmanager_Group( '', [
            'name'        => 'pedestal_entities_to_localities_connections',
            'tabbed'      => true,
        ] );
        $group_stories_to_localities = new \Fieldmanager_Group( '', [
            'name'        => 'pedestal_stories_to_localities_connections',
            'tabbed'      => true,
        ] );

        foreach ( self::get_cluster_post_types( false ) as $post_type ) {
            $labels = self::get_post_type_labels( $post_type );
            $sanitized_labels = self::get_sanitized_post_type_labels( $post_type );
            $child = new \Fieldmanager_Group( esc_html__( $labels['name'], 'pedestal' ), [
                'name' => $sanitized_labels['name'],
            ] );

            // Add clusters to entity box
            $group_entities_to_clusters->add_child( $child );

            // Add non-identical clusters to some clusters
            if ( ! self::is_story( $post_type ) ) {
                $group_stories_to_clusters->add_child( $child );
            }
        }

        // Add standard Cluster boxes
        $group_entities_to_clusters->add_meta_box( esc_html__( 'Clusters', 'pedestal' ), self::get_entity_post_types(), 'normal', 'default' );
        $group_stories_to_clusters->add_meta_box( esc_html__( 'Clusters', 'pedestal' ), 'pedestal_story', 'normal', 'default' );
    }

    /**
     * Render the metabox for indeterminate Geospace connections
     */
    public function render_meta_box_p2p_connections_geospaces_to_geospaces( $post ) {
        $geospace_id = $post->ID;
        $geospace = Geospace::get_by_post_id( $geospace_id );
        $context = [
            'geospace'  => $geospace,
            'connected' => $geospace->get_connected_geospaces_passive(),
        ];
        ob_start();
        Timber::render( 'partials/admin/metabox-p2p-geospaces-to-geospaces.twig', $context );
        echo ob_get_clean();
    }
}
