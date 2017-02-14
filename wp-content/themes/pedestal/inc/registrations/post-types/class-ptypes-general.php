<?php

namespace Pedestal\Registrations\Post_Types;

use Pedestal\Utils\Utils;

class General_Types extends Types {

    /**
     * Newsletter item types
     */
    protected static $newsletter_item_types;

    protected $editorial_post_types = [];

    protected $post_types = [
        'pedestal_newsletter',
    ];

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new General_Types;
            self::$instance->setup_types();
            self::$instance->setup_item_types();
            self::$instance->setup_actions();
        }
        return self::$instance;

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
                'map_meta_cap'      => true,
            ];

            switch ( $post_type ) {

                case 'pedestal_newsletter':
                    $cap_manage = 'send_emails';

                    $singular = esc_html__( 'Newsletter', 'pedestal' );
                    $plural = esc_html__( 'Newsletters', 'pedestal' );
                    $class = 'Posts\\Newsletter';
                    $args['menu_position'] = 12;
                    $args['menu_icon'] = 'dashicons-email-alt';
                    $args['supports'] = [
                        'title',
                        'slots',
                    ];
                    $args['capabilities'] = [
                        'read'                   => 'read',
                        'edit_post'              => 'edit_newsletter',
                        'read_post'              => 'read_newsletter',
                        'delete_post'            => 'delete_newsletter',
                        'create_posts'           => $cap_manage,
                        'publish_posts'          => $cap_manage,
                        'edit_posts'             => $cap_manage,
                        'delete_posts'           => $cap_manage,
                        'edit_others_posts'      => $cap_manage,
                        'delete_others_posts'    => $cap_manage,
                        'edit_published_posts'   => $cap_manage,
                        'delete_published_posts' => $cap_manage,
                        'read_private_posts'     => $cap_manage,
                        'edit_private_posts'     => $cap_manage,
                        'delete_private_posts'   => $cap_manage,
                    ];
                    $args['rewrite'] = [
                        'slug' => 'newsletters',
                    ];
                    break;

            }

            $post_types[ $post_type ] = compact( 'singular', 'plural', 'class', 'args' );

        endforeach;

        $this->post_types = $post_types;

    }

    /**
     * Set up the Newsletter item types
     *
     * @return void
     */
    private function setup_item_types() {
        static::$newsletter_item_types = [
            'post'    => esc_html__( 'Post', 'pedestal' ),
            'heading' => esc_html__( 'Heading', 'pedestal' ),
            'heading_likes' => esc_html__( sprintf( 'Heading: %s Likes', PEDESTAL_BLOG_NAME ), 'pedestal' ),
            'heading_event_feat' => esc_html__( 'Heading: Featured Event', 'pedestal' ),
            'heading_on_calendar' => esc_html__( 'Heading: On the Calendar', 'pedestal' ),
        ];
    }

    /**
     * Setup actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
    }

    /**
     * Register newsletter fields
     */
    public function action_init_after_post_types_registered() {
        $items = new \Fieldmanager_Group( esc_html__( 'Item', 'pedestal' ), [
            'name'           => 'newsletter_items',
            'limit'          => 0,
            'save_empty'     => false,
            'extra_elements' => 0,
            'sortable'       => true,
            'save_empty'     => false,
            'extra_elements' => 0,
            'add_more_label' => esc_html__( 'Add Item', 'pedestal' ),
            'collapsible'    => true,
            'collapsed'      => true,
            'label_macro'    => [
                '%s',
                'type',
            ],
            'children'       => [
                'type' => new \Fieldmanager_Select( esc_html__( 'Type', 'pedestal' ), [
                    'options' => static::get_newsletter_item_types(),
                    'default_value'       => 'post',
                    'first_empty'         => false,
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                ] ),
                'post' => new \Fieldmanager_Autocomplete( esc_html__( 'Post Selection (Required)', 'pedestal' ), [
                    'name'        => 'post',
                    'description' => esc_html__( 'Select an Entity', 'pedestal' ),
                    'display_if'  => [
                        'src'   => 'type',
                        'value' => 'post',
                    ],
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                    'show_edit_link'      => true,
                    'datasource'          => new \Fieldmanager_Datasource_Post( [
                        'query_args' => [
                            'post_type'      => Types::get_entity_post_types(),
                            'posts_per_page' => 1000,
                        ],
                    ] ),

                ] ),
                'post_title'     => new \Fieldmanager_Textfield( esc_html__( 'Title (Optional)', 'pedestal' ), [
                    'name'        => 'post_title',
                    'description' => esc_html__( 'Customize the display title. Defaults to the Entity\'s original title. Not used for Events.', 'pedestal' ),
                    'display_if'  => [
                        'src'   => 'type',
                        'value' => 'post',
                    ],
                ] ),
                'url' => new \Fieldmanager_Link( esc_html__( 'Title Link (Optional)', 'pedestal' ), [
                    'name' => 'url',
                    'description' => esc_html__( 'Customize the URL the item title links to. Defaults to the Entity\'s original permalink. Not used for Events.', 'pedestal' ),
                    'display_if'  => [
                        'src'   => 'type',
                        'value' => 'post',
                    ],
                ] ),
                'description' => new \Fieldmanager_RichTextArea( esc_html__( 'Description', 'pedestal' ), [
                    'name'        => 'description',
                    'description' => esc_html__( 'Customize the blurb. Not used for Events.', 'pedestal' ),
                    'display_if'  => [
                        'src'   => 'type',
                        'value' => 'post',
                    ],
                    'editor_settings' => [
                        'teeny'         => true,
                        'media_buttons' => false,
                        'editor_height' => 300,
                    ],
                ] ),
                'heading_title'     => new \Fieldmanager_Textfield( esc_html__( 'Heading Text', 'pedestal' ), [
                    'name'        => 'heading_title',
                    'display_if'  => [
                        'src'   => 'type',
                        'value' => 'heading',
                    ],
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                ] ),
            ],
        ] );
        $items->add_meta_box( esc_html__( 'Newsletter Items', 'pedestal' ), [ 'pedestal_newsletter' ], 'normal', 'high' );

        if ( current_user_can( 'send_emails' ) ) {
            $options = new \Fieldmanager_Checkbox( esc_html__( 'This newsletter is part of a test', 'pedestal' ), [
                'name' => 'newsletter_is_test',
            ] );
            $options->add_meta_box( esc_html__( 'Newsletter Options', 'pedestal' ), [ 'pedestal_newsletter' ], 'side', 'low' );
        }
    }

    /**
     * Get the Newsletter item types
     *
     * @return array
     */
    public static function get_newsletter_item_types() {
        return static::$newsletter_item_types;
    }
}
