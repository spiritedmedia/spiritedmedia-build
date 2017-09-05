<?php

namespace Pedestal\Posts\Entities;

use function Pedestal\Pedestal;
use \Pedestal\Utils\Utils;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Post;
use Pedestal\Posts\Clusters\Story;

class Entity extends Post {

    /**
     * Primary Story
     *
     * Multiple Stories may be connected to an Entity, but only one will be
     * featured as its "primary" Story, which is displayed in the frontend as a
     * "story bar" attached to the entity.
     *
     * @var Story
     */
    protected $primary_story;

    /**
     * Query vars for connected Story ordering
     *
     * @var array
     */
    private $story_connection_order_vars = [
        'connected_order_num' => true,
        'connected_orderby'   => '_order_from',
        'connected_order'     => 'asc',
    ];

    /**
     * Set up the Post's HTML data attributes
     */
    protected function set_data_atts() {
        parent::set_data_atts();
        $atts = parent::get_data_atts();
        $new_atts = [
            'entity'          => '',
            'source-external' => '',
        ];

        $story = $this->get_primary_story();
        if ( $story && Types::is_story( $story ) ) {
            $new_atts['primary-story'] = $story->get_id();
        }

        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Is the Entity connected to more than one Story?
     *
     * @return boolean
     */
    public function has_multiple_stories() {
        $stories = $this->get_clusters( [
            'types'      => 'story',
            'count'      => 2,
            'count_only' => true,
        ] );
        if ( 1 < $stories ) {
            return true;
        }
        return false;
    }

    /**
     * Whether or not this entity has a story
     *
     * @return boolean
     */
    public function has_story() {
        return (bool) $this->get_clusters( [
            'types'      => 'story',
            'count'      => 1,
            'count_only' => true,
        ] );
    }

    /**
     * Get the Primary Story associated with an Entity
     *
     * The Primary Story is the first Story connected to an Entity as ordered
     * from the Story connection metabox.
     *
     * @return Story|false
     */
    public function get_primary_story() {
        if ( isset( $this->primary_story ) ) {
            return $this->primary_story;
        }

        $args = [
            'flatten' => true,
            'types'   => 'story',
            'count'   => 1,
        ] + $this->story_connection_order_vars;
        $stories = $this->get_clusters( $args );
        if ( ! empty( $stories ) ) {
            $this->primary_story = $stories[0];
        } else {
            $this->primary_story = false;
        }

        return $this->primary_story;
    }

    /**
     * Get a comma-separated list of this entity's non-story Clusters
     *
     * @param string|array $types Optional cluster type(s) to narrow list
     * @param array        $args  Additional args to set up the query -- note
     *     that the post type(s) set in $type will override anything set here
     * @return string|false HTML
     */
    public function get_clusters_with_links( $types = '', array $args = [] ) {
        $clusters_with_links = [];

        $args = wp_parse_args( $args, [
            'flatten'        => true,
            'accent_primary' => false,
        ] );

        if ( 'story' === $types ) {
            $story_args = $this->story_connection_order_vars;
            if ( is_admin() ) {
                $story_args['accent_primary'] = true;
            }
            $args = $story_args + $args;
        }

        if ( ! empty( $types ) && ( is_string( $types ) || is_array( $types ) ) ) {
            $args['types'] = $types;
        }

        $clusters = $this->get_clusters( $args );
        if ( ! empty( $clusters ) ) {
            $count = 0;
            foreach ( $clusters as $cluster ) {
                $html = '<a href="%s" data-ga-category="Cluster Link" data-ga-label="%s">%s</a>';
                $ga_label = $cluster->get_type_name() . '|' . $cluster->get_title();
                if ( $args['accent_primary'] && 0 === $count ) {
                    $html = '<strong>' . $html . '</strong>';
                }
                $clusters_with_links[] = sprintf( $html,
                    esc_url( $cluster->get_permalink() ),
                    esc_attr( $ga_label ),
                    esc_html( $cluster->get_title() )
                );
                $count++;
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
        return (bool) $this->get_clusters( [
            'count'      => 1,
            'count_only' => true,
        ] );
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
            'count'      => 99,
            'count_only' => false,
            'paged'      => 1,
        ] );
        $types = $args['types'];
        $count = $args['count'];
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
            'posts_per_page'  => $count,
            'no_found_rows'   => true,
            'connected_type'  => Types::get_cluster_connection_types( $types ),
            'connected_items' => $this->post,
        ] + $args;

        if ( $args['paginate'] || $count_only ) {
            $query_args['no_found_rows'] = false;
        }

        if ( $count_only ) {
            $query_args['update_post_meta_cache'] = false;
            $query_args['update_post_term_cache'] = false;
            $query_args['fields'] = 'ids';
        }

        $count = 0;
        $query = new \WP_Query( $query_args );
        if ( ! empty( $query->posts ) ) {

            if ( $count_only ) {
                return $query->found_posts;
            }

            $ped_posts = Post::get_posts( $query );
            if ( $args['flatten'] ) {
                return $ped_posts;
            }

            $clusters = [];
            foreach ( $ped_posts as $cluster ) {
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
}
