<?php

namespace Pedestal\Admin;

use function Pedestal\Pedestal;

use Timber\Timber;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Post;
use Pedestal\Posts\Attachment;
use Pedestal\Posts\Entities\Embed;
use Pedestal\Posts\Slots\Slots;
use Pedestal\Posts\Clusters\Person;
use Pedestal\Icons;

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

        add_action( 'admin_enqueue_scripts', [ $this, 'action_admin_enqueue_scripts' ], 11 );
        add_action( 'admin_menu', [ $this, 'action_admin_menu_late' ], 100 );

        add_action( 'fm_user', [ $this, 'action_user_fields' ] );

        // Move some builtin metaboxes around
        add_action( 'do_meta_boxes', [ $this, 'action_do_meta_boxes_move_publish' ], 100 );
        add_action( 'do_meta_boxes', [ $this, 'action_do_meta_boxes_move_excerpt_below_title' ], 100 );
        add_action( 'do_meta_boxes', [ $this, 'action_do_meta_boxes_move_featured_image' ], 100 );

        // Set up the `after_title` metabox context
        add_action( 'edit_form_after_title', function() {
            global $post, $wp_meta_boxes;
            do_meta_boxes( get_current_screen(), 'after_title', $post );
        } );

        add_action( 'save_post', function( $post_id, $post, $update ) {
            $post_type = get_post_type( $post_id );
            switch ( $post_type ) :
                case 'pedestal_embed':
                    $embed = Embed::get( $post_id );
                    if ( method_exists( $embed, 'update_embed_data' ) ) {
                        $embed->update_embed_data();
                    }
                    break;
                case 'pedestal_person':
                    $person = Person::get( $post_id );
                    if ( ! Types::is_cluster( $person ) ) {
                        break;
                    }

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
            endswitch;
        }, 100, 3 );

        add_action( 'admin_notices', [ $this, 'action_admin_notice_locality_type_required' ] );
        add_action( 'admin_notices', [ $this, 'action_admin_notice_unembeddable_url' ] );
        add_action( 'admin_notices', [ $this, 'action_admin_notice_slot_item_defaults_missing' ] );

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
                'pedestal_scheduled_posts',
                'Scheduled Posts',
                [ $this, 'handle_dashboard_widget_scheduled_posts' ]
            );
        });

        add_action( 'edit_form_advanced', function( $post ) {
            echo '<h1 class="wp-heading-inline">Distribution</h1>';
            do_meta_boxes( null, 'distribution', $post );
        } );

        // Fix the SVG icon size
        add_action('admin_head', function() {
            echo '<style type="text/css">
                    .fm-icon .thumbnail {
                          width: 50px;
                          height: 50px;
                     }
                 </style>';
        });
    }

    /**
     * Set up admin filters
     */
    private function setup_filters() {

        add_filter( 'fm_element_markup_start', [ $this, 'filter_fm_element_markup_start' ], 10, 2 );
        add_filter( 'wp_insert_post_data', [ $this, 'filter_wp_insert_post_data' ], 10, 2 );
        add_filter( 'tiny_mce_before_init', [ $this, 'filter_tiny_mce_before_init' ] );

        if ( current_user_can( 'manage_uploads' ) ) {
            add_filter( 'attachment_fields_to_edit', [ $this, 'filter_attachment_fields_to_edit' ], 10, 2 );
            add_filter( 'attachment_fields_to_save', [ $this, 'filter_attachment_fields_to_save' ], 10, 2 );
        }

        add_filter( 'fm_datasource_post_get_items', [ $this, 'filter_fm_datasource_post_get_items' ] );

        // Highlight the proper parent menu item for submenu items that have been moved around
        add_filter( 'parent_file', function( $parent_file ) {
            global $pagenow;
            if ( ! empty( $_GET['taxonomy'] ) && 'edit-tags.php' == $pagenow ) {
                if ( 'pedestal_subscriptions' == $_GET['taxonomy'] ) {
                    $parent_file = 'users.php';
                }
                if ( 'pedestal_slot_item_type' == $_GET['taxonomy'] ) {
                    $parent_file = 'slots';
                }
            }
            return $parent_file;
        } );

        add_filter( 'user_contactmethods', function( $methods ) {
            $methods['phone_number'] = esc_html__( 'Phone Number', 'pedestal' );
            // If the User's public email address needs to be different than the
            // address registered to their account, then this field can be used
            // to override.
            $methods['public_email']       = esc_html__( 'Public Email', 'pedestal' );
            $methods['twitter_username']   = esc_html__( 'Twitter Username', 'pedestal' );
            $methods['facebook_profile']   = esc_html__( 'Facebook Profile', 'pedestal' );
            $methods['instagram_username'] = esc_html__( 'Instagram Username', 'pedestal' );
            return $methods;
        });

        // Remove default avatars
        add_filter( 'avatar_defaults', function ( $avatar_defaults ) {
            $avatar_url                     = '404';
            $avatar_defaults[ $avatar_url ] = 'No Avatar';
            return $avatar_defaults;
        } );

        // Enable SVG upload
        add_filter('upload_mimes', function( $mimes ) {
            $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        });

        // Disable TinyMCE if the post content contains a SoundCite shortcode
        add_filter( 'user_can_richedit', function( $can ) {
            global $post;
            if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'soundcite' ) ) {
                return false;
            }
            return $can;
        } );

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
                $allowed_tags[ $tag ]['dir']   = false;
            }
            return $allowed_tags;
        }, 10, 2 );

        /* Save any credit meta data to its own post meta field */
        add_filter( 'wp_generate_attachment_metadata', function( $metadata = [], $attachment_id = 0 ) {
            $attachment = Attachment::get( $attachment_id );
            if ( ! Types::is_attachment( $attachment ) ) {
                return $metadata;
            }
            $old_credit = $attachment->get_credit();
            if ( $old_credit ) {
                return $metadata;
            }
            if ( empty( $metadata['image_meta'] ) ) {
                return $metadata;
            }
            $img_meta = $metadata['image_meta'];
            $credit   = '';
            if ( ! empty( $img_meta['copyright'] ) ) {
                $credit = $img_meta['copyright'];
            }
            if ( ! empty( $img_meta['credit'] ) ) {
                $credit = $img_meta['credit'];
            }
            if ( $credit ) {
                $attachment->set_meta( 'credit', $credit );
            }

            return $metadata;
        }, 10, 2 );

        // Change new post title placeholder text
        add_filter( 'enter_title_here', function() {
            return 'Headline';
        } );

        // Customize the excerpt field description
        add_filter( 'gettext', function( $translation, $original ) {
            if ( false !== strpos( $original, 'Excerpts are optional hand-crafted summaries of your' ) ) {
                return 'Optional short sentence supporting the headline; help readers make a decision about continuing to read the full article. This will appear below the headline on the article. It will also appear on the homepage, unless it is overridden, see below.';
            }
            return $translation;
        }, 10, 2 );

        // Remove smart characters before inserting into the database
        add_filter( 'content_save_pre', [ $this, 'filter_fix_characters_before_save' ] );
        add_filter( 'title_save_pre', [ $this, 'filter_fix_characters_before_save' ] );
        // Restore smart characters before displaying in WYSIWYG editor
        add_filter( 'content_edit_pre', 'wptexturize' );
        add_filter( 'title_edit_pre', 'wptexturize' );
    }

    /**
     * Register Fieldmanager fields
     */
    public function action_init_after_post_types_registered() {
        $this->register_distribution_fields();
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
        wp_enqueue_style( 'pedestal-admin', PEDESTAL_DIST_DIRECTORY_URI . '/css/admin.css', [], PEDESTAL_VERSION );
        wp_enqueue_script( 'pedestal-admin', PEDESTAL_DIST_DIRECTORY_URI . '/js/admin.js', [], PEDESTAL_VERSION );

        wp_register_script( 'twig', PEDESTAL_DIST_DIRECTORY_URI . '/js/vendor/twig.min.js', [], PEDESTAL_VERSION );

        // Dequeue Fieldmanager group tabs CSS so we can load our own
        wp_dequeue_style( 'fm_group_tabs_css' );
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
            'name'            => 'user_bio_extended',
            'buttons_1'       => [
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
        $img      = new \Fieldmanager_Media( [
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
     * Display admin notice if the Locality Type is not set
     */
    public function action_admin_notice_locality_type_required() {
        $message = 'Please set the Locality Type in the field below!';
        self::handle_admin_notice_error( 'locality_type_required', $message );
    }

    /**
     * Display admin notice for unembeddable Embed URLs
     */
    public function action_admin_notice_unembeddable_url() {
        $add_link_url = admin_url( '/post-new.php?post_type=pedestal_link' );
        $message      = sprintf( 'You\'ve entered an invalid embed URL. Perhaps you meant to <a href="%s">post a Link</a>?', $add_link_url );
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
     * Move Publish metabox to the high position before any others
     *
     * This action must be added before any other action that moves metaboxes to
     * the high position in the side context.
     */
    public function action_do_meta_boxes_move_publish() {
        foreach ( get_post_types() as $post_type ) {
            remove_meta_box( 'submitdiv', $post_type, 'side' );
        }
        add_meta_box(
            'submitdiv',
            'Publish',
            'post_submit_meta_box',
            get_post_types(),
            'side',
            'high'
        );
    }

    /**
     * Re-position the post excerpt metabox to just below the title
     */
    public function action_do_meta_boxes_move_excerpt_below_title() {
        $supports_excerpt = Types::get_post_types_by_supported_feature( 'excerpt' );
        foreach ( $supports_excerpt as $post_type ) {
            remove_meta_box( 'postexcerpt', $post_type, 'normal' );
        }
        add_meta_box(
            'subhead',
            'Subhead (optional)',
            'post_excerpt_meta_box',
            $supports_excerpt,
            'after_title',
            'high'
        );
    }

    /**
     * Move the featured image metabox to just below the publish metabox
     *
     * N.B. the publish metabox must be moved to the `high` position before this
     * action is added or else the featured image metabox will appear before the
     * publish metabox.
     */
    public function action_do_meta_boxes_move_featured_image() {
        $supports_feat_image = Types::get_post_types_by_supported_feature( 'thumbnail' );
        foreach ( $supports_feat_image as $post_type ) {
            remove_meta_box( 'postimagediv', $post_type, 'side' );
        }
        add_meta_box(
            'postimagediv',
            'Featured Image',
            'post_thumbnail_meta_box',
            $supports_feat_image,
            'side',
            'high'
        );
    }

    /**
     * Filter markup to include placeholders specific to this post
     */
    public function filter_fm_element_markup_start( $out, $fm ) {

        $screen = get_current_screen();
        if ( 'post' !== $screen->base ) {
            return $out;
        }

        $post = Post::get( get_the_ID() );
        if ( ! $post ) {
            return $out;
        }

        $fm_tree = $fm->get_form_tree();
        array_pop( $fm_tree );
        $parent = array_pop( $fm_tree );

        if ( $parent ) {

            switch ( $parent->name ) {
                case 'twitter':
                    $placeholders = [
                        'title'       => $post->get_twitter_card_tag( 'title' ),
                        'description' => $post->get_twitter_card_tag( 'description' ),
                    ];
                    break;
                case 'facebook':
                    $placeholders = [
                        'title'       => $post->get_facebook_open_graph_tag( 'title' ),
                        'description' => $post->get_facebook_open_graph_tag( 'description' ),
                    ];
                    break;
                case 'linkedin':
                case 'seo':
                    $placeholders = [
                        'title'       => $post->get_default_seo_title(),
                        'description' => $post->get_default_seo_description(),
                    ];
                    break;
            }

            if ( isset( $placeholders[ $fm->name ] ) ) {
                $fm->attributes['placeholder'] = $placeholders[ $fm->name ];
            }
        }

        return $out;
    }

    /**
     * Filter the posts returned by an FM datasource
     *
     * @link https://github.com/alleyinteractive/wordpress-fieldmanager/pull/696
     * @param array $ret ID => title
     * @return array
     */
    public function filter_fm_datasource_post_get_items( $ret ) {
        foreach ( $ret as $post_id => &$post_title ) :
            $ped_post = Post::get( $post_id );
            if ( ! Types::is_post( $ped_post ) ) {
                continue;
            }

            // Append the post type name to the post title
            $plurals = false;
            $type    = $ped_post->get_post_type_name( $plurals );
            if ( $ped_post->is_locality() ) {
                $type = $ped_post->get_type_name();
            }
            $post_title .= ' (' . $type . ')';

            // Indicate a post is to be published in the future
            if ( $ped_post->get_status() == 'future' ) {
                $post_title = sprintf(
                    '— Scheduled: %s – %s',
                    $post_title,
                    $ped_post->get_the_datetime()
                );
            }

            if ( $ped_post->is_password_protected() ) {
                $post_title = '— PROTECTED: ' . $post_title;
            }
        endforeach;
        return $ret;
    }

    /**
     * Set up TinyMCE
     *
     * @link https://developer.wordpress.org/reference/hooks/tiny_mce_before_init/
     * @param array $settings An array with TinyMCE config
     */
    public function filter_tiny_mce_before_init( $settings ) {
        $post_id = get_queried_object_id();
        if ( 'pedestal_newsletter' !== get_post_type( $post_id ) ) {
            // Limit suggested formats
            $settings['block_formats'] = 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3';
        }

        // Set-up custom CSS classes to add to the Formats dropwdown
        $style_formats             = [
            [
                'title'    => 'Site Color',
                'selector' => '*', // Add this class to every element
                'classes'  => 'u-text-color-primary',
            ],
        ];
        $settings['style_formats'] = json_encode( $style_formats );

        // Add classes to the editor body element
        $extra_classes = ' s-content';
        if ( empty( $settings['body_class'] ) ) {
            $settings['body_class'] = $extra_classes;
        } else {
            $settings['body_class'] .= $extra_classes;
        }

        return $settings;
    }

    /**
     * Filter attachment fields available to edit
     */
    public function filter_attachment_fields_to_edit( $fields, $post ) {
        $attachment = Attachment::get( $post );
        if ( ! Types::is_attachment( $attachment ) ) {
            return $metadata;
        }
        $credit      = $attachment->get_credit();
        $credit_link = $attachment->get_credit_link();

        $fields['pedestal_credit']      = [
            'label' => esc_html__( 'Credit', 'pedestal' ),
            'input' => 'text',
            'value' => $credit,
        ];
        $fields['pedestal_credit_link'] = [
            'label' => esc_html__( 'Credit Link', 'pedestal' ),
            'input' => 'url',
            'value' => $credit_link,
        ];

        return $fields;
    }

    /**
     * Filter attachment fields as they are saved
     */
    public function filter_attachment_fields_to_save( $post, $attachment_data ) {
        $post_id = $post['ID'];
        if ( isset( $attachment_data['pedestal_credit'] ) ) {
            $val = sanitize_text_field( $attachment_data['pedestal_credit'] );
            update_post_meta( $post_id, 'credit', $val );
        }
        if ( isset( $attachment_data['pedestal_credit_link'] ) ) {
            $val = esc_url( $attachment_data['pedestal_credit_link'] );
            update_post_meta( $post_id, 'credit_link', $val );
        }
        return $post;
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
                if ( empty( $postarr['embed_url'] ) || ! Embed::get_embed_type_from_url( $postarr['embed_url'] ) ) {
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
     * Removes "smart" characters from word processors and replaces them with the correct HTML safe characters
     * WordPress will handle converting them to "smart" characters on the frontend with the use of wptexturize()
     *
     * @link https://wordpress.org/plugins/smart-quote-fixer/
     *
     * @param  string $str String to be modified
     * @return string      Cleaned string
     */
    public function filter_fix_characters_before_save( $str = '' ) {
        // Replace the smart quotes that cause question marks to appear
        $str = str_replace(
            [ "\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6" ],
            [ "'", "'", '"', '"', '-', '--', '...' ],
            $str
        );

        $str = str_replace(
            [ '™', '©', '®' ],
            [ '&trade;', '&copy;', '&reg;' ],
            $str
        );
        return $str;
    }

    /**
     * Register fields to manage a post's social media appearance and SEO
     */
    private function register_distribution_fields() {
        $distribution_group = new \Fieldmanager_Group( '', [
            'name'   => 'pedestal_distribution',
            'tabbed' => true,
        ] );

        $twitter_group = new \Fieldmanager_Group( esc_html__( 'Twitter', 'pedestal' ), [
            'name'     => 'twitter',
            'children' => [
                'title'       => new \Fieldmanager_TextArea( esc_html__( 'Alt Headline', 'pedestal' ), [
                    'description' => esc_html__( 'Title truncates after 70 characters', 'pedestal' ),
                    'attributes'  => [
                        'style'     => 'width:100%',
                        'maxlength' => 70,
                        'rows'      => 2,
                    ],
                ] ),
                'image'       => new \Fieldmanager_Media( esc_html__( 'Image', 'pedestal' ), [
                    'description'        => esc_html__( 'Twitter minimum size is 120x120 pixels', 'pedestal' ),
                    'button_label'       => esc_html__( 'Select Alternate Image', 'pedestal' ),
                    'modal_button_label' => esc_html__( 'Select image', 'pedestal' ),
                    'modal_title'        => esc_html__( 'Choose image', 'pedestal' ),
                ] ),
                'description' => new \Fieldmanager_TextArea( esc_html__( 'Alt Description', 'pedestal' ), [
                    'description' => esc_html__( 'Description text will be truncated at 200 characters.', 'pedestal' ),
                    'attributes'  => [
                        'style'     => 'width:100%',
                        'maxlength' => 200,
                        'rows'      => 3,
                    ],
                ] ),
            ],
        ] );

        $facebook_group = new \Fieldmanager_Group( esc_html__( 'Facebook', 'pedestal' ), [
            'name'     => 'facebook',
            'children' => [
                'title'       => new \Fieldmanager_TextArea( esc_html__( 'Alt Headline', 'pedestal' ), [
                    'description' => esc_html__( 'The title of your article, excluding any branding.', 'pedestal' ),
                    'attributes'  => [
                        'style' => 'width:100%',
                        'rows'  => 2,
                    ],
                ] ),
                'image'       => new \Fieldmanager_Media( esc_html__( 'Image', 'pedestal' ), [
                    'description'        => esc_html__( 'Override the featured image with an image specific to Facebook. We suggest that you use an image of at least 1200x630 pixels.', 'pedestal' ),
                    'button_label'       => esc_html__( 'Select Alternate Image', 'pedestal' ),
                    'modal_button_label' => esc_html__( 'Select image', 'pedestal' ),
                    'modal_title'        => esc_html__( 'Choose image', 'pedestal' ),
                ] ),
                'description' => new \Fieldmanager_TextArea( esc_html__( 'Alt Description', 'pedestal' ), [
                    'description' => esc_html__( 'A detailed description of the piece of content, usually between 2 and 4 sentences.', 'pedestal' ),
                    'attributes'  => [
                        'style' => 'width:100%',
                        'rows'  => 4,
                    ],
                ] ),
            ],
        ] );

        $linkedin_group = new \Fieldmanager_Group( esc_html__( 'LinkedIn', 'pedestal' ), [
            'name'     => 'linkedin',
            'children' => [
                'title'       => new \Fieldmanager_TextArea( esc_html__( 'Alt Headline', 'pedestal' ), [
                    'description' => esc_html__( 'The title of your article, excluding any branding. Max 200 characters.', 'pedestal' ),
                    'attributes'  => [
                        'style' => 'width:100%',
                        'rows'  => 2,
                    ],
                ] ),
                'description' => new \Fieldmanager_TextArea( esc_html__( 'Alt Description', 'pedestal' ), [
                    'description' => esc_html__( 'A detailed description of the piece of content, usually between 2 and 4 sentences. Longer titles will be truncated gracefully with ellipses.', 'pedestal' ),
                    'attributes'  => [
                        'style' => 'width:100%',
                        'rows'  => 4,
                    ],
                ] ),
            ],
        ] );

        $seo_group = new \Fieldmanager_Group( esc_html__( 'Google Search', 'pedestal' ), [
            'name'     => 'seo',
            'children' => [
                'title'       => new \Fieldmanager_TextArea( esc_html__( 'Alt Headline', 'pedestal' ), [
                    'description' => esc_html__( 'Suggested length of up to 60 characters.', 'pedestal' ),
                    'attributes'  => [
                        'style' => 'width:100%',
                        'rows'  => 2,
                    ],
                ] ),
                'description' => new \Fieldmanager_TextArea( esc_html__( 'Alt Description', 'pedestal' ), [
                    'description' => esc_html__( 'Suggested length of up to 150 characters. Defaults to summary or subhead.', 'pedestal' ),
                    'attributes'  => [
                        'style' => 'width:100%',
                        'rows'  => 2,
                    ],
                ] ),
            ],
        ] );

        $distribution_group->add_child( $twitter_group );
        $distribution_group->add_child( $facebook_group );
        $distribution_group->add_child( $linkedin_group );
        $distribution_group->add_child( $seo_group );

        $distributable_post_types         = get_post_types( [
            'public'   => true,
            '_builtin' => false,
        ] );
        $distributable_post_types['page'] = 'page';

        if ( current_user_can( 'manage_distribution' ) ) {
            $distribution_group->add_meta_box(
                esc_html__( 'Social Media & Search Engines', 'pedestal' ),
                $distributable_post_types,
                'advanced',
                'default'
            );
        }
    }

    /**
     * Handle the display of the Scheduled Posts dashboard widget
     */
    public function handle_dashboard_widget_scheduled_posts() {
        $post_types         = Types::get_entity_post_types();
        $post_types[]       = 'pedestal_newsletter';
        $future_posts_query = new \WP_Query( [
            'post_type'      => $post_types,
            'posts_per_page' => 15,
            'post_status'    => 'future',
            'orderby'        => 'date',
            'order'          => 'ASC',
        ] );
        $context            = array_merge( Timber::get_context(), [
            'items' => Post::get_posts_from_query( $future_posts_query ),
        ] );
        Timber::render( 'partials/admin/dash-widget-scheduled-posts.twig', $context );
    }
}
