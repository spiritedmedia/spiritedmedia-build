<?php

namespace Pedestal\Objects;

use Timber\Timber;

use \Pedestal\Utils\Utils;

use \Pedestal\Posts\Attachment;

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
        'attachment'   => 0,
        'classes'      => '',
        'wrap_classes' => '',
        'align'        => '',
        'url'          => '',
        'capid'        => '',
        'caption'      => '',
        'credit'       => '',
        'credit_link'  => '',
        'element_wrap' => '',
        'style'        => '',
    ];

    public function __construct( $type, $content, $atts = [] ) {
        $this->content = $content;
        $this->hash = substr( md5( $this->content ), 0, 8 );

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
        $id = esc_attr( $id );
        $capid = 'id="figcaption_' . $id . '" ';
        $id_str = sprintf( 'id="figure_%s" ', $id );
        $classes = $atts['classes'];
        $wrap_classes = $atts['wrap_classes'];
        $style = $atts['style'];
        $youtube_id = false;

        // Only use `aria-labelledby` if caption is present
        if ( ! empty( $atts['caption'] ) ) {
            $id_str .= sprintf( 'aria-labelledby="figcaption_%s" ', $id );
        }

        if ( 'embed' === $type || 'social-embed' === $type ) {
            // Let's figure out an aspect ratio...
            $width = '';
            $height = '';

            // via http://stackoverflow.com/a/3820783/1119655
            $dom = new \DOMDocument;
            $dom->loadHTML( $this->content );
            $xpath = new \DOMXPath( $dom );
            $nodes = $xpath->query( '//*[@width]' );
            $whitelisted_elements = [ 'script', 'iframe' ];
            foreach ( $nodes as $node ) {
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

                // @codingStandardsIgnoreStart
                if ( 'iframe' === $node->nodeName && is_feed( 'fias' ) ) {
                    // @codingStandardsIgnoreEnd
                    $atts['element_wrap'] = null;
                    $this->content = str_replace( 'class="', 'class="column-width ', $this->content );
                }

                // Is it a YouTube embed?
                $youtube_id = false;
                // @codingStandardsIgnoreStart
                if ( 'iframe' === $node->nodeName && stristr( $node->getAttribute( 'src' ), 'youtube.com' ) ) {
                    // @codingStandardsIgnoreEnd
                    $youtube_url = $node->getAttribute( 'src' );
                    $parts = explode( '/embed/', $youtube_url );
                    $youtube_id = untrailingslashit( $parts[1] );
                }
            }

            if ( $width && $height && $is_responsive ) {
                $classes = 'c-figure--responsive-iframe ' . $classes;
                $ratio = $height / $width * 100;
                if ( $height > $width ) {
                    $ratio = $width / $height * 100;
                }
                $style = 'padding-bottom: ' . $ratio . '%;';
            }
        }

        if ( $style ) {
            $style = 'style="' . esc_attr( $style ) . '"';
        }

        $context = [
            'type'         => $type,
            'id'           => $id_str,
            'capid'        => $capid,
            'align'        => esc_attr( $atts['align'] ),
            'classes'      => $classes,
            'wrap_classes' => $wrap_classes,
            'url'          => $atts['url'],
            'content'      => $this->content,
            'caption'      => $atts['caption'],
            'credit'       => $atts['credit'],
            'credit_link'  => $atts['credit_link'],
            'element_wrap' => $atts['element_wrap'],
            'style'        => $style,
        ];

        // If the <img> is already wrapped in a <a> then don't double link it
        if ( '<' === $content[0] && 'a' === strtolower( $content[1] ) ) {
            $context['url'] = null;
        }
        // Override YouTube embed content so we can lazy load videos
        if ( $youtube_id && ! is_feed( 'fias' ) ) {
            $thumbnail_sizes = [
                '120' => 'default.jpg',
                '320' => 'mqdefault.jpg',
                '480' => 'hqdefault.jpg',
                '640' => 'sddefault.jpg',
                '1280' => 'maxresdefault.jpg',
            ];
            $src_sets = [];
            foreach ( $thumbnail_sizes as $width => $suffix ) {
                $src_sets[] = 'https://img.youtube.com/vi/' . $youtube_id . '/' . $suffix . ' ' . $width . 'w';
            }
            $srcset_attr = implode( ', ', $src_sets );

            $youtube_url = add_query_arg( 'v', $youtube_id, 'https://www.youtube.com/watch' );
            $context['content'] = '<a href="' . esc_url( $youtube_url ) . '" class="c-yt-placeholder__link js-yt-placeholder-link" data-youtube-id="' . esc_attr( $youtube_id ) . '" target="_blank" data-ga-category="Embed|Video" data-ga-label="Load YouTube Video">';
            $context['content'] .= '<img src="https://img.youtube.com/vi/' . $youtube_id . '/sddefault.jpg" srcset="' . esc_attr( $srcset_attr ) . '" class="c-yt-placeholder__image">';
            $context['content'] .= '<span class="c-yt-placeholder__play-button fa fa-play">';
            $context['content'] .= '</span></a>';
            $context['classes'] .= ' c-figure--youtube';
            $context['wrap_classes'] .= 'c-yt-placeholder js-yt-placeholder';
        }

        if ( ! empty( $atts['attachment'] ) ) {
            $obj = new Attachment( $atts['attachment'] );
            if ( ! $atts['omit_presentation_mode'] ) {
                $context['fias_presentation'] = $obj->get_fias_presentation_mode( $atts['allow_fullscreen'] );
            } else {
                $context['fias_presentation'] = '';
            }
        }

        ob_start();
        $out = Timber::render( 'partials/figure.twig', $context );
        ob_get_clean();

        $this->html = $out;

    }
}
