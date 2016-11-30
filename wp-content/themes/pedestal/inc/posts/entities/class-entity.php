<?php

namespace Pedestal\Posts\Entities;

use function Pedestal\Pedestal;

use \Pedestal\Utils\Utils;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Posts\Post;

use Pedestal\Objects\Stream;

use Pedestal\Posts\Clusters\Story;

abstract class Entity extends Post {

    protected $story;

    protected $clusters;

    /**
     * Get CSS classes
     *
     * @return array
     */
    public function get_css_classes() {
        $classes = array_merge( [
            'entity--' . $this->get_type(),
            'entity',
        ], parent::get_css_classes() );
        return $classes;
    }

    /**
     * Set up the Post's HTML data attributes
     */
    protected function set_data_atts() {
        parent::set_data_atts();
        $atts = parent::get_data_atts();
        $new_atts = [
            'entity' => '',
        ];

        if ( $this->has_story() ) {
            $new_atts['in-story'] = $this->get_story()->get_id();
        }

        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Whether or not this entity has a story
     *
     * @return bool
     */
    public function has_story() {
        return (bool) $this->get_story();
    }

    /**
     * Get the Entity's Story title wrapped in a link
     *
     * @return string HTML
     */
    public function get_story_with_link() {
        $story = $this->get_story();
        if ( ! empty( $story ) ) {
            return '<a href="' . esc_url( $story->get_permalink() ) . '">' . esc_html( $story->get_title() ) . '</a>';
        }
    }

    /**
     * Get the story associated with an entity
     *
     * @param array
     * @return Story|false
     */
    public function get_story() {

        if ( isset( $this->story ) ) {
            return $this->story;
        }

        $args = [
            'post_type'         => [ 'pedestal_story' ],
            'post_status'       => 'publish',
            'posts_per_page'    => 1,
            'connected_type'    => 'entities_to_stories',
            'connected_items'   => $this->post,

            // for performance
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];

        $query = new \WP_Query( $args );
        if ( ! empty( $query->posts ) ) {
            $this->story = new Story( $query->posts[0] );
        } else {
            $this->story = false;
        }

        return $this->story;
    }

    /**
     * Get a comma-separated list of this entity's non-story Clusters
     *
     * @param string|array $type Optional cluster type(s) to narrow list
     * @return string|false HTML
     */
    public function get_clusters_with_links( $types = '' ) {
        $clusters_with_links = [];
        $cluster_args = [ 'flatten' => true ];

        if ( ! empty( $types ) && ( is_string( $types ) || is_array( $types ) ) ) {
            $cluster_args['types'] = $types;
        }

        $clusters = $this->get_clusters( $cluster_args );
        if ( ! empty( $clusters ) ) {
            foreach ( $clusters as $cluster ) {
                $clusters_with_links[] = '<a href="' . esc_url( $cluster->get_permalink() ) . '">' . esc_html( $cluster->get_title() ) . '</a>';
            }
            return implode( ', ', $clusters_with_links );
        }
        return false;
    }

    /**
     * Check whether the entity has any non-story clusters
     *
     * @return boolean
     */
    public function has_clusters() {
        return (bool) $this->get_clusters( [ 'count_only' => true ] );
    }

    /**
     * Get the non-story clusters associated with an entity
     *
     * @param array $args Args for getting clusters
     * @return array Array of Clusters
     */
    public function get_clusters( array $args = [] ) {
        $args = wp_parse_args( $args, [
            'types'      => Types::get_cluster_post_types_sans_story(),
            'flatten'    => false,
            'paginate'   => false,
            'count_only' => false,
        ] );
        $types = $args['types'];
        $count_only = $args['count_only'];

        // Allow passing both full post type names and short post type names
        if ( is_string( $types ) ) {
            $types = [ $types ];
        }
        $types = array_map( [ '\Pedestal\Utils\Utils', 'remove_name_prefix' ], $types );
        $types = array_map( function( $type ) {
            return 'pedestal_' . $type;
        }, $types );

        $query_args = [
            'post_type'       => $types,
            'post_status'     => 'publish',
            'posts_per_page'  => 99,
            'no_found_rows'   => true,
            'connected_type'  => Types::get_connection_types_entities_to_clusters(),
            'connected_items' => $this->post,
        ];

        if ( $args['paginate'] || $count_only ) {
            $query_args['no_found_rows'] = false;
        }

        if ( $count_only ) {
            $query_args['update_post_meta_cache'] = false;
            $query_args['update_post_term_cache'] = false;
            $query_args['fields'] = 'ids';
        }

        $count = 0;
        $stream = new Stream( $query_args );
        if ( $stream->has_posts() ) {
            $clusters = [];

            if ( $args['flatten'] ) {
                return $stream->get_stream();
            }

            if ( $count_only ) {
                return count( $stream->get_posts() );
            }

            foreach ( $stream->get_stream() as $cluster ) {
                $cluster_type = $cluster->get_type();
                if ( ! isset( $clusters[ $cluster_type ] ) ) {
                    $clusters[ $cluster_type ] = [];
                }
                $clusters[ $cluster_type ][] = $cluster;
            }

            return $clusters;
        }
        return false;
    }

    /**
     * Check if entity is currently pinned
     *
     * If the pin feature is turned off, this will return false.
     *
     * @return boolean
     */
    public function is_pinned() {
        if ( $this->get_id() === Pedestal()->get_pinned_data()['content'] ) {
            return true;
        } else {
            return false;
        }
    }
}
