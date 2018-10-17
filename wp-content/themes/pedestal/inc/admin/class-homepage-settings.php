<?php
namespace Pedestal\Admin;

use Sunra\PhpSimple\HtmlDomParser;
use Pedestal\Frontend;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Homepage_Settings {

    private $exclude_stream_meta_key = 'exclude_from_home_stream';

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook in to various actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init' ], 11 );
        add_action( 'added_post_meta', [ $this, 'purge_excluded_post_ids' ], 10, 3 );
        add_action( 'updated_post_meta', [ $this, 'purge_excluded_post_ids' ], 10, 3 );
    }

    /**
     * Hook in to various filters
     */
    private function setup_filters() {
        add_filter( 'fm_element_markup_start', [ $this, 'filter_fm_element_markup_start' ], 10, 2 );
        add_filter( 'fm_element_markup_end', [ $this, 'filter_fm_element_markup_end_summary_buttons' ], 10, 2 );
    }

    /**
     * Register fields to manage a post's appearance on the homepage
     */
    public function action_init() {
        $homepage_settings_group = new \Fieldmanager_Group( '', [
            'name'           => 'homepage_settings',
            'serialize_data' => false,
            'add_to_prefix'  => false,
        ] );

        $summary_field = new \Fieldmanager_TextArea( 'Summary (optional)', [
            'name' => 'summary',
            'description' => 'Optional short paragraph(s) capturing the article in a nutshell; help readers get caught up on the news at-a-glance. This will appear on the homepage below the headline, and defaults to the subhead, unless you override it here.',
        ] );

        $exclude_field = new \Fieldmanager_Radios( [
            'name' => $this->exclude_stream_meta_key,
            'default_value' => 'show',
            'options' => [
                'show' => 'Show on homepage stream',
                'hide' => 'Hide from homepage stream',
            ],
        ] );

        $homepage_settings_group->add_child( $summary_field );
        $homepage_settings_group->add_child( $exclude_field );

        if ( current_user_can( 'manage_distribution' ) ) {
            $homepage_settings_group->add_meta_box(
                esc_html__( 'Homepage', 'pedestal' ),
                Types::get_entity_post_types(),
                'distribution',
                'default'
            );
        }
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

        // If the name attribute of the parent group isn't 'summary' then bail
        $fm_tree = $fm->get_form_tree();
        $parent = array_pop( $fm_tree );
        if (
            empty( $parent->name )
            || 'summary' != $parent->name
        ) {
            return $out;
        }

        $fm->attributes['placeholder'] = wp_strip_all_tags( $post->get_excerpt() );

        return $out;
    }

    /**
     * Inject markup for the Summary field buttons
     *
     * @param  string $out    HTML of the field element
     * @param  object $obj    FieldManager Field object
     * @return string         Modified HTML
     */
    public function filter_fm_element_markup_end_summary_buttons( $out, $obj ) {
        if ( empty( $obj->name ) || 'summary' !== $obj->name ) {
            return $out;
        }

        $buttons = '<div class="wp-media-buttons fm-summary-buttons js-fm-summary-buttons">';
        $buttons .=
            '<button type="button" class="button js-pedestal-summary-copy-subhead">' .
                '<span class="wp-media-buttons-icon dashicons dashicons-admin-page"></span> ' .
                'Copy Subhead' .
            '</button>';

        $buttons .=
            '<button type="button" class="button js-pedestal-summary-copy-first-graf">' .
                '<span class="wp-media-buttons-icon dashicons dashicons-admin-page"></span> ' .
                'Copy First Paragraph' .
            '</button>';

        $buttons .= '</div>';

        // Insert the buttons after the <label> element
        $dom = HtmlDomParser::str_get_html( $out );
        $node = $dom->find( '.fm-label-summary', 0 );
        $node->innertext = $node->innertext . $buttons;

        return $dom->save();
    }

    /**
     * Listen for change to exclude_from_home_stream post meta value and flush
     * the option that stores the list of post ids that should be excluded from streams
     *
     * @param  string  $meta_id   ID of meta item in post_meta table
     * @param  integer $object_id Post ID associated with this meta
     * @param  string  $meta_key  The key used to store this post meta value
     */
    public function purge_excluded_post_ids( $meta_id = '', $object_id = 0, $meta_key = '' ) {
        if ( $this->exclude_stream_meta_key !== $meta_key ) {
            return;
        }

        $force_refresh = true;
        Frontend::get_post_ids_excluded_from_home_stream( $force_refresh );
    }
}
