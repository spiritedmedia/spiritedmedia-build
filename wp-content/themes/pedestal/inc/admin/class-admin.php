<?php

namespace Pedestal\Admin;

use function Pedestal\Pedestal;

use Timber\Timber;

use \Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

use \Pedestal\Posts\Entities\Embed;

use \Pedestal\Posts\Attachment;

use \Pedestal\Posts\Clusters\Geospaces\Localities\Neighborhood;

use \Pedestal\Posts\Clusters\Story;

use \Pedestal\Posts\Clusters\Person;

use Pedestal\Posts\Slots\Slots;

use Pedestal\Objects\Stream;

/**
 * Encapsulates customizations for the WordPress admin
 */
class Admin {

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Admin;
            self::$instance->load();
        }
        return self::$instance;

    }

    /**
     * Load code for the admin
     */
    private function load() {

        $this->setup_actions();
        $this->setup_filters();

    }

    /**
     * Set up admin filters
     */
    private function setup_actions() {

        // Needs to happen after post types are registered
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
        add_action( 'admin_enqueue_scripts', [ $this, 'action_admin_enqueue_scripts' ] );
        add_action( 'admin_head', [ $this, 'action_admin_head' ] );
        add_action( 'admin_menu', [ $this, 'action_admin_menu_late' ], 100 );

        add_action( 'fm_user', [ $this, 'action_user_fields' ] );

        add_action( 'save_post', function( $post_id, $post, $update ) {
            switch ( $post_type = get_post_type( $post_id ) ) {
                case 'pedestal_embed':
                    $embed = new Embed( $post_id );
                    $embed->update_embed_data();
                    break;
                case 'pedestal_story':
                    $story = new Story( $post_id );
                    if ( $story->has_story_branding() && $story->has_story_bar_icon() ) {
                        $this->update_story_branding( $post_id );
                    }
                    break;
                case 'pedestal_person':
                    $person = new Person( $post_id );

                    if ( ! $person->get_title() ) {
                        $person->set_person_title();

                        // @TODO Note that this should only fire once upon new post
                        //     creation, but instead it fires every time the post is
                        //     updated. Ideally we handle this with the
                        //     `new_pedestal_person` hook or even the `$update`
                        //     parameter in `save_post`, but neither of those are
                        //     working correctly.
                        $name = wp_unique_post_slug(
                            sanitize_title( $person->get_title() ),
                            $post_id,
                            $person->get_status(),
                            $post_type,
                            $person->get_parent_id()
                        );
                        $person->set_name( $name );
                    }
                    break;
            }
        }, 100, 3 );

        add_action( 'admin_notices', [ $this, 'action_admin_notice_maintenance_mode' ] );
        add_action( 'admin_notices', [ $this, 'action_admin_notice_excerpt_required' ] );
        add_action( 'admin_notices', [ $this, 'action_admin_notice_locality_type_required' ] );
        add_action( 'admin_notices', [ $this, 'action_admin_notice_mandrill_failure' ] );
        add_action( 'admin_notices', [ $this, 'action_admin_notice_unembeddable_url' ] );
        add_action( 'admin_notices', [ $this, 'action_admin_notice_slot_item_defaults_missing' ] );

        add_action( 'add_attachment', [ $this, 'update_svg_fallback' ] );
        add_action( 'edit_attachment', [ $this, 'update_svg_fallback' ] );
        add_action( 'delete_attachment', [ $this, 'delete_svg_fallback' ] );

        // Set up kses for all user roles
        remove_action( 'init', 'kses_init' );
        remove_action( 'set_current_user', 'kses_init' );
        add_action( 'init', [ $this, 'action_kses_init' ] );
        add_action( 'set_current_user', [ $this, 'action_kses_init' ] );

        // Modify dashboard widgets
        add_action( 'wp_dashboard_setup', function() {
            // Remove the Activity widget
            remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );

            // Add Scheduled Entities widget
            wp_add_dashboard_widget(
                'pedestal_scheduled_entities',
                'Scheduled Entities',
                [ $this, 'handle_dashboard_widget_scheduled_entities' ]
            );
        });
    }

    /**
     * Set up admin filters
     */
    private function setup_filters() {

        add_filter( 'fm_element_markup_start', [ $this, 'filter_fm_element_markup_start' ], 10, 2 );
        add_filter( 'wp_insert_post_data', [ $this, 'filter_wp_insert_post_data' ], 10, 2 );
        add_filter( 'gettext', [ $this, 'filter_gettext_publish_button' ], 10, 2 );
        add_filter( 'tiny_mce_before_init', [ $this, 'filter_tiny_mce_before_init' ] );

        if ( current_user_can( 'manage_uploads' ) ) {
            add_filter( 'attachment_fields_to_edit', [ $this, 'filter_attachment_fields_to_edit' ], 10, 2 );
            add_filter( 'attachment_fields_to_save', [ $this, 'filter_attachment_fields_to_save' ], 10, 2 );
        }

        // Filter the post titles in FM Post Datasource results
        add_filter( 'fm_datasource_post_title', function( $title, $post ) {
            if ( isset( $post->post_type ) ) {
                $type = Types::get_post_type_name( $post->post_type, $plurals = false );
                if ( 'pedestal_locality' === $post->post_type ) {
                    $locality = Post::get_by_post_id( $post->ID );
                    $type = $locality->get_locality_type_name();
                }
                $title .= ' (' . $type . ')';
            }
            if ( isset( $post->post_status ) && 'future' == $post->post_status ) {
                $title = '— Scheduled: ' . $title . ' — ' . date( 'M, d Y g:ia', strtotime( $post->post_date ) );
            }
            return $title;
        }, 10, 2 );

        // Highlight the proper parent menu item for submenu items that have been moved around
        add_filter( 'parent_file', function( $parent_file ) {
            global $pagenow;
            if ( ! empty( $_GET['taxonomy'] ) && 'edit-tags.php' == $pagenow ) {
                if ( 'pedestal_subscriptions' == $_GET['taxonomy']  ) {
                    $parent_file = 'users.php';
                }
                if ( 'pedestal_slot_item_type' == $_GET['taxonomy']  ) {
                    $parent_file = 'slots';
                }
            }
            return $parent_file;
        } );

        add_filter( 'user_contactmethods', function( $methods ) {
            // If the User's public email address needs to be different than the
            // address registered to their account, then this field can be used
            // to override.
            $methods['public_email'] = esc_html__( 'Public Email', 'pedestal' );
            $methods['twitter_username'] = esc_html__( 'Twitter Username', 'pedestal' );
            $methods['facebook_profile'] = esc_html__( 'Facebook Profile', 'pedestal' );
            return $methods;
        });

        // Remove default avatars
        add_filter( 'avatar_defaults', function ( $avatar_defaults ) {
            $avatar_url = '404';
            $avatar_defaults[ $avatar_url ] = 'No Avatar';
            return $avatar_defaults;
        } );

        // Enable SVG upload
        add_filter('upload_mimes', function( $mimes ) {
            $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        });

        // Fix the SVG icon size
        add_action('admin_head', function() {
            echo '<style type="text/css">
                    .fm-icon .thumbnail {
                          width: 50px;
                          height: 50px;
                     }
                 </style>';
        });

        // Disable TinyMCE if the post content contains a SoundCite shortcode
        add_filter( 'user_can_richedit', function( $can ) {
            global $post;
            if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'soundcite' ) ) {
                return false;
            }
            return $can;
        } );

        // Load custom TinyMCE buttons`
        add_filter( 'mce_buttons', function( $buttons ) {
            array_push( $buttons, 'insertPostElement' );
            return $buttons;
        });

        // Load additional TinyMCE plugins
        add_filter( 'mce_external_plugins', function( $plugins ) {
            $plugin_array['Pedestal'] = get_template_directory_uri() . '/assets/js/custom-tinymce-buttons.js';
            return $plugin_array;
        });

        // Modify which tinyMCE buttons we show
        add_filter( 'mce_buttons_2', function( $buttons ) {
            array_unshift( $buttons, 'styleselect' );
            $buttons_to_remove = [ 'alignjustify', 'forecolor', 'charmap' ];
            foreach ( $buttons as $index => $button ) {
                if ( in_array( $button, $buttons_to_remove ) ) {
                    unset( $buttons[ $index ] );
                }
            }
	        return $buttons;
        });

        add_filter( 'wp_kses_allowed_html', function( $allowed_tags, $context ) {
            if ( isset( $allowed_tags['span'] ) ) {
                unset( $allowed_tags['span'] );
            }
            foreach ( $allowed_tags as $tag => $attrs ) {
                $allowed_tags[ $tag ]['style'] = false;
                $allowed_tags[ $tag ]['dir'] = false;
            }
            return $allowed_tags;
        }, 10, 2 );
    }

    /**
     * Register Fieldmanager fields
     */
    public function action_init_after_post_types_registered() {

        $this->register_distribution_fields();
        $this->register_maintenance_mode_fields();
        $this->register_spotlight_fields();
        $this->register_pinned_entity_fields();

    }

    /**
     * What to do in the admin head
     */
    public function action_admin_head() {
        global $wp_meta_boxes;

        $screen = get_current_screen();
        if ( 'nav-menus' == $screen->id ) {
            $valid_meta_boxes = [
                'add-page',
                'add-custom-links',
                'add-post-type-pedestal_story',
            ];

            foreach ( $wp_meta_boxes['nav-menus']['side']['core'] as $key => $data ) {
                if ( ! in_array( $key, $valid_meta_boxes ) ) {
                    unset( $wp_meta_boxes['nav-menus']['side']['core'][ $key ] );
                }
            }

            foreach ( $wp_meta_boxes['nav-menus']['side']['default'] as $key => $data ) {
                if ( ! in_array( $key, $valid_meta_boxes ) ) {
                    unset( $wp_meta_boxes['nav-menus']['side']['default'][ $key ] );
                }
            }

            add_filter( 'hidden_meta_boxes', '__return_empty_array' );

        }

    }

    /**
     * Customizations to the admin menu
     */
    public function action_admin_menu_late() {
        global $menu;

        // Hide menus we don't use
        unset( $menu[5] ); // posts
        unset( $menu[25] ); // comments

        // Add some new menus
        add_menu_page( 'Slots', 'Slots', 'edit_slots', 'slots', '', 'dashicons-businessman', 21 );

        // Remove some meta boxes
        remove_meta_box( 'pedestal_locality_typediv', 'pedestal_locality', 'side' );
        remove_meta_box( 'pedestal_slot_item_typediv', 'pedestal_slot_item', 'side' );
    }

    /**
     * Set up kses for all user roles, not just those who can't unfiltered_html
     */
    public function action_kses_init() {
        kses_remove_filters();
        kses_init_filters();
    }

    /**
     * Scripts and styles for the admin
     */
    public function action_admin_enqueue_scripts() {
        wp_enqueue_style( 'pedestal-admin', get_template_directory_uri() . '/assets/dist/css/admin.css', [], PEDESTAL_VERSION );
        wp_enqueue_script( 'pedestal-admin', get_template_directory_uri() . '/assets/dist/js/admin.js', [], PEDESTAL_VERSION );
    }

    /**
     * Add fields to the User profile
     */
    public function action_user_fields() {
        global $wpdb;

        $title = new \Fieldmanager_TextField( [
            'name' => 'user_title',
        ] );

        $bio_short = new \Fieldmanager_TextArea( [
            'name' => 'user_bio_short',
        ] );

        $bio_extended = new \Fieldmanager_RichTextArea( [
            'name'      => 'user_bio_extended',
            'buttons_1' => [
                'bold',
                'italic',
                'bullist',
                'numlist',
                'link',
            ],
            'editor_settings' => [
                'quicktags'     => false,
                'media_buttons' => false,
                'editor_height' => 300,
            ],
        ] );

        // We need a dynamic field name because the value is site specifc
        $img_name = $wpdb->prefix . 'user_img';
        $img = new \Fieldmanager_Media( [
            'name'               => $img_name,
            'button_label'       => 'Add Image',
            'modal_title'        => 'Select Image',
            'modal_button_label' => 'Use Image as User Image',
            'preview_size'       => 'thumbnail',
        ] );

        $title->add_user_form( 'Position Title' );
        $bio_short->add_user_form( 'Description' );
        $bio_extended->add_user_form( 'Extended Bio' );
        $img->add_user_form( 'User Image' );

    }

    /**
     * Display admin notice if maintenance mode is enabled
     */
    public function action_admin_notice_maintenance_mode() {
        $options = get_option( 'pedestal_maintenance_mode' );
        if ( ! empty( $options['enabled'] ) ) {
            $msg = '<p>Maintenance mode is active. Please don\'t forget to <a href="options-general.php?page=pedestal_maintenance_mode">deactivate it</a> as soon as you are done.</p>';
            echo '<div class="updated fade">' . $msg . '</div>';
        }
    }

    /**
     * Display admin notice if the post excerpt is missing
     */
    public function action_admin_notice_excerpt_required() {
        $message = 'Excerpt is required to publish an article.';
        self::handle_admin_notice_error( 'excerpt_required', $message );
    }

    /**
     * Display admin notice if the Locality Type is not set
     */
    public function action_admin_notice_locality_type_required() {
        $message = 'Please set the Locality Type in the field below!';
        self::handle_admin_notice_error( 'locality_type_required', $message );
    }

    /**
     * Display admin notice if the Mandrill API responds with a failure code
     */
    public function action_admin_notice_mandrill_failure() {
        $message = 'Mandrill API responded with error code {GET_VAR}. Email still may have sent. Check API logs.';
        self::handle_admin_notice_error( 'mandrill_resp', $message );
    }

    /**
     * Display admin notice for unembeddable Embed URLs
     */
    public function action_admin_notice_unembeddable_url() {
        $add_link_url = admin_url( '/post-new.php?post_type=pedestal_link' );
        $message = sprintf( 'You\'ve entered an invalid embed URL. Perhaps you meant to <a href="%s">post a Link</a>?', $add_link_url );
        self::handle_admin_notice_error( 'unembeddable_url', $message );
    }

    /**
     * Display admin notice if any required Slot Item Placement defaults are missing
     */
    public function action_admin_notice_slot_item_defaults_missing() {
        $message = 'You are missing required default values in the Slot Placement Defaults!';
        self::handle_admin_notice_error( 'slot_item_defaults_missing', $message );
    }

    /**
     * Filter markup to include placeholders specific to this post
     */
    public function filter_fm_element_markup_start( $out, $fm ) {

        $screen = get_current_screen();
        if ( 'post' !== $screen->base ) {
            return $out;
        }

        $post = \Pedestal\Posts\Post::get_by_post_id( get_the_ID() );
        if ( ! $post ) {
            return $out;
        }

        $fm_tree = $fm->get_form_tree();
        array_pop( $fm_tree );
        $parent = array_pop( $fm_tree );

        if ( $parent ) {

            if ( 'facebook' === $parent->name ) {
                $placeholders = [
                    'title'        => $post->get_default_facebook_open_graph_tag( 'title' ),
                    'description'  => $post->get_default_facebook_open_graph_tag( 'description' ),
                ];
            } elseif ( 'twitter' === $parent->name ) {
                $placeholders = [
                    'title'        => $post->get_default_twitter_card_tag( 'title' ),
                    'description'  => $post->get_default_twitter_card_tag( 'description' ),
                ];
            } elseif ( 'seo' === $parent->name ) {
                $placeholders = [
                    'title'        => $post->get_default_seo_title(),
                    'description'  => $post->get_default_seo_description(),
                ];
            }

            if ( isset( $placeholders[ $fm->name ] ) ) {
                $fm->attributes['placeholder'] = $placeholders[ $fm->name ];
            }
        }

        return $out;
    }

    /**
     * Set up TinyMCE
     */
    public function filter_tiny_mce_before_init( $arr ) {
        $post_id = get_queried_object_id();
        if ( 'pedestal_newsletter' !== get_post_type( $post_id ) ) {
            // Limit suggested formats
            $arr['block_formats'] = 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3';
        }

        // Set-up custom CSS classes to add to the Formats dropwdown
        $style_formats = [
            [
                'title' => 'Site Color',
                'selector' => '*', // Add this class to every element
                'classes' => 'u-text-color-primary',
            ],
        ];
        $arr['style_formats'] = json_encode( $style_formats );
        return $arr;
    }

    /**
     * Filter attachment fields available to edit
     */
    public function filter_attachment_fields_to_edit( $fields, $post ) {
        $metadata = wp_get_attachment_metadata( $post->ID );
        if ( ! empty( $metadata['image_meta'] ) ) {
            // @TODO clean up ternaries
            $credit = ( empty( $metadata['image_meta']['credit'] ) )
                ? ''
                : $metadata['image_meta']['credit'];
            $credit_link = ( empty( $metadata['image_meta']['credit_link'] ) )
                ? ''
                : $metadata['image_meta']['credit_link'];

            $fields['pedestal_credit'] = [
                'label'              => esc_html__( 'Credit', 'pedestal' ),
                'input'              => 'text',
                'value'              => $credit,
            ];
            $fields['pedestal_credit_link'] = [
                'label'              => esc_html__( 'Credit Link', 'pedestal' ),
                'input'              => 'url',
                'value'              => $credit_link,
            ];
        }

        return $fields;
    }

    /**
     * Filter attachment fields as they are saved
     */
    public function filter_attachment_fields_to_save( $data, $attachment ) {
        $metadata = wp_get_attachment_metadata( $data['ID'] );
        if ( ! empty( $metadata['image_meta'] ) ) {
            if ( isset( $attachment['pedestal_credit'] ) ) {
                $metadata['image_meta']['credit'] = sanitize_text_field( $attachment['pedestal_credit'] );
            }
            if ( isset( $attachment['pedestal_credit_link'] ) ) {
                $metadata['image_meta']['credit_link'] = esc_url( $attachment['pedestal_credit_link'] );
            }
            wp_update_attachment_metadata( $data['ID'], $metadata );
        }
        return $data;
    }

    /**
     * Filter the text of the post Publish button
     */
    public function filter_gettext_publish_button( $translation, $text ) {
        if ( 'Publish' !== $text ) {
            return $translation;
        }

        // We need to account for the var postL10n JavaScript variable translation
        // as well and `get_post_type()` returns null during that context.
        $post_type = get_post_type();
        if ( ! $post_type && isset( $_GET['post'] ) ) {
            $post_type = get_post_type( $_GET['post'] );
        }
        if ( ! $post_type && isset( $_GET['post_type'] ) ) {
            $post_type = $_GET['post_type'];
        }
        if ( 'pedestal_newsletter' !== $post_type ) {
            return $translation;
        }

        $new_text = 'Send Newsletter';
        // If the trash is disabled we need to use a shorter label
        // 'Move to Trash' changes to 'Delete Permanently' and there is less space
        if ( ! EMPTY_TRASH_DAYS ) {
            $new_text = 'Send';
        }

        return $new_text;
    }

    /**
     * Handle the display of an admin error notice based on a GET variable
     *
     * Value of the GET variable can be displayed in the $message with the
     * placeholder string `{GET_VAR}`.
     *
     * @param  string $get_var Name of a GET variable
     * @param  string $message Message to display
     */
    public static function handle_admin_notice_error( $get_var, $message ) {
        if ( ! isset( $_GET[ $get_var ] ) ) {
            return;
        }
        $get_var = $_GET[ $get_var ];
        $message = str_replace( '{GET_VAR}', $get_var, $message );
        if ( is_numeric( $get_var ) ) {
            switch ( absint( $get_var ) ) {
                case 1:
                    $message = '<strong>Post saved as draft!</strong> ' . $message;
                    break;
                default:
                    $message = 'Unexpected error. ¯\_(ツ)_/¯';
                    break;
            }
        }
        echo '<div id="notice" class="error"><p>' . $message . '</p></div>';
    }

    public function filter_wp_insert_post_data( $data, $postarr ) {
        $redirect_arg = '';

        if ( ! empty( $data['post_status'] ) && 'auto-draft' === $data['post_status'] ) {
            return $data;
        }

        switch ( $data['post_type'] ) {
            case 'pedestal_embed':
                $url = $postarr['embed_url'];
                if ( empty( $url ) || ! Embed::get_embed_type_from_url( $url ) ) {
                    $redirect_arg = 'unembeddable_url';
                }
                break;
            case 'pedestal_locality':
                if ( empty( $postarr['locality_type'] ) ) {
                    $redirect_arg = 'locality_type_required';
                }
                break;
            case 'pedestal_slot_item':
                if ( empty( Slots::is_required_data_set_up( $postarr ) ) ) {
                    $redirect_arg = 'slot_item_defaults_missing';
                }
                break;
        }

        // Require excerpt for all editorial post types
        if ( in_array( $data['post_type'], Types::get_editorial_post_types() )
            && empty( $data['post_excerpt'] ) ) {
            $redirect_arg = 'excerpt_required';
        }

        $redirect_post_location_statuses = [ 'future', 'publish' ];
        if ( ! empty( $redirect_arg ) && in_array( $data['post_status'], $redirect_post_location_statuses ) ) {
            add_filter( 'redirect_post_location', function( $location ) use ( $redirect_arg ) {
                remove_filter( 'redirect_post_location', __FILTER__, '99' );
                return add_query_arg( $redirect_arg, 1, remove_query_arg( 'message', $location ) );
            }, '99');
            $data['post_status'] = 'draft';
        }

        return $data;
    }

    /**
     * Register fields for the "Spotlight"
     */
    private function register_spotlight_fields() {

        $fm_spotlight = new \Fieldmanager_Group( false, [
            'name'       => 'pedestal_spotlight',
            'children'   => [
                'enabled' => new \Fieldmanager_Radios( false, [
                    'name'              => 'enabled',
                    'default_value'     => 0,
                    'options'           => [
                        1               => esc_html__( 'On', 'pedestal' ),
                        0               => esc_html__( 'Off', 'pedestal' ),
                    ],
                    'sanitize'          => 'intval',
                ] ),
                'label'  => new \Fieldmanager_TextField( esc_html__( 'Label', 'pedestal' ), [
                    'name'              => 'label',
                    'default_value'     => 'Breaking',
                ] ),
                'content'     => new \Fieldmanager_Autocomplete( esc_html__( 'Story or Entity', 'pedestal' ), [
                    'name'             => 'content',
                    'description'      => esc_html__( 'Leave blank to display most recent original content', 'pedestal' ),
                    'attributes'       => [
                        'placeholder'  => esc_html__( 'Search by title', 'pedestal' ),
                        'size'         => 50,
                    ],
                    'datasource'       => new \Fieldmanager_Datasource_Post( [
                        'query_args'        => [
                            'post_type'     => Types::get_post_types(),
                        ],
                    ] ),
                ] ),
            ],
        ] );
        $fm_spotlight->add_submenu_page( 'themes.php', esc_html__( 'Spotlight Settings', 'pedestal' ), esc_html__( 'Spotlight', 'pedestal' ), 'manage_spotlight' );

    }

    private function register_pinned_entity_fields() {

        $label_enabled = esc_html__( 'Currently this only affects the home stream.', 'pedestal' );
        $fm_pinned = new \Fieldmanager_Group( false, [
            'name'       => 'pedestal_pinned',
            'children'   => [
                'enabled' => new \Fieldmanager_Radios( $label_enabled, [
                    'name'              => 'enabled',
                    'default_value'     => 0,
                    'options'           => [
                        1               => esc_html__( 'On', 'pedestal' ),
                        0               => esc_html__( 'Off', 'pedestal' ),
                    ],
                    'sanitize'          => 'intval',
                ] ),
                'content'     => new \Fieldmanager_Autocomplete( esc_html__( 'Entity', 'pedestal' ), [
                    'name'             => 'content',
                    'attributes'       => [
                        'placeholder'  => esc_html__( 'Search by title', 'pedestal' ),
                        'size'         => 50,
                    ],
                    'datasource'       => new \Fieldmanager_Datasource_Post( [
                        'query_args'        => [
                            'post_type'     => Types::get_entity_post_types(),
                        ],
                    ] ),
                ] ),
            ],
        ] );
        $fm_pinned->add_submenu_page( 'themes.php', esc_html__( 'Pinned Entity Settings', 'pedestal' ), esc_html__( 'Pinned', 'pedestal' ), 'manage_pinned' );

    }

    /**
     * Register fields for maintenance mode
     */
    private function register_maintenance_mode_fields() {
        $fm_spotlight = new \Fieldmanager_Group( false, [
            'name'       => 'pedestal_maintenance_mode',
            'children'   => [
                'enabled' => new \Fieldmanager_Radios( false, [
                    'name'              => 'enabled',
                    'default_value'     => 0,
                    'options'           => [
                        1               => esc_html__( 'On', 'pedestal' ),
                        0               => esc_html__( 'Off', 'pedestal' ),
                    ],
                    'sanitize'          => 'intval',
                ] ),
            ],
        ] );
        $fm_spotlight->add_submenu_page( 'options-general.php', esc_html__( 'Maintenance Mode', 'pedestal' ), esc_html__( 'Maintenance Mode', 'pedestal' ), 'manage_network' );
    }

    /**
     * Register distribution meta box fields
     */
    private function register_distribution_fields() {

        /**
         * Distribution settings
         */
        $meta_group = new \Fieldmanager_Group( '', [
            'name'        => 'pedestal_distribution',
            'tabbed'      => true,
        ] );

        // Can't use $fm_group->add_child(): https://github.com/alleyinteractive/wordpress-fieldmanager/pull/172
        $meta_group->children['twitter'] = new \Fieldmanager_Group( esc_html__( 'Twitter', 'pedestal' ), [
            'name'                    => 'twitter',
            'children'                => [
                'share_text'          => new \Fieldmanager_TextArea( esc_html__( 'Share Text', 'pedestal' ), [
                    'description'     => esc_html__( 'What text would you like the user to include in their tweet? (Defaults to title and shortlink)', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                    ],
                ] ),
                'title'               => new \Fieldmanager_TextField( esc_html__( 'Title', 'pedestal' ), [
                    'description'     => esc_html__( 'Title should be concise and will be truncated at 70 characters.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                        'maxlength'       => 70,
                    ],
                ] ),
                'description'         => new \Fieldmanager_TextArea( esc_html__( 'Description', 'pedestal' ), [
                    'description'     => esc_html__( 'Description text will be truncated at the word to 200 characters.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                        'maxlength'       => 200,
                        'rows'            => 3,
                    ],
                ] ),
                'image'               => new \Fieldmanager_Media( esc_html__( 'Image', 'pedestal' ), [
                    'description'     => esc_html__( 'Override the featured image with an image specific to Twitter. The image must be a minimum size of 120x120px. Images larger than 120x120px will be resized and cropped square based on its longest dimension.', 'pedestal' ),
                    'button_label'    => esc_html__( 'Select an image', 'pedestal' ),
                    'modal_button_label' => esc_html__( 'Select image', 'pedestal' ),
                    'modal_title'     => esc_html__( 'Choose image', 'pedestal' ),
                ] ),
            ],
        ] );
        $meta_group->children['facebook'] = new \Fieldmanager_Group( esc_html__( 'Facebook Open Graph', 'pedestal' ), [
            'name'                    => 'facebook',
            'children'                => [
                'title'               => new \Fieldmanager_TextField( esc_html__( 'Title', 'pedestal' ), [
                    'description'     => esc_html__( 'The title of your article, excluding any branding.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                    ],
                ] ),
                'description'         => new \Fieldmanager_TextArea( esc_html__( 'Description', 'pedestal' ), [
                    'description'     => esc_html__( 'A detailed description of the piece of content, usually between 2 and 4 sentences.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                        'rows'            => 4,
                    ],
                ] ),
                'image'               => new \Fieldmanager_Media( esc_html__( 'Image', 'pedestal' ), [
                    'description'     => esc_html__( 'Override the featured image with an image specific to Facebook. We suggest that you use an image of at least 1200x630 pixels.', 'pedestal' ),
                    'button_label'    => esc_html__( 'Select an image', 'pedestal' ),
                    'modal_button_label' => esc_html__( 'Select image', 'pedestal' ),
                    'modal_title'     => esc_html__( 'Choose image', 'pedestal' ),
                ] ),
            ],
        ] );
        $meta_group->children['linkedin'] = new \Fieldmanager_Group( esc_html__( 'LinkedIn', 'pedestal' ), [
            'name'                    => 'linkedin',
            'children'                => [
                'title'               => new \Fieldmanager_TextField( esc_html__( 'Title', 'pedestal' ), [
                    'description'     => esc_html__( 'The title of your article, excluding any branding. Max 200 characters.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                    ],
                ] ),
                'summary'         => new \Fieldmanager_TextArea( esc_html__( 'Summary', 'pedestal' ), [
                    'description'     => esc_html__( 'A detailed description of the piece of content, usually between 2 and 4 sentences. Longer titles will be truncated gracefully with ellipses.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                        'rows'            => 4,
                    ],
                ] ),
            ],
        ] );

        $seo_group = new \Fieldmanager_Group( esc_html__( 'SEO', 'pedestal' ), [
            'name'        => 'seo',
            'children'                => [
                'title'          => new \Fieldmanager_TextField( esc_html__( 'Title', 'pedestal' ), [
                    'description'     => esc_html__( 'Suggested length of up to 60 characters.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                    ],
                ] ),
                'description'         => new \Fieldmanager_TextArea( esc_html__( 'Description', 'pedestal' ), [
                    'description'     => esc_html__( 'Suggested length of up to 150 characters. Defaults to excerpt.', 'pedestal' ),
                    'attributes'      => [
                        'style'           => 'width:100%',
                        'rows'            => 2,
                    ],
                ] ),
            ],
        ] );
        $meta_group->children['seo'] = $seo_group;

        $distributable_post_types = get_post_types( [
            'public'   => true,
            '_builtin' => false,
        ] );

        if ( current_user_can( 'manage_distribution' ) ) {
            $meta_group->add_meta_box( esc_html__( 'Distribution', 'pedestal' ), $distributable_post_types, 'advanced', 'low' );
        }
    }

    /**
     * Save a PNG fallback for SVG.
     *
     * @link http://eperal.com/automatically-generate-png-images-from-uploaded-svg-images-in-wordpress/
     *
     * @param int    $attachment_id
     * @param string $color         Hex foreground color
     */
    public function save_svg_fallback( $attachment_id, $color ) {
        $attachment_src = get_attached_file( $attachment_id );
        $fallback_src = str_replace( '.svg','.png', $attachment_src );

        // Load the re-colorized SVG into memory
        $svg = $this->recolor_svg( $attachment_id, $color );

        // Create PNG from SVG
        $im = new \Imagick();
        $im->setBackgroundColor( new \ImagickPixel( 'transparent' ) );
        $im->readImageBlob( $svg );
        $im->trimImage( 0 );
        $im->setImageFormat( 'png24' );
        $im->resizeImage( 25, 25, \Imagick::FILTER_LANCZOS, 1 );  /*Optional, if you need to resize*/
        $im->writeImage( $fallback_src );
        $im->clear();
        $im->destroy();
    }

    /**
     * Save optimized SVG
     *
     * @param  SVG    $svg          An SVG file loaded into memory
     * @param  string $path         Path to save the SVG to
     * @param  string $fallback_url URL to load as fallback image
     */
    public function save_svg( $svg, $path, $fallback_url ) {
        // @codingStandardsIgnoreStart
        $dom = new \DOMDocument();
        $dom->loadXML( $svg );
        $dom->createAttributeNS( 'http://www.w3.org/1999/xlink', 'xmlns:xlink' );
        $dom->documentElement->removeAttribute( 'width' );
        $dom->documentElement->removeAttribute( 'height' );
        foreach ( $dom->getElementsByTagName( 'image' ) as $image ) {
            $image->parentNode->removeChild( $image );
        }
        $f = $dom->createDocumentFragment();
        $f->appendXML( "<image src='$fallback_url'></image>" );
        $dom->documentElement->appendChild( $f );
        file_put_contents( $path, $dom->saveXML() );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Change the SVG fill color based on ID
     *
     * @param  int    $attachment_id SVG attachment ID
     * @param  string $color         Hex color to replace with
     * @return SVG
     */
    public function recolor_svg( $attachment_id, $color ) {
        $attachment_src = get_attached_file( $attachment_id );
        $svg = Utils::file_get_contents_with_auth( $attachment_src );
        // The SVG must have the xml declaration.
        // Very hacky.
        if ( '?' != $svg[1] ) {
            $svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $svg;
        }
        // Search/replace 3- or 6-character hex color code
        return preg_replace( '/#(?:[0-9a-fA-F]{3}){1,2}\b/i', $color, $svg );
    }

    /**
     * Update story branding SVG and PNG icons based on color settings
     *
     * @param  int $story_id
     */
    public function update_story_branding( $story_id ) {
        $story = Story::get_by_post_id( $story_id );
        if ( $styles = $story->get_primary_story_branding() ) {
            $attachment_id = $story->get_icon_id();
            $fallback_url = str_replace( '.svg', '.png', wp_get_attachment_url( $attachment_id ) );

            $color = $styles['foreground_color'];
            $svg = $this->recolor_svg( $attachment_id, $color );
            $svg_path = get_attached_file( $attachment_id );

            $this->save_svg_fallback( $attachment_id, $color );
            $this->save_svg( $svg, $svg_path, $fallback_url );
        }
    }

    /**
     * When an attachment is uploaded, create svg fallback.
     *
     * @param int $attachment_id
     */
    public function update_svg_fallback( $attachment_id ) {
        $type = get_post_mime_type( $attachment_id );
        if ( 'image/svg+xml' == $type ) {
            $this->save_svg_fallback( $attachment_id, '#ffffff' );
        }
    }

    /**
     * Remove PNG fallback for SVG on attatchment delete.
     *
     * @link http://eperal.com/automatically-generate-png-images-from-uploaded-svg-images-in-wordpress/
     *
     * @param int $attachment_id
     */
    public function delete_svg_fallback( $attachment_id ) {
        $type = get_post_mime_type( $attachment_id );
        if ( 'image/svg+xml' == $type ) {
            $attachment_src = get_attached_file( $attachment_id ); // Gets path to attachment
            unlink( str_replace( '.svg','.png', $attachment_src ) );
        }
    }

    /**
     * Handle the display of the Scheduled Entities dashboard widget
     */
    public function handle_dashboard_widget_scheduled_entities() {
        $future_posts = Stream::get( [
            'post_type'      => Types::get_entity_post_types(),
            'posts_per_page' => 15,
            'post_status'    => 'future',
            'orderby'        => 'date',
            'order'          => 'ASC',
        ] );
        $context = array_merge( Timber::get_context(), [ 'items' => $future_posts ] );
        Timber::render( 'partials/admin/dash-widget-scheduled-entities.twig', $context );
    }
}
