<?php
namespace Pedestal\Objects;

use Timber\Timber;
use Pedestal\Icons;
use Pedestal\Posts\{
    Attachment
};
use Pedestal\Posts\Post;
use Pedestal\Utils\Utils;
use Pedestal\Registrations\Post_Types\Types;

class Stream {

    /**
     * WP_Query object to use for the stream
     */
    private $query_obj;

    public function __construct( $query_obj = [] ) {
        global $wp_query;
        $this->query_obj = $wp_query;
        if ( $query_obj instanceof \WP_Query ) {
            $this->query_obj = $query_obj;
        }
    }

    /**
     * Get default stream item context values
     * @return array Default stream item context values
     */
    public function get_default_stream_item_context() {
        return [
            // Note: __context needs two underscores so as not to conflict with twig context variable
            // See standard-item.twig for descriptions of these values
            '__context'         => 'standard',
            'post'              => '',
            'type'              => '',
            'stream_index'      => '',
            'featured_image'    => '',
            'thumbnail_image'   => '',
            'overline'          => '',
            'overline_url'      => '',
            'title'             => '',
            'permalink'         => '',
            'date_time'         => '',
            'machine_time'      => '',
            'description'       => '',
            'author_names'      => '',
            'author_image'      => '',
            'author_link'       => '',
            'source_name'       => '',
            'source_image'      => '',
            'source_link'       => '',
            'is_footer_compact' => false,
        ];
    }

    /**
     * Get a rendered series of stream items
     * @return string Rendered markup of a stream
     */
    public function get_the_stream() {
        $wp_query = $this->query_obj;
        $html = '';
        foreach ( $wp_query->posts as $index => $post ) {
            $index++;
            $ped_post = Post::get( $post );
            if ( ! Types::is_post( $ped_post ) ) {
                continue;
            }
            $context = [
                '__context'         => 'standard', // Where is this stream item going to be displayed?
                'post'              => $post,
                'type'              => $ped_post->get_type(),
                'stream_index'      => $index,
                'title'             => $ped_post->get_the_title(),
                'permalink'         => $ped_post->get_the_permalink(),
                'date_time'         => $ped_post->get_the_datetime(),
                'machine_time'      => $ped_post->get_post_date( 'c' ),
                'description'       => $ped_post->get_the_excerpt(),
                'is_footer_compact' => false,
            ] + $this->get_default_stream_item_context();
            $context = apply_filters( 'pedestal_stream_item_context', $context );

            ob_start();
            do_action( 'pedestal_before_stream_item_' . $index, $post );
            echo $this->get_the_stream_item( $context );
            do_action( 'pedestal_after_stream_item_' . $index, $post );
            $html .= ob_get_clean();
        }
        return $html;
    }

    /**
     * Get a stream comprised of headlines
     * @return string Rendered markup of a stream list
     */
    public function get_the_stream_list() {
        $html = '';
        foreach ( $this->query_obj->posts as $index => $post ) {
            $index++;
            $ped_post = Post::get( $post );
            if ( ! Types::is_post( $ped_post ) ) {
                continue;
            }
            $context = [
                '__context'    => 'list', // Where is this stream item going to be displayed?
                'post'         => $post,
                'type'         => $ped_post->get_type(),
                'stream_index' => $index,
                'title'        => $ped_post->get_the_title(),
                'permalink'    => $ped_post->get_the_permalink(),
            ] + $this->get_default_stream_item_context();

            ob_start();
            do_action( 'pedestal_before_stream_list_item_' . $index, $post );
            echo $this->get_the_stream_item( $context );
            do_action( 'pedestal_after_stream_list_item_' . $index, $post );
            $html .= ob_get_clean();
        }
        return $html;
    }

    /**
     * Rendering method of an individual stream item
     *
     * @param  array $context Array of values to pass to the template
     * @return string         Rendered HTML markup of a stream item
     */
    public function get_the_stream_item( $context = [] ) {
        $stream_item_template = apply_filters( 'pedestal_stream_item_template', 'partials/stream/standard-stream-item.twig', $context );
        $full_template_path = get_template_directory() . '/views/' . $stream_item_template;
        if ( file_exists( $full_template_path ) ) {
            ob_start();
            Timber::render( $stream_item_template, $context );
            return ob_get_clean();
        }
    }

    /**
     * Get the data about pagination for the given query
     *
     * @param  array  $args Arguments to override values
     * @return object       Various pagination properties
     */
    public function get_pagination_data( $args = [] ) {
        global $wp_rewrite;
        $output = [
            'total_pages'   => 0,
            'current_page'  => 1,
            'next_url'      => false,
            'next_page_num' => -1,
            'prev_url'      => false,
            'prev_page_num' => 1,
            'links'         => [],
        ];

        // Setting up default values based on the current URL
        $current_page = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

        $total_pages = 1;
        if ( isset( $this->query_obj->max_num_pages ) ) {
            $total_pages = $this->query_obj->max_num_pages;
        }

        $pagenum_link = html_entity_decode( get_pagenum_link() );
        $url_parts = explode( '?', $pagenum_link );

        // Append the format placeholder to the base URL
        $pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

        // URL base depends on permalink settings
        $format = '';
        if ( $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ) {
            $format = 'index.php';
        }
        if ( $wp_rewrite->using_permalinks() ) {
            $format .= user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' );
        } else {
            $format .= '?paged=%#%';
        }

        $defaults = [
            'base'         => $pagenum_link, // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
            'format'       => $format, // ?page=%#% : %#% is replaced by the page number
            'total_pages'  => $total_pages,
            'current_page' => $current_page,
            'show_all'     => false,
            'range'        => 5,
            'add_args'     => [], // array of query args to add
        ];
        $args = wp_parse_args( $args, $defaults );

        // Who knows what else people pass in $args
        $output['total_pages'] = intval( $args['total_pages'] );
        if ( $output['total_pages'] < 2 ) {
            $output = apply_filters( 'pedestal_get_pagination_data', $output, $args );
            return (object) $output;
        }

        $current_page = intval( $args['current_page'] );
        $output['current_page'] = $current_page;

        $range = intval( $args['range'] ); // Out of bounds?  Make it the default
        if ( $range < 1 ) {
            $range = $defaults['range'];
        }

        if ( ! is_array( $args['add_args'] ) ) {
            $args['add_args'] = [];
        }

        // Merge additional query vars found in the original URL into 'add_args' array
        if ( isset( $url_parts[1] ) ) {
            // Find the format argument
            $format = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
            $format_query = isset( $format[1] ) ? $format[1] : '';
            wp_parse_str( $format_query, $format_args );

            // Find the query args of the requested URL
            wp_parse_str( $url_parts[1], $url_query_args );

            // Remove the format argument from the array of query arguments, to avoid overwriting custom format
            foreach ( $format_args as $format_arg => $format_arg_value ) {
                unset( $url_query_args[ $format_arg ] );
            }

            $args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
        }

        $prev_page_num = $current_page - 1;
        if ( $prev_page_num > 0 ) {
            $output['prev_page_num'] = $prev_page_num;
            $output['prev_url'] = $this->get_pagination_link( $prev_page_num, $args['base'], $args['format'], $args['add_args'] );
        }

        $next_page_num = $current_page + 1;
        if ( $next_page_num <= $total_pages ) {
            $output['next_page_num'] = $next_page_num;
            $output['next_url'] = $this->get_pagination_link( $next_page_num, $args['base'], $args['format'], $args['add_args'] );
        }

        if ( $current_page > ( $total_pages - $range ) ) {
            // We're near the end
            $start = max( $total_pages - $range + 1, 1 );
            $end = $total_pages;
        } elseif ( $current_page < $range ) {
            // We're near the beginning
            $start = 1;
            $end = $range;
        } else {
            // The Rest
            $start = $current_page - floor( $range / 2 );
            $end = $current_page + floor( $range / 2 );
        }
        for ( $i = $start; $i <= $end; $i++ ) {
            $is_current = false;
            if ( $i == $current_page ) {
                $is_current = true;
            }
            $output['links'][] = (object) [
                'num' => intval( $i ),
                'url' => $this->get_pagination_link( $i, $args['base'], $args['format'], $args['add_args'] ),
                'is_current' => $is_current,
            ];
        }

        $range_fraction = '';
        switch ( count( $output['links'] ) ) {
            case 5:
                $range_fraction = 'fifths';
                break;

            case 4:
                $range_fraction = 'fourths';
                break;

            case 3:
                $range_fraction = 'thirds';
                break;

            case 2:
                $range_fraction = 'halves';
                break;
        }
        $output['range_fraction'] = $range_fraction;
        $output['range'] = $range;
        $output = apply_filters( 'pedestal_get_pagination_data', $output, $args );
        return (object) $output;
    }

    /**
     * Get an individual pagination link
     *
     * @param  integer $num        The pagination number
     * @param  string  $base       Base URL for pagination
     * @param  string  $format     The pagination format
     * @param  array   $query_args Query arguments to append to the end of a link
     * @param  string  $fragment   Fragment to add to the end of a link
     * @return string              The link URL
     */
    public function get_pagination_link( $num = 0, $base = '', $format = '', $query_args = [], $fragment = '' ) {
        $link = str_replace( '%_%', 1 == $num ? '' : $format, $base );
        $link = str_replace( '%#%', $num, $link );
        if ( $query_args ) {
            $link = add_query_arg( $query_args, $link );
        }
        return $link .= $fragment;
    }

    /**
     * Get rendered pagination
     *
     * @param  array  $args      Args to modify pagination options
     * @param  array  $data_args Args to modify the data used to generate the pagination
     * @return string            Rendered HTML of pagination
     */
    public function get_pagination( $args = [], $data_args = [] ) {
        $pagination = $this->get_pagination_data( $data_args );
        if ( $pagination->total_pages <= 1 ) {
            return;
        }

        $default_next_text = Icons::get_icon( 'angle-right', 'c-pagination__dir__icon' ) . ' ';
        $default_next_text .= '<span class="c-pagination__dir__label">Next Page</span>';
        $default_prev_text = Icons::get_icon( 'angle-left', 'c-pagination__dir__icon' ) . ' ';
        $default_prev_text .= '<span class="c-pagination__dir__label">Previous Page</span>';

        $defaults = [
            'show_text' => true,
            'show_nav' => true,
            'next_text' => $default_next_text,
            'prev_text' => $default_prev_text,
        ];
        $args = wp_parse_args( $args, $defaults );

        if ( 1 > $pagination->total_pages ) {
            return;
        }

        $context = [
            'show_text'      => $args['show_text'],
            'show_nav'       => $args['show_nav'],
            'total_pages'    => $pagination->total_pages,
            'current_page'   => $pagination->current_page,
            'next_url'       => $pagination->next_url,
            'next_text'      => $args['next_text'],
            'prev_url'       => $pagination->prev_url,
            'prev_text'      => $args['prev_text'],
            'links'          => $pagination->links,
            'range_fraction' => $pagination->range_fraction,
        ];

        if ( $pagination->current_page == $pagination->next_page_num ) {
            $context['next_url'] = false;
        }

        if ( $pagination->current_page == $pagination->prev_page_num ) {
            $context['prev_url'] = false;
        }

        ob_start();
        Timber::render( 'partials/pagination.twig', $context );
        return ob_get_clean();
    }

    /**
     * Conditional for checking if the stream should be a list
     * @return boolean [description]
     */
    public function is_stream_list() {
        if ( is_tax( 'pedestal_source' ) ) {
            return false;
        }

        if ( is_tax() ) {
            return true;
        }

        if ( is_post_type_archive( Types::get_cluster_post_types() ) ) {
            return true;
        }

        return false;
    }

    /**
     * Conditional for checking if viewing the first page of a stream
     * @return boolean
     */
    public function is_first_page() {
        $pagination = $this->get_pagination_data();
        if ( $pagination->current_page > 1 ) {
            return false;
        }
        return true;
    }

    /**
     * Conditional for checking if viewing the last page of a stream
     * @return boolean
     */
    public function is_last_page() {
        $pagination = $this->get_pagination_data();
        if ( $pagination->current_page == $pagination->total_pages ) {
            return true;
        }
        return false;
    }
}
