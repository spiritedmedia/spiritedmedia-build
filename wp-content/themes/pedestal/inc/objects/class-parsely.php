<?php

namespace Pedestal\Objects;

use Pedestal\Utils\Utils;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Parsely {

    /**
     * Arguments
     *
     * @var array
     */
    private $args = [];

    /**
     * Scope
     *
     * @var string
     */
    private $scope = '';

    /**
     * ID of post or user object
     *
     * @var integer
     */
    private $obj_id = 0;

    /**
     * Global base data
     *
     * @var array
     */
    private $base_data = [];

    /**
     * Parsely data
     *
     * @var array
     */
    private $data = [];

    public function __construct( $args = [] ) {
        $this->setup_args( $args );
        $this->set_base_data();
        $this->set_data();
    }

    /**
     * Setup and store args, scope, and object ID
     *
     * Uses conditional tags if in the loop, if not, will use the supplied args.
     *
     * @param  array $args
     */
    private function setup_args( $args ) {
        $scope = '';
        $id = 0;
        if ( is_single() || is_page() ) {
            $scope = 'post';
            $id = get_queried_object_id();
        } elseif ( is_author() ) {
            $scope = 'user';
        } elseif ( is_archive() ) {
            $scope = 'archive';
        } elseif ( is_home() ) {
            $scope = 'home';
        }

        $defaults = [
            'scope' => $scope,
            'id'    => $id,
        ];

        $args = wp_parse_args( $args, $defaults );

        $this->args = $args;
        $this->scope = $args['scope'];
        $this->obj_id = $args['id'];
    }

    /**
     * Set the global base data
     */
    private function set_base_data() {
        $this->base_data = [
            '@context' => 'http://schema.org',
            '@type'    => 'WebPage',
            'url'      => esc_url( home_url( Utils::get_request_uri() ) ),
        ];
    }

    /**
     * Set up data based on page context
     */
    private function set_data() {
        $args = $this->args;
        $base_data = $this->base_data;
        $scope = $args['scope'];

        if ( ! isset( $args['scope'] ) || empty( $args['scope'] ) ) {
            return '';
        }

        // If the specified scope is post or user,and the id is not valid, then return
        if ( in_array( $scope, [ 'post', 'user' ] )
            && ( ! isset( $args['id'] ) || empty( $args['id'] ) || ! is_int( $args['id'] ) ) ) {
            return '';
        }

        switch ( $scope ) {
            case 'post':
                $keywords = [];
                $post = Post::get( (int) $args['id'] );
                if ( ! is_a( $post, '\\Pedestal\\Posts\\Post' ) ) {
                    return '';
                }
                $headline = $this->str_sanitize( $post->get_title() );
                $schema = ( 'page' == $post->get_type() ) ? 'WebPage' : 'NewsArticle';
                if ( $post->is_entity() ) {
                    $clusters = $post->get_clusters( [
                        'types'   => Types::get_cluster_post_types(),
                        'flatten' => true,
                    ] );
                    if ( ! empty( $clusters ) ) {
                        foreach ( $clusters as $cluster ) {
                            $type = $cluster->get_type_name();
                            $type = strtolower( $type );
                            $keywords[] = $type . ' :: ' . $cluster->get_title();
                        }
                    }
                }
                $data = array_merge( $base_data, [
                    '@type'          => $schema,
                    'headline'       => $headline,
                    'url'            => $post->get_permalink(),
                    'thumbnailUrl'   => $post->get_featured_image_url(),
                    'articleId'      => (string) $post->get_id(),
                    'dateCreated'    => $post->get_post_date_gmt( 'c' ),
                    'articleSection' => '',
                    'creator'        => $post->get_author_names(),
                    'keywords'       => $keywords,
                ] );
                break;

            case 'user':
                $user = new User( $args['id'] );
                $headline = $this->str_sanitize( 'Author — ' . $user->get_display_name() );
                $data = array_merge( $base_data, [
                    'headline'       => $headline,
                    'url'            => $user->get_permalink(),
                ] );
                break;

            case 'archive':
                $headline = $this->str_sanitize( \Pedestal\Frontend::get_archive_title() );
                $data = array_merge( $base_data, [
                    'headline'       => $headline,
                ] );
                break;

            default:
                $data = array_merge( $base_data, [
                    'headline'       => $this->str_sanitize( get_bloginfo( 'name', 'raw' ) ),
                    'url'            => home_url(),
                ] );
                break;
        }// End switch().

        $this->data = $data;
    }

    /**
     * Get the data in JSON-LD format
     *
     * @return string A JSON-LD string surrounded by script tags
     */
    public function get_data() {
        $output = '<script type="application/ld+json">';
        $output .= json_encode( $this->data, JSON_UNESCAPED_SLASHES );
        $output .= '</script>';
        return $output;
    }

    /**
     * Sanitize string for Parse.ly
     *
     * @param  string $val The string to sanitize
     * @return string
     */
    private function str_sanitize( $val ) {
        if ( is_string( $val ) ) {
            $val = str_replace( "\n" , '', $val );
            $val = str_replace( "\r", '', $val );
            $val = strip_tags( $val );
            $val = trim( $val );
            return $val;
        } else {
            return $val;
        }
    }
}
