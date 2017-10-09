<?php

namespace Pedestal\Registrations\Post_Types;

use Timber\Timber;

use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Posts\Entities\Entity;
use Pedestal\Posts\Entities\Embed;
use Pedestal\Posts\Entities\Originals\Whos_Next;
use Pedestal\Posts\Clusters\Person;

class Entity_Types {

    public $original_post_types = [];

    private static $politifact_ratings = [
        'pants'        => 'Pants on Fire!',
        'false'        => 'False',
        'mostly_false' => 'Mostly False',
        'half_true'    => 'Half True',
        'mostly_true'  => 'Mostly True',
        'true'         => 'True',
        'full_flop'    => 'Full Flop',
        'half_flip'    => 'Half Flip',
        'no_flip'      => 'No Flip',
    ];

    public $post_types = [
        'pedestal_article',
        'pedestal_embed',
        'pedestal_event',
        'pedestal_link',
        'pedestal_factcheck',
        'pedestal_whosnext',
    ];

    private static $instance;

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
            $instance->setup_types();
        }
        return $instance;
    }

    /**
     * Set up actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
        add_action( 'save_post_pedestal_embed', [ $this, 'action_save_post_pedestal_embed' ], 10, 3 );
        add_action( 'save_post_pedestal_whosnext', [ $this, 'action_save_post_whosnext_clusters' ], 10, 3 );
    }

    /**
     * Hook into filters
     */
    public function setup_filters() {
        add_filter( 'pedestal_stream_item_context', [ $this, 'filter_pedestal_stream_item_context' ] );
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
                'capability_type'   => [ 'entity', 'entities' ],
            ];

            switch ( $post_type ) {

                case 'pedestal_article':
                    $singular = esc_html__( 'Article', 'pedestal' );
                    $plural = esc_html__( 'Articles', 'pedestal' );
                    $class = 'Posts\\Entities\\Originals\\Article';
                    $args['menu_position'] = 7;
                    $args['menu_icon'] = 'dashicons-welcome-write-blog';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                        'editor',
                        'excerpt',
                        'author',
                        'slots',
                        'breaking',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'articles',
                    ];
                    $args['capability_type'] = 'article';
                    $this->original_post_types[] = $post_type;
                    break;

                case 'pedestal_link':
                    $singular = esc_html__( 'Link', 'pedestal' );
                    $plural = esc_html__( 'Links', 'pedestal' );
                    $class = 'Posts\\Entities\\Link';
                    $args['menu_position'] = 8;
                    $args['menu_icon'] = 'dashicons-admin-links';
                    $args['capability_type'] = [ 'link', 'links' ];
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'links',
                    ];
                    break;

                case 'pedestal_embed':
                    $singular = esc_html__( 'Embed', 'pedestal' );
                    $plural = esc_html__( 'Embeds', 'pedestal' );
                    $class = 'Posts\\Entities\\Embed';
                    $args['menu_position'] = 9;
                    $args['menu_icon'] = 'dashicons-twitter';
                    $args['capability_type'] = [ 'embed', 'embeds' ];
                    $args['supports'] = [
                        'title',
                        'excerpt',
                        'slots',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'embeds',
                    ];
                    break;

                case 'pedestal_factcheck':
                    $singular = esc_html__( 'Factcheck', 'pedestal' );
                    $plural = esc_html__( 'Factchecks', 'pedestal' );
                    $class = 'Posts\\Entities\\Originals\\Factcheck';
                    $args['menu_position'] = 10;
                    $args['menu_icon'] = 'dashicons-forms';
                    $args['supports'] = [
                        'title',
                        'excerpt',
                        'thumbnail',
                        'author',
                        'slots',
                        'breaking',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'factchecks',
                    ];
                    $this->original_post_types[] = $post_type;
                    break;

                case 'pedestal_event':
                    $singular = esc_html__( 'Event', 'pedestal' );
                    $plural = esc_html__( 'Events', 'pedestal' );
                    $class = 'Posts\\Entities\\Event';
                    $args['menu_position'] = 11;
                    $args['menu_icon'] = 'dashicons-calendar-alt';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                        'author',
                        'slots',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'events',
                    ];
                    $args['capability_type'] = 'event';
                    break;

                case 'pedestal_whosnext':
                    $singular = esc_html__( 'Who’s Next', 'pedestal' );
                    $plural = esc_html__( 'Who’s Next', 'pedestal' );
                    $class = 'Posts\\Entities\\Originals\\Whos_Next';
                    $args['menu_position'] = 13;
                    $args['menu_icon'] = 'dashicons-universal-access-alt';
                    $args['supports'] = [
                        'title',
                        'thumbnail',
                        'editor',
                        'excerpt',
                        'author',
                    ];
                    $args['rewrite'] = [
                        'slug' => 'whos-next',
                    ];
                    $this->original_post_types[] = $post_type;
                    break;

            }// End switch().

            $post_types[ $post_type ] = compact( 'singular', 'plural', 'class', 'args' );

        endforeach;

        $this->post_types = $post_types;

    }

    /**
     * Setup properties unique to this stream item
     *
     * @param  array $context  List of properties for a stream item
     * @return array           Modified list of properties
     */
    public function filter_pedestal_stream_item_context( $context = [] ) {
        if ( empty( $context['post'] ) ) {
            return $context;
        }

        $entity = Post::get( $context['post'] );
        if ( ! Types::is_entity( $entity ) ) {
            return $context;
        }

        $story = $entity->get_primary_story();
        if ( $story ) {
            $context['overline'] = $story->get_the_title();
            $context['overline_url'] = $story->get_the_permalink();
        }
        $context['featured_image'] = $entity->get_featured_image_html( '1024-16x9' );

        return $context;
    }

    /**
     * Register entity fields
     */
    public function action_init_after_post_types_registered() {
        $this->register_entity_fields();
        $this->register_article_fields();
        $this->register_embed_fields();
        $this->register_event_fields();
        $this->register_link_fields();
        $this->register_factcheck_fields();
        $this->register_whosnext_fields();
    }

    /**
     * Perform actions on saving Embeds
     */
    public function action_save_post_pedestal_embed( $post_id, $post, $update ) {
        $accepted_post_statuses = [ 'publish', 'future' ];
        if ( ! isset( $post->post_status ) || ! in_array( $post->post_status, $accepted_post_statuses ) ) {
            return;
        }

        $embed = Embed::get( $post_id );
        if ( ! $embed instanceof Embed || empty( $_POST['embed_url'] ) ) {
            return;
        }
        $embed_url = $_POST['embed_url'];

        $embed_type = Embed::get_embed_type_from_url( $embed_url );
        if ( $embed_type !== $embed->get_meta( 'embed_type' ) ) {
            $embed->set_embed_type( $embed_type );
        }

        $embed->set_embed_meta_from_oembed( 'author_name', $embed_url );
    }

    /**
     * Connect Who's Next entities to clusters upon save
     */
    public function action_save_post_whosnext_clusters( $post_id, $post, $update ) {

        $post = Whos_Next::get( $post_id );
        if ( ! $post instanceof Whos_Next ) {
            return;
        }

        if ( empty( $post::get_whosnext_story() ) ) {
            return;
        }

        // Who's Next posts must always be connected to the Who's Next story
        $whos_next_story_id = $post::get_whosnext_story()->get_id();
        $wrong_story_id = ( ! empty( $post->get_primary_story() ) && $post->get_primary_story()->get_id() !== $whos_next_story_id );
        if ( empty( $post->get_primary_story() ) || $wrong_story_id ) {
            p2p_type( 'entities_to_stories' )->connect( $post_id, $whos_next_story_id );
        }

        if ( isset( $post->post_status ) && ( 'publish' !== $post->post_status ) ) {
            return;
        }

        $items = $post->get_items();

        if ( empty( $items ) ) {
            return;
        }

        // Connect the post to each of the People listed
        foreach ( $items as $item ) {
            if ( empty( $item['people'] ) ) {
                continue;
            }
            foreach ( $item['people'] as $person ) {
                if ( ! $person instanceof Person ) {
                    continue;
                }
                p2p_type( 'entities_to_people' )->connect( $post_id, $person->get_id() );
            }
        }
    }

    /**
     * Register fields for all entities
     */
    private function register_entity_fields() {
        // Replaces deprecated `hidden_in_stream` post meta
        $exclude = new \Fieldmanager_Select( [
            'name' => 'exclude_from_home_stream',
            'options' => [
                'data' => [
                    false => 'False',
                    true => 'True',
                ],
            ],
        ] );
        $exclude->add_meta_box( 'Exclude from Home Stream', Types::get_entity_post_types(), 'side', 'low' );
    }

    /**
     * Register fields for Articles
     */
    private function register_article_fields() {
        $footnotes = new \Fieldmanager_RichTextArea( [
            'name'     => 'footnotes',
            'label'    => false,
            'description' => esc_html__( 'Add any additional footnotes here, such as corrections, contributors, other acknowledgements.', 'pedestal' ),
        ] );
        $footnotes->add_meta_box( esc_html__( 'Footnotes', 'pedestal' ), Types::get_original_post_types(), 'normal', 'high' );
    }

    /**
     * Register fields for Embeds
     */
    private function register_embed_fields() {

        $services = array_values( Embed::get_embeddable_services() );
        $services = Utils::get_byline_list( $services, [
            'pretext' => '',
        ] );
        $description = sprintf( 'Only %s URLs are supported at this time. Other URLs will not save and your post will be blocked from publishing.', $services );
        $fm = new \Fieldmanager_Textfield( [
            'name'     => 'embed_url',
            'label'    => false,
            'description' => esc_html__( $description, 'pedestal' ),
            'sanitize' => function( $url ) {
                if ( empty( $url ) ) {
                    return '';
                }

                if ( Utils::get_service_name_from_url( $url ) ) {
                    return esc_url_raw( $url );
                } else {
                    return '';
                }
            },
        ] );
        $fm->add_meta_box( esc_html__( 'Embed URL', 'pedestal' ), [ 'pedestal_embed' ], 'normal', 'high' );

        $caption = new \Fieldmanager_Textarea( false, [
            'name' => 'embed_caption',
        ] );
        $caption->add_meta_box( esc_html__( 'Embed Caption', 'pedestal' ), [ 'pedestal_embed' ], 'normal', 'high' );

        $daily_insta_description = esc_html__( "If you'd like this Instagram Embed to
        be featured as Instagram of the Day, set the date here. To cancel, just
        clear the contents of the date field.", 'pedestal' );
        $daily_insta = new \Fieldmanager_Datepicker( false, [
            'name'        => 'daily_insta_date',
            'description' => $daily_insta_description,
            'js_opts'     => [
                'firstDay' => 0,
            ],
        ] );
        $daily_insta->add_meta_box( esc_html__( 'Instagram of the Day', 'pedestal' ), [ 'pedestal_embed' ], 'normal', 'high' );

    }

    /**
     * Register the fields for Events
     */
    private function register_event_fields() {

        $details = new \Fieldmanager_Group( false, [
            'name'       => 'event_details',
            'children'   => [
                'what'                => new \Fieldmanager_RichTextArea( esc_html__( 'What', 'pedestal' ), [
                    'name'            => 'what',
                    'editor_settings' => [
                        'teeny'         => true,
                        'media_buttons' => false,
                        'editor_height' => 300,
                    ],
                ] ),
                'start_time'     => new \Fieldmanager_Datepicker( esc_html__( 'Start Time', 'pedestal' ), [
                    'name'       => 'start_time',
                    'use_time'   => true,
                    'js_opts'    => [
                        'firstDay' => 0,
                    ],
                ] ),
                'end_time'     => new \Fieldmanager_Datepicker( esc_html__( 'End Time', 'pedestal' ), [
                    'name'       => 'end_time',
                    'use_time'   => true,
                    'js_opts'    => [
                        'firstDay' => 0,
                    ],
                ] ),
                'venue_name'     => new \Fieldmanager_Textfield( esc_html__( 'Venue Name', 'pedestal' ), [
                    'name'       => 'venue_name',
                ] ),
                'address'     => new \Fieldmanager_Textfield( esc_html__( 'Address', 'pedestal' ), [
                    'name'       => 'address',
                ] ),
                'cost'        => new \Fieldmanager_Textfield( esc_html__( 'Cost', 'pedestal' ), [
                    'name'       => 'cost',
                ] ),
                'more'                => new \Fieldmanager_RichTextArea( esc_html__( 'More Details', 'pedestal' ), [
                    'name'            => 'more',
                    'description'     => esc_html__( 'These additional details will only display on the single event page.', 'pedestal' ),
                    'editor_settings' => [
                        'teeny'         => true,
                        'media_buttons' => false,
                        'editor_height' => 300,
                    ],
                ] ),
                'url' => new \Fieldmanager_Link( esc_html__( 'Event URL', 'pedestal' ), [
                    'description' => esc_html__( 'If URL isn\'t set, the link will not display.', 'pedestal' ),
                ] ),
                'text'     => new \Fieldmanager_Textfield( esc_html__( 'Event Link Text', 'pedestal' ), [
                    'name'       => 'text',
                    'default_value' => esc_html__( 'Find out more', 'pedestal' ),
                ] ),
            ],
        ] );
        $details->add_meta_box( esc_html__( 'Event Details', 'pedestal' ), [ 'pedestal_event' ], 'normal', 'high' );
    }

    /**
     * Register fields for links
     */
    private function register_link_fields() {

        $fm = new \Fieldmanager_Textfield( [
            'name'    => 'external_url',
            'label'   => false,
        ] );
        $fm->add_meta_box( esc_html__( 'External URL', 'pedestal' ), [ 'pedestal_link' ], 'normal', 'high' );

    }

    /**
     * Register the fields for Factchecks
     */
    private function register_factcheck_fields() {

        $statement = new \Fieldmanager_Group( false, [
            'name'           => 'factcheck_statement',
            'serialize_data' => false,
            'children'       => [
                'type' => new \Fieldmanager_Radios( esc_html__( 'Statement Type', 'pedestal' ), [
                    'name'              => 'type',
                    'default_value'     => 'quote',
                    'options'           => [
                        'quote'   => esc_html__( 'Quote', 'pedestal' ),
                        'summary' => esc_html__( 'Summary', 'pedestal' ),
                    ],
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                ] ),
                'text_short' => new \Fieldmanager_TextArea( esc_html__( 'Short Statement', 'pedestal' ), [
                    'name'                => 'text_short',
                    'description'         => esc_html__( 'The statement as it will appear in the hero element, without quotation marks.', 'pedestal' ),
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                ] ),
                'text_full' => new \Fieldmanager_RichTextArea( esc_html__( 'Full Statement', 'pedestal' ), [
                    'name'                => 'text_full',
                    'description'         => esc_html__( 'The statement as it will appear directly above the post content, without quotation marks.', 'pedestal' ),
                ] ),
                'speaker' => new \Fieldmanager_Autocomplete( esc_html__( 'Speaker', 'pedestal' ), [
                    'name'                => 'speaker',
                    'description'         => esc_html__( 'Select a Person or Organization.', 'pedestal' ),
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                    'datasource'          => new \Fieldmanager_Datasource_Post( [
                        'query_args' => [
                            'post_type' => [
                                'pedestal_person',
                                'pedestal_org',
                            ],
                        ],
                    ] ),
                ] ),
                'setting' => new \Fieldmanager_TextField( esc_html__( 'Setting / Context', 'pedestal' ), [
                    'name'                => 'setting',
                    'description'         => esc_html__( 'Where / in what context did the speaker make the statement?', 'pedestal' ),
                ] ),
                'date' => new \Fieldmanager_Datepicker( esc_html__( 'Date', 'pedestal' ), [
                    'name'    => 'date',
                    'js_opts' => [
                        'firstDay' => 0,
                    ],
                ] ),
            ],
        ] );

        $rating = new \Fieldmanager_Select( false, [
            'name'                => 'factcheck_rating',
            'first_empty'         => true,
            'options'             => [
                'data' => static::$politifact_ratings,
            ],
        ] );

        $analysis = new \Fieldmanager_RichTextArea( false, [
            'name'                => 'factcheck_analysis',
            'required'            => true,
            'validation_rules'    => 'required',
            'validation_messages' => esc_html__( 'Required', 'pedestal' ),
            'editor_settings' => [
                'editor_height' => 600,
            ],
        ] );

        $ruling = new \Fieldmanager_RichTextArea( false, [
            'name'                => 'factcheck_ruling',
            'editor_settings' => [
                'editor_height' => 300,
            ],
        ] );

        $sources = new \Fieldmanager_RichTextArea( false, [
            'name'                => 'factcheck_sources',
            'required'            => true,
            'validation_rules'    => 'required',
            'validation_messages' => esc_html__( 'Required', 'pedestal' ),
            'editor_settings' => [
                'editor_height' => 300,
            ],
        ] );

        $editor = new \Fieldmanager_TextField( false, [
            'name'                => 'factcheck_editor',
        ] );

        $statement->add_meta_box( esc_html__( 'Statement', 'pedestal' ), [ 'pedestal_factcheck' ], 'normal', 'high' );
        $rating->add_meta_box( esc_html__( 'Rating', 'pedestal' ), [ 'pedestal_factcheck' ], 'normal', 'high' );
        $analysis->add_meta_box( esc_html__( 'Analysis', 'pedestal' ), [ 'pedestal_factcheck' ], 'normal', 'high' );
        $ruling->add_meta_box( esc_html__( 'Ruling', 'pedestal' ), [ 'pedestal_factcheck' ], 'normal', 'high' );
        $sources->add_meta_box( esc_html__( 'Sources', 'pedestal' ), [ 'pedestal_factcheck' ], 'normal', 'high' );
        $editor->add_meta_box( esc_html__( 'Editor', 'pedestal' ), [ 'pedestal_factcheck' ], 'normal', 'high' );

    }

    /**
     * Register fields for the Who's Next post type
     */
    private function register_whosnext_fields() {

        $items = new \Fieldmanager_Group( esc_html__( 'Item', 'pedestal' ), [
            'display_if'  => [
                'src'   => 'type',
                'value' => 'list',
            ],
            'limit'          => 0,
            'save_empty'     => false,
            'extra_elements' => 0,
            'add_more_label' => esc_html__( 'Add List Item', 'pedestal' ),
            'children'       => [
                'description' => new \Fieldmanager_RichTextArea( esc_html__( 'Why are these People featured on this list?', 'pedestal' ), [
                    'name'        => 'description',
                    'label_element' => 'p',
                    'editor_settings' => [
                        'teeny'         => true,
                        'media_buttons' => false,
                        'editor_height' => 300,
                    ],
                ] ),
                'img' => new \Fieldmanager_Media( esc_html__( 'Image', 'pedestal' ), [
                    'label_element' => 'p',
                ] ),
                'people' => new \Fieldmanager_Group( esc_html__( 'Person', 'pedestal' ), [
                    'name'           => 'people',
                    'minimum_count'  => 1,
                    'limit'          => 3,
                    'sortable'       => true,
                    'save_empty'     => false,
                    'extra_elements' => 0,
                    'add_more_label' => esc_html__( 'Add Additional Person', 'pedestal' ),
                    'children'       => [
                        'person' => new \Fieldmanager_Autocomplete( false, [
                            'name'                => 'person',
                            'description'         => esc_html__( 'Select a Person.', 'pedestal' ),
                            'required'            => true,
                            'validation_rules'    => 'required',
                            'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                            'show_edit_link'      => true,
                            'datasource'          => new \Fieldmanager_Datasource_Post( [
                                'query_args' => [
                                    'post_type'      => 'pedestal_person',
                                    'posts_per_page' => 1000,
                                ],
                            ] ),

                        ] ),
                    ],
                ] ),
            ],
        ] );

        $details = new \Fieldmanager_Group( false, [
            'name'           => 'whosnext_details',
            'children'       => [
                'type' => new \Fieldmanager_Radios( esc_html__( 'Type', 'pedestal' ), [
                    'options'           => [
                        'nomination' => esc_html__( 'Nomination', 'pedestal' ),
                        'list'       => esc_html__( 'List', 'pedestal' ),
                    ],
                    'default_value'     => 'nomination',
                    'required'            => true,
                    'validation_rules'    => 'required',
                    'validation_messages' => esc_html__( 'Required', 'pedestal' ),
                ] ),
                'items' => $items,
            ],
        ] );

        $details->add_meta_box( esc_html__( "Who's Next Details", 'pedestal' ), [ 'pedestal_whosnext' ], 'normal', 'high' );

    }

    /**
     * Get the array of Politifact ratings
     *
     * @return array
     */
    public static function get_politifact_ratings() {
        return self::$politifact_ratings;
    }
}
