<?php

namespace Pedestal;

use Timber\Timber;
use Pedestal\Icons;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Email\Newsletter_Emails;

class Conversion_Prompts {

    /**
     * Get an instance of this class
     */
    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook into WordPress via action
     */
    public function setup_actions() {
        // Render the newsletter signup conversion prompt after the 3rd item in a stream
        add_action( 'pedestal_after_stream_item_3', function() {
            $ignore_post_types = Types::get_original_post_types();
            $ignore_post_types[] = 'pedestal_story';
            if ( is_singular( $ignore_post_types ) ) {
                return;
            }

            $signup_source = 'Other stream';
            if ( is_home() ) {
                $signup_source = 'Homepage stream';
            }

            echo '<div class="stream-item signup-email">';
            echo self::get_prompts( 'stream', [
                'signup_source' => $signup_source,
            ] );
            echo '</div>';
        } );

        add_action( 'rest_api_init', function() {
            register_rest_route( PEDESTAL_API_NAMESPACE, '/conversion-prompt/render', [
                'methods'  => 'GET',
                'callback' => [ $this, 'handle_render_endpoint' ],
            ] );
        } );
    }

    /**
     * Hook in to WordPress via filters
     */
    public function setup_filters() {
        add_filter( 'body_class', function( $classes ) {
            // Allow target audiences to be set from a query parameter
            // ?target-audience=foo --> is-target-audience--foo
            if ( ! empty( $_GET['target-audience'] ) ) {
                $target_audience = sanitize_title( $_GET['target-audience'] );
                // Disable the cookie script from taking action and overriding what we set here
                $classes[] = 'is-target-audience--disabled';
                $classes[] = 'is-target-audience--' . $target_audience;
            } else {
                $classes[] = 'is-target-audience--unidentified';
            }
            return $classes;
        } );
    }

    /**
     * Handle the `conversion-prompt/render` API endpoint
     *
     * Simple wrapper for `self::get_prompt()`.
     *
     * @param \WP_REST_Request $request
     * @return string
     */
    public static function handle_render_endpoint( \WP_REST_Request $request ) {
        $context = $request->get_params();
        return self::get_prompt( $context );
    }

    /**
     * Render a conversion prompt
     *
     * @param  array  $args Arguments to modify how the prompt is rendered
     * @return string       Rendered conversion prompt
     */
    public static function get_prompt( $args = [] ) {
        $defaults = [
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

        $target_audience = '';

        if ( empty( $context['icon'] ) && ! empty( $context['icon_name'] ) ) {
            $context['icon'] = Icons::get_icon( $context['icon_name'], 'conversion-prompt__icon' );
        }

        if ( ! empty( $context['body'] ) ) {
            $context['body'] = apply_filters( 'the_content', $context['body'] );

            // Add custom analytics events to track links in the body
            $replacement = sprintf(
                'data-ga-category="inline-prompt" data-ga-label="%s" href=',
                esc_attr( $context['target_audience'] )
            );
            $context['body'] = str_replace( 'href=', $replacement, $context['body'] );
        }

        if ( ! empty( $context['style'] ) ) {
            $context['style_class'] = 'conversion-prompt--' . sanitize_title( $context['style'] );
        }

        if ( ! empty( $context['target_audience'] ) ) {
            $target_audience = sanitize_title( $context['target_audience'] );
            $context['target_audience_class'] = 'target-audience--' . $target_audience;
        }

        if ( empty( $context['signup_form'] ) && $context['type'] === 'with_signup_form' ) {
            $context['signup_form'] = Newsletter_Emails::get_signup_form( [
                'signup_source' => $context['signup_source'],
                'ga_label'      => $target_audience,
            ] );
        }

        ob_start();
        Timber::render( 'partials/conversion-prompt.twig', $context );
        return ob_get_clean();
    }

    /**
     * Get all of the rendered prompts for a given location
     *
     * @param string $location Location name
     * @param array $args
     * @return string Rendered prompts
     */
    public function get_prompts( $location, $args = [] ) {
        $args = wp_parse_args( $args, [
            'signup_source' => '',
        ] );
        $output = '';
        $prompts = Conversion_Prompt_Admin::get_prompt_data_by_location( $location );
        foreach ( $prompts as $prompt_data ) {
            $prompt_data['signup_source'] = $args['signup_source'];
            $output .= self::get_prompt( $prompt_data );
        }
        return $output;
    }
}
