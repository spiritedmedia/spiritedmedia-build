<?php

namespace Pedestal\Audience;

use Timber\Timber;
use Pedestal\Icons;
use Pedestal\Email\Newsletter_Emails;
use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Conversion_Prompts extends Targeted_Messages {

    protected static $model_defaults = [
        'standard' => [
            'title'       => 'Default Title',
            'icon_name'   => 'link',
            'body'        => 'This is the default message body',
            'type'        => 'with_button',
            'button_text' => 'Default Label',
            'button_url'  => '#',
            'style'       => 'subtle',
        ],
        'override' => [],
    ];

    protected static $option_name = 'pedestal_conversion_prompts';

    protected $admin_page_title = 'Conversion Prompts';

    protected $capability = 'manage_conversion_prompts';

    protected $api_component_name = 'conversion-prompt';

    protected $locations = [
        'stream' => 'Homepage and other streams',
        'entity' => 'Article pages, after the end of an article',
    ];

    protected function setup_actions() {
        parent::setup_actions();

        // Render the newsletter signup conversion prompt after the 3rd item in a stream
        add_action( 'pedestal_after_stream_item_3', function() {
            $ignore_post_types   = Types::get_original_post_types();
            $ignore_post_types[] = 'pedestal_story';
            if ( is_singular( $ignore_post_types ) ) {
                return;
            }

            $signup_source = 'Other stream';
            if ( is_home() ) {
                $signup_source = 'Homepage stream';
            }

            echo '<div class="stream-item signup-email">';
            echo static::get_rendered_messages( 'stream', [
                'signup_source' => $signup_source,
            ] );
            echo '</div>';
        } );
    }

    protected function setup_filters() {
        parent::setup_filters();
        add_filter( 'fm_element_markup_start', [ $this, 'filter_fm_element_markup_start' ], 10, 2 );
    }

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
        wp_localize_script( 'pedestal-conversion-prompts', 'messagePreviewDefaults', static::$model_defaults );
        wp_localize_script( 'pedestal-conversion-prompts', 'PedestalIcons', $this->icons_svg );
    }

    protected function message_fields() {
        return [
            'id'            => new \Fieldmanager_Hidden(),
            'title'         => new \Fieldmanager_TextField( 'Message Title', [
                'description'         => 'Use to draw attention (succinctly); use Message to describe',
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'attributes'          => [
                    'placeholder' => 'A compelling title to draw the reader\'s attention',
                    'size'        => 50,
                ],
            ] ),
            'icon_name'     => new \Fieldmanager_Radios( 'Icon', [
                'default_value' => 'link',
                'options'       => $this->icon_buttons,
            ] ),
            'body'          => new \Fieldmanager_RichTextArea( 'Message', [
                'editor_settings' => [
                    'media_buttons' => false,
                ],
                'buttons_1'       => [ 'bold', 'italic', 'underline', 'link', 'unlink' ],
                'buttons_2'       => [],
            ] ),
            'type'          => new \Fieldmanager_Radios( 'Include newsletter email signup fields', [
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'default_value'       => 'with_button',
                'options'             => [
                    'with_button'      => 'With call-to-action button',
                    'with_signup_form' => 'With email signup form',
                ],
            ] ),
            'button_text'   => new \Fieldmanager_TextField( 'Button Label', [
                'validation_rules'    => 'required',
                'validation_messages' => 'Required',
                'display_if'          => [
                    'src'   => 'type',
                    'value' => 'with_button',
                ],
                'attributes'          => [
                    'placeholder' => '…',
                    'size'        => 50,
                ],
            ] ),
            'button_url'    => new \Fieldmanager_Link( 'Destination URL', [
                'display_if'          => [
                    'src'   => 'type',
                    'value' => 'with_button',
                ],
                'validation_rules'    => [
                    'required' => true,
                    'url'      => true,
                ],
                'validation_messages' => [
                    'required' => true,
                    'url'      => 'This is not a URL!',
                ],
                'attributes'          => [
                    'placeholder' => 'https://',
                    'size'        => 50,
                ],
            ] ),
            'preview'       => new \Fieldmanager_TextField( 'Preview', [
                'description'               => 'How the message will look to readers',
                'description_after_element' => false,
                'template'                  => $this->preview_template,
            ] ),
            'preview_model' => new \Fieldmanager_Hidden( false, [
                'sanitize' => [ '\Pedestal\Utils\Utils', 'return_same' ],
            ] ),
            'style'         => new \Fieldmanager_Radios( 'Presentation style', [
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
        $parent  = array_pop( $fm_tree );
        if (
            empty( $parent->name )
            || 'pedestal_conversion_prompts' != $parent->name
        ) {
            return $out;
        }

        echo '<p class="conversion-prompt-admin-explainer">You can target the same location more than once. Drag-and-drop individual messages to override.</p>';

        return $out;
    }

    public static function render( $args = [] ) {
        $defaults      = [
            'target_audience' => 'unidentified',
            'style'           => '',
            'icon_name'       => '',
            'icon'            => '',
            'title'           => '',
            'body'            => '',
            'type'            => 'with_button',
            'button_text'     => 'Sign Up',
            'button_url'      => '',
            'signup_form'     => '',
            'signup_source'   => '',
        ];
        $filtered_args = apply_filters( 'pedestal_conversion_prompt_args', wp_parse_args( $args, $defaults ) );
        // Passed-in args always take precedence over filtered args
        $context = wp_parse_args( $args, $filtered_args );

        if ( empty( $context['icon'] ) && ! empty( $context['icon_name'] ) ) {
            $context['icon'] = Icons::get_icon( $context['icon_name'], 'conversion-prompt__icon' );
        }

        if ( ! empty( $context['body'] ) ) {
            $context['body'] = apply_filters( 'the_content', $context['body'] );

            // Add custom analytics events to track links in the body
            $replacement     = sprintf(
                'data-ga-category="inline-prompt" data-ga-label="%s" href=',
                esc_attr( $context['target_audience'] )
            );
            $context['body'] = str_replace( 'href=', $replacement, $context['body'] );
        }

        if ( ! empty( $context['style'] ) ) {
            $context['style_class'] = 'conversion-prompt--' . sanitize_title( $context['style'] );
        }

        if ( ! empty( $context['target_audience'] ) ) {
            $target_audience                  = sanitize_title( $context['target_audience'] );
            $context['target_audience_class'] = 'show-for-target-audience--' . $target_audience;
        }

        if ( empty( $context['target_audience'] ) ) {
            $target_audience = $defaults['target_audience'];
        } else {
            $target_audience                  = sanitize_title( $context['target_audience'] );
            $context['target_audience_class'] = 'show-for-target-audience--' . $target_audience;
        }

        if ( empty( $context['signup_form'] ) && $context['type'] === 'with_signup_form' ) {
            $context['signup_form'] = Newsletter_Emails::get_signup_form( [
                'signup_source' => $context['signup_source'],
                'ga_label'      => $target_audience,
            ] );
        }

        return Timber::fetch( 'partials/conversion-prompt.twig', $context );
    }
}
