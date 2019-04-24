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

    /**
     * The content's embed type
     *
     * If not an embed, then will remain null.
     *
     * @var string|null
     */
    private $embed_type = null;

    /**
     * Figure HTML
     *
     * @var string
     */
    private $html = '';

    /**
     * Inner figure HTML content
     *
     * @var string
     */
    private $content = '';

    /**
     * Attributes
     *
     * @var array
     */
    private $atts = [];

    /**
     * Unique identifier for the supplied content
     *
     * @var string
     */
    private $hash = '';

    /**
     * YouTube ID if available
     *
     * @var string
     */
    private $youtube_id = '';

    /**
     * Whitelisted figure types
     *
     * @var array
     */
    private $allowed_types = [
        'embed',
        'img',
    ];

    /**
     * Default attributes
     *
     * @var array
     */
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

    /**
     * [constructor]
     *
     * @param string $type Figure type
     * @param string $content Inner figure content
     * @param array $atts Settings
     * @param string $embed_type The embed's service name
     */
    public function __construct( $type, $content, $atts = [], $embed_type = '' ) {
        $this->type = $type;
        if ( $type == 'embed' ) {
            $this->embed_type = $embed_type;
        }

        $this->content = $content;
        $this->atts    = wp_parse_args( $this->atts, $this->default_atts );
        $this->hash    = substr( md5( $this->content ), 0, 8 );

        $this->set_html();
    }

    /**
     * Get the rendered figure HTML
     *
     * @return string
     */
    public function get_html() {
        return $this->html;
    }

    /**
     * Set up the HTML
     */
    private function set_html() {
        if ( empty( $this->content ) || ! in_array( $this->type, $this->allowed_types ) ) {
            return '';
        }

        $id = $this->hash;
        if ( ! empty( $this->atts['attachment'] ) ) {
            $id .= '_' . $this->atts['attachment'];
        }
        $id     = esc_attr( $id );
        $capid  = 'id="figcaption_' . $id . '" ';
        $id_str = sprintf( 'id="figure_%s" ', $id );

        // Only use `aria-labelledby` if caption is present
        if ( ! empty( $this->atts['caption'] ) ) {
            $id_str .= sprintf( 'aria-labelledby="figcaption_%s" ', $id );
        }

        // Avoid parsing the DOM and making a responsive embed for Instagram
        // embeds because they contain SVGs which break the parser and Instagram
        // has its own way of handling responsiveness.
        //
        // All other embeds should be parsed.
        if ( 'embed' === $this->type && 'instagram' !== $this->embed_type ) {
            $this->prepare_embed();
        }

        $style = '';
        if ( $this->atts['style'] ) {
            $style = 'style="' . esc_attr( $this->atts['style'] ) . '"';
        }

        $this->context = [
            'type'                => $this->type,
            'id'                  => $id_str,
            'capid'               => $capid,
            'align'               => esc_attr( $this->atts['align'] ),
            'classes'             => $this->atts['classes'],
            'figcaption_classes'  => $this->atts['figcaption_classes'],
            'wrap_classes'        => $this->atts['wrap_classes'],
            'url'                 => $this->atts['url'],
            'ga_category'         => $this->atts['ga_category'],
            'ga_label'            => $this->atts['ga_label'],
            'content'             => $this->content,
            'caption'             => $this->atts['caption'],
            'caption_html'        => $this->atts['caption_html'],
            'credit'              => $this->atts['credit'],
            'credit_link'         => $this->atts['credit_link'],
            'element_figure_wrap' => $this->atts['element_figure_wrap'],
            'style'               => $style,
        ];

        // If the <img> is already wrapped in a <a> then don't double link it
        if ( '<' === $this->content[0] && 'a' === strtolower( $this->content[1] ) ) {
            $this->context['url'] = null;
        }

        if ( ! is_feed() && $this->youtube_id ) {
            $this->context['content'] = $this->render_youtube_placeholder();
        }

        $this->html = Timber::fetch( 'partials/figure.twig', $this->context );
    }

    /**
     * Prepare the embed by looking into its contents
     *
     * - Set up a responsive embed if responsiveness is enabled and width and
     *   height attributes are available
     * - Store the YouTube ID if we are dealing with a YouTube embed
     */
    private function prepare_embed() {
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
            // @codingStandardsIgnoreStart
            if ( 'iframe' === $node->nodeName && stristr( $node->getAttribute( 'src' ), 'youtube.com' ) ) {
                // @codingStandardsIgnoreEnd
                $youtube_url      = $node->getAttribute( 'src' );
                $parts            = explode( '/embed/', $youtube_url );
                $this->youtube_id = untrailingslashit( $parts[1] );
            }

        endforeach;

        if ( $width && $height && $is_responsive ) {
            $ratio = $height / $width * 100;
            if ( $height > $width ) {
                $ratio = $width / $height * 100;
            }
            $this->atts['style']    = 'padding-bottom: ' . $ratio . '%;';
            $this->atts['classes'] .= ' c-figure--responsive-iframe ';
        }
    }

    /**
     * Render a YouTube placeholder embed so we can lazy load videos
     *
     * @return string Content HTML
     */
    private function render_youtube_placeholder() {
        $youtube_id = $this->youtube_id;
        if ( empty( $youtube_id ) ) {
            return '';
        }

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

        $this->context['classes']      .= ' c-figure--youtube';
        $this->context['wrap_classes'] .= ' yt-placeholder';

        $placeholder_ga_category = $this->atts['content_ga_category'];
        if ( ! $placeholder_ga_category ) {
            $placeholder_ga_category = 'post-content';
            if ( Pedestal()->is_stream() ) {
                $placeholder_ga_category = 'stream-item';
            }
        }

        return Timber::fetch( 'partials/yt-placeholder.twig', [
            'id'          => $youtube_id,
            'url'         => $youtube_url,
            'img_src'     => $thumbnails['src'],
            'img_srcset'  => $srcset_attr,
            'ga_category' => $placeholder_ga_category,
        ] );
    }
}
