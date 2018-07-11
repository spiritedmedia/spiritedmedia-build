<?php

namespace Pedestal;

use Timber\Timber;
use Sunra\PhpSimple\HtmlDomParser;
use Pedestal\Objects\Stream;
use Pedestal\Posts\Slots\Slots;
use Pedestal\Posts\Attachment;
use Pedestal\Icons;
use Pedestal\Registrations\Post_Types\Types;

class Adverts {
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
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'pedestal_after_stream_item_2', [ $this, 'action_pedestal_after_stream_item_2' ] );
        add_action( 'pedestal_after_stream_item_4', function() {
            echo $this->render_stream_ad_unit( '04' );
            echo self::render_dfp_unit( PEDESTAL_DFP_PREFIX . '_Inline', '300x250', [
                'additional_classes' => 'dfp--inline-stream',
            ] );
        } );
        add_action( 'pedestal_after_stream_item_8', function() {
            echo $this->render_stream_ad_unit( '08' );
        } );
        add_action( 'pedestal_after_stream_item_12', function() {
            echo $this->render_stream_ad_unit( '12' );
        } );
        add_action( 'pedestal_after_stream_item_16', function() {
            echo $this->render_stream_ad_unit( '16' );
        } );
    }

    /**
     * Hook in to various filters
     */
    private function setup_filters() {
        add_filter( 'query_vars', function( $query_vars ) {
            $query_vars[] = 'pedestal-ad-tester';
            return $query_vars;
        });
        add_filter( 'template_include', function( $template_path ) {
            if ( 1 == get_query_var( 'pedestal-ad-tester' ) ) {
                $new_template_path = locate_template( [ 'ad-tester.php' ] );
                if ( $new_template_path ) {
                    $template_path = $new_template_path;
                }
            }
            return $template_path;
        });
        add_filter( 'pedestal_stream_item_template', [ $this, 'filter_pedestal_stream_item_template' ], 10, 2 );
        add_filter( 'the_content', [ $this, 'filter_the_content_inject_inline_ads' ], 999 );
    }

    /**
     * Register rewrite rules
     */
    public function action_init_register_rewrites() {
        add_rewrite_rule( '^ad-tester/?$', 'index.php?pedestal-ad-tester=1', 'top' );
    }

    /**
     * Maybe render a sponsored stream item
     */
    public function action_pedestal_after_stream_item_2() {
        if ( ! is_home() ) {
            return;
        }
        echo $this->get_the_sponsored_item( '2a' );
    }

    /**
     * Modify the template used for sponsored stream items
     *
     * @param  string $template  Path to twig template to use for stream item
     * @param  array  $context   List of options specific to this stream item
     * @return string            Path to twig template to use for stream item
     */
    public function filter_pedestal_stream_item_template( $template = '', $context = [] ) {
        if ( empty( $context['type'] ) || 'sponsored' != $context['type'] ) {
            return $template;
        }
        return 'partials/stream/sponsored-stream-item.twig';
    }

    /**
     * Filter the content of posts to inject ads in between paragraphs
     *
     * @param  string $html HTML to maybe inject ads into
     * @return string       Modified HTML
     */
    public function filter_the_content_inject_inline_ads( $html = '' ) {
        if ( ! is_single() ) {
            return $html;
        }
        $post = get_post();
        if ( ! is_object( $post ) ) {
            return $html;
        }
        if ( ! Types::is_original_content( $post->post_type ) ) {
            return $html;
        }
        $args = [
            'ad_frequency' => 8,
            'max_ads'      => 3,
        ];
        return self::inject_inline_ads( $html, $args );
    }

    /**
     * Render a stream ad unit, these are unique per site.
     * @param  string $index  Which ad unit position to render
     * @return string         HTML for the ad unit
     */
    public function render_stream_ad_unit( $index = '' ) {
        $stream = new Stream;
        if ( $stream->is_stream_list() ) {
            return;
        }

        // Don't show on author pages
        if ( get_query_var( 'author_name' ) ) {
            return;
        }
        $context = Timber::get_context();
        ob_start();
        Timber::render( 'partials/adverts/ad-stream-' . $index . '.twig', $context );
        return ob_get_clean();
    }

    /**
     * Render a DFP ad unit
     * @param  string $id     The ID of the ad position
     * @param  string $sizes  Comma separated list of accepted sizes
     * @return string         HTML markup of the ad unit
     */
    public static function render_dfp_unit( $id = '', $sizes = '', $args = [] ) {
        if ( empty( $id ) || empty( $sizes ) ) {
            return;
        }
        $defaults = [
            'additional_classes' => '',
        ];
        $args = wp_parse_args( $args, $defaults );
        $ad_context = [
            'id'        => $id,
            'sizes'     => $sizes,
            'unique_id' => uniqid(),
        ];
        $ad_context = wp_parse_args( $ad_context, $args );
        ob_start();
        Timber::render( 'partials/adverts/dfp-unit.twig', $ad_context );
        return ob_get_clean();
    }

    /**
     * Get data about any sponsored stream items
     *
     * @return Array|False An array of data or false if no sponsored itmes found
     */
    public function get_sponsored_items() {
        $slots = Slots::get_slot_data( 'slot_item', [
            'type' => 'stream',
        ] );
        if ( ! $slots ) {
            return false;
        }

        // Get the slot data
        $data = $slots->get_fm_field( 'slot_item_type', 'sponsored-stream-items' );
        // Whitelisted keys to ensure a consistent output
        $whitelisted_keys = [ 'position', 'url', 'title', 'sponsored_by', 'image', 'featured_image' ];
        $output = [];
        foreach ( $whitelisted_keys as $key ) {
            $output[ $key ] = '';
            if ( ! empty( $data[ $key ] ) ) {
                $output[ $key ] = $data[ $key ];
            }
        }

        // If we don't have a sponsored_by value then bail to prevent an empty
        // sponsored slot item from rendering
        if ( empty( $output['sponsored_by'] ) ) {
            return false;
        }

        // Get an image
        if ( is_numeric( $output['image'] ) ) {
            $attachment = Attachment::get( $output['image'] );
            // Make sure we have a proper Attachment object
            if ( method_exists( $attachment, 'get_html' ) ) {
                $output['featured_image'] = $attachment->get_html( 'medium-square' );
            }
        }
        return $output;
    }

    /**
     * Render a sponsored stream item
     *
     * @param  string $index  Position in the stream
     * @return string         HTML markup of the sponsored item
     */
    public function get_the_sponsored_item( $index = '' ) {
        $data = $this->get_sponsored_items();
        if ( ! $data ) {
            return;
        }

        $context = [
            'type'          => 'sponsored',
            '__context'       => 'standard',
            'stream_index'    => $index,
            'thumbnail_image' => '',
            'overline'        => 'Advertisement',
            'permalink'       => $data['url'],
            'title'           => $data['title'],
            'thumbnail_image' => $data['featured_image'],
            'source_name'     => $data['sponsored_by'],
            'source_image'    => Icons::get_icon( 'external-link' ),
            'source_link'     => $data['url'],
        ];
        $stream = new Stream;
        ob_start();
        echo $stream->get_the_stream_item( $context );
        return ob_get_clean();
    }

    /**
     * Injects inline ad units into a string of HTML
     *
     * @param  string $html HTML to have ads injected into it
     * @param  array  $args Optional arguments
     * @return string       Modified HTML
     */
    public function inject_inline_ads( $html = '', $args = [] ) {
        $defaults = [
            'ad_frequency' => 8,   // How often to inject ad between selectors
            'max_ads'      => 3,   // Maximum number of ads to inject
            'selector'     => 'p', // Selector to insert ads after
        ];
        $args = wp_parse_args( $args, $defaults );

        $debug_ads = false;
        if ( isset( $_GET['debug-inline-ads'] ) ) {
            $debug_ads = true;
        }
        if ( $debug_ads ) {
            if ( ! empty( $_GET['ad-frequency'] ) ) {
                $args['ad_frequency'] = intval( $_GET['ad-frequency'] );
            }

            if ( ! empty( $_GET['max-ads'] ) ) {
                $args['max_ads'] = intval( $_GET['max-ads'] );
            }
        }

        if ( empty( $html ) ) {
            return $html;
        }

        $dom = HtmlDomParser::str_get_html( $html );
        $nodes = $dom->find( $args['selector'] );

        // Weed out nodes that are children of another element
        foreach ( $nodes as $index => $node ) {
            if ( 'root' != $node->parent()->tag ) {
                unset( $nodes[ $index ] );
            }
        }

        // Reindex array
        $nodes = array_values( $nodes );

        // Remove the last node because an ad after the end of an article is off limits
        array_pop( $nodes );

        if ( count( $nodes ) < $args['ad_frequency'] ) {
            // Not enough elements to inject even 1 ad...
            return $html;
        }

        $ads_injected = 0;
        foreach ( $nodes as $index => $node ) {
            $ideal_position = $args['ad_frequency'] * ( $ads_injected + 1 );
            if ( $index + 1 < $ideal_position ) {
                continue;
            }

            $ad_unit_id = PEDESTAL_DFP_PREFIX . '_Inline';
            $ad_unit = self::render_dfp_unit( $ad_unit_id, '300x250', [
                'additional_classes' => 'dfp--inline dfp--inline--' . ( $index + 1 ),
            ] );

            // Insert the ad unit after the node
            $node->outertext = $node->outertext . $ad_unit;
            $ads_injected++;
            if ( $ads_injected >= $args['max_ads'] ) {
                break;
            }
        }

        $html = $dom->save();

        if ( $debug_ads ) {
            $html = '
            <style>
                body {
                    counter-reset: para;
                }
                .s-content > p::before,
                .c-factcheck__analysis > p::before {
                    counter-increment: para;
                    content: counter( para ) ") ";
                }
            </style>
            <div style="color: red;">Inline ads will be shown every ' . $args['ad_frequency'] . ' paragraphs, max of ' . $args['max_ads'] . ' ads<br><br></div>
            ' . $html;
        }
        return $html;
    }
}
