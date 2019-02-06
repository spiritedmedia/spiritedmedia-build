<?php

namespace Pedestal\Objects;

use function Pedestal\Pedestal;

use Timber\Timber;

use Pedestal\Icons;
use Pedestal\Utils\Utils;
use Pedestal\Objects\YouTube;
use Pedestal\Posts\Attachment;
use Pedestal\Posts\Entities\Embed;
use Pedestal\Registrations\Post_Types\Types;

/**
 * Figure
 */
class Figure {

    public $html = '';

    public $content = '';

    private $hash = '';

    private $allowed_types = [
        'embed',
        'img',
        'social-embed',
        'script',
    ];

    private $default_atts = [
        'attachment'          => 0,
        'classes'             => '',
        'figcaption_classes'  => '',
        'wrap_classes'        => '',
        'align'               => '',
        'url'                 => '',
        'capid'               => '',
        'caption'             => '',
        'caption_html'        => '',
        'credit'              => '',
        'credit_link'         => '',
        'element_figure_wrap' => '',
        'style'               => '',
        'ga_category'         => '',
        'ga_label'            => '',
        'content_ga_category' => '',
    ];

    public function __construct( $type, $content, $atts = [] ) {
        $this->content = $content;
        $this->hash    = substr( md5( $this->content ), 0, 8 );

        $this->setup_figure( $type, $content, $atts );
    }

    public function get_html() {
        return $this->html;
    }

    private function setup_figure( $type, $content, $atts = [] ) {
        $atts = wp_parse_args( $atts, $this->default_atts );

        if ( empty( $content ) || ! in_array( $type, $this->allowed_types ) ) {
            return '';
        }

        if ( ! isset( $atts['allow_fullscreen'] ) ) {
            $atts['allow_fullscreen'] = false;
        }

        // Cover images should not use a presentation mode, so allow this option
        if ( ! isset( $atts['omit_presentation_mode'] ) ) {
            $atts['omit_presentation_mode'] = false;
        }

        $id = $this->hash;
        if ( ! empty( $atts['attachment'] ) ) {
            $id .= '_' . $atts['attachment'];
        }
        $id           = esc_attr( $id );
        $capid        = 'id="figcaption_' . $id . '" ';
        $id_str       = sprintf( 'id="figure_%s" ', $id );
        $classes      = $atts['classes'];
        $wrap_classes = $atts['wrap_classes'];
        $style        = $atts['style'];
        $youtube_id   = false;

        // Only use `aria-labelledby` if caption is present
        if ( ! empty( $atts['caption'] ) ) {
            $id_str .= sprintf( 'aria-labelledby="figcaption_%s" ', $id );
        }

        if ( 'embed' === $type || 'social-embed' === $type ) :

            // Let's figure out an aspect ratio...
            $width  = '';
            $height = '';

            // via http://stackoverflow.com/a/3820783/1119655
            $dom = new \DOMDocument;
            $dom->loadHTML( $this->content );
            $xpath                = new \DOMXPath( $dom );
            $nodes                = $xpath->query( '//*[@width]' );
            $whitelisted_elements = [ 'script', 'iframe' ];

            foreach ( $nodes as $node ) :

                // @codingStandardsIgnoreStart
                if ( ! in_array( $node->nodeName, $whitelisted_elements ) ) {
                    // @codingStandardsIgnoreEnd
                    continue;
                }

                $is_responsive = true;

                // via http://stackoverflow.com/a/12582416/1119655
                $width = $node->getAttribute( 'width' );
                if ( '100%' === $width ) {
                    $is_responsive = false;
                }
                $width = filter_var( $width, FILTER_SANITIZE_NUMBER_INT );

                $height = $node->getAttribute( 'height' );
                $height = filter_var( $height, FILTER_SANITIZE_NUMBER_INT );

                $class = $node->getAttribute( 'class' );
                if ( stripos( $class, 'disable-responsiveness' ) ) {
                    $is_responsive = false;
                }

                // Is it a YouTube embed?
                $youtube_id = false;
                // @codingStandardsIgnoreStart
                if ( 'iframe' === $node->nodeName && stristr( $node->getAttribute( 'src' ), 'youtube.com' ) ) {
                    // @codingStandardsIgnoreEnd
                    $youtube_url = $node->getAttribute( 'src' );
                    $parts       = explode( '/embed/', $youtube_url );
                    $youtube_id  = untrailingslashit( $parts[1] );
                }

            endforeach;

            if ( $width && $height && $is_responsive ) {
                $classes = 'c-figure--responsive-iframe ' . $classes;
                $ratio   = $height / $width * 100;
                if ( $height > $width ) {
                    $ratio = $width / $height * 100;
                }
                $style = 'padding-bottom: ' . $ratio . '%;';
            }

        endif;

        if ( $style ) {
            $style = 'style="' . esc_attr( $style ) . '"';
        }

        $context = [
            'type'                => $type,
            'id'                  => $id_str,
            'capid'               => $capid,
            'align'               => esc_attr( $atts['align'] ),
            'classes'             => $classes,
            'figcaption_classes'  => $atts['figcaption_classes'],
            'wrap_classes'        => $wrap_classes,
            'url'                 => $atts['url'],
            'ga_category'         => $atts['ga_category'],
            'ga_label'            => $atts['ga_label'],
            'content'             => $this->content,
            'caption'             => $atts['caption'],
            'caption_html'        => $atts['caption_html'],
            'credit'              => $atts['credit'],
            'credit_link'         => $atts['credit_link'],
            'element_figure_wrap' => $atts['element_figure_wrap'],
            'style'               => $style,
        ];

        // If the <img> is already wrapped in a <a> then don't double link it
        if ( '<' === $content[0] && 'a' === strtolower( $content[1] ) ) {
            $context['url'] = null;
        }
        // Override YouTube embed content so we can lazy load videos
        if ( isset( $youtube_id ) && $youtube_id ) {
            $src_sets    = [];
            $youtube     = new YouTube;
            $youtube_url = $youtube::get_url_from_id( $youtube_id );
            $thumbnails  = $youtube->get_video_thumbnails( $youtube_url );
            if ( empty( $thumbnails ) || empty( $thumbnails['src'] ) || empty( $thumbnails['srcset'] ) ) {
                return;
            }
            foreach ( $thumbnails['srcset'] as $width => $url ) {
                $src_sets[] = $url . ' ' . $width . 'w';
            }
            $srcset_attr = implode( ', ', $src_sets );

            $context['classes']      .= ' c-figure--youtube';
            $context['wrap_classes'] .= ' yt-placeholder';

            $placeholder_ga_category = $atts['content_ga_category'];
            if ( ! $placeholder_ga_category ) {
                $placeholder_ga_category = 'post-content';
                if ( Pedestal()->is_stream() ) {
                    $placeholder_ga_category = 'stream-item';
                }
            }

            ob_start();
            Timber::render( 'partials/yt-placeholder.twig', [
                'id'          => $youtube_id,
                'url'         => $youtube_url,
                'img_src'     => $thumbnails['src'],
                'img_srcset'  => $srcset_attr,
                'ga_category' => $placeholder_ga_category,
            ] );
            $context['content'] = ob_get_clean();
        }

        ob_start();
        $out = Timber::render( 'partials/figure.twig', $context );
        ob_get_clean();

        $this->html = $out;

    }
}
