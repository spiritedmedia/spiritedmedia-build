<?php

namespace Pedestal\Posts\Entities;

use \Timber\Timber;

use function Pedestal\Pedestal;
use \Pedestal\Utils\Utils;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Post;
use Pedestal\Posts\Clusters\Story;

abstract class Entity extends Post {

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
     * Cache a list of all post ids connected to this post
     *
     * @var array
     */
    private $all_connected_object_ids = [];

    /**
     * Set up the Post's HTML data attributes
     */
    protected function set_data_atts() {
        parent::set_data_atts();
        $atts     = parent::get_data_atts();
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

        $args    = [
            'types' => 'story',
            'count' => 1,
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
                $html = '<a href="%s" data-ga-category="post-footer" data-ga-label="cluster">%s</a>';
                if ( $args['accent_primary'] && 0 === $count ) {
                    $html = '<strong>' . $html . '</strong>';
                }
                $clusters_with_links[] = sprintf( $html,
                    esc_url( $cluster->get_permalink() ),
                    esc_html( $cluster->get_title() )
                );
                $count++;
            }
            return implode( ', ', $clusters_with_links );
        }
        return false;
    }

    /**
     * Get the non-story clusters associated with an entity
     *
     * @param array $args Args for getting clusters
     * @return array Array of Clusters
     */
    public function get_clusters( array $args = [] ) {
        $args       = wp_parse_args( $args, [
            'types'           => Types::get_cluster_post_types_sans_story(),
            'include_stories' => false,
            'flatten'         => true,
            'paginate'        => false,
            'count'           => 99,
            'count_only'      => false,
            'paged'           => 1,
        ] );
        $types      = $args['types'];
        $count      = $args['count'];
        $count_only = $args['count_only'];

        if ( is_string( $types ) ) {
            $types = [ $types ];
        }

        // Allow passing both full post type names and short post type names
        $types = array_map( [ '\Pedestal\Utils\Utils', 'remove_name_prefix' ], $types );
        $types = array_map( function( $type ) {
            return 'pedestal_' . $type;
        }, $types );

        $defaults   = [
            'post_type'       => $types,
            'post_status'     => 'publish',
            'posts_per_page'  => $count,
            'no_found_rows'   => true,
            'connected_type'  => Types::get_cluster_connection_types( $types ),
            'connected_items' => $this->post,
        ];
        $query_args = wp_parse_args( $args, $defaults );

        if ( $args['paginate'] || $count_only ) {
            $query_args['no_found_rows'] = false;
        }

        if ( $count_only ) {
            $query_args['update_post_meta_cache'] = false;
            $query_args['update_post_term_cache'] = false;
            $query_args['fields']                 = 'ids';
        }

        if ( $args['include_stories'] ) {
            $query_args['post_type'][] = 'pedestal_story';
        }

        // Reduce the strain of the query by narrowing the post IDs to search for
        // to only those that are connected to the current post
        if ( empty( $query_args['post__in'] ) && empty( $query_args['post__not_in'] ) ) {
            $query_args['post__in'] = $this->get_all_connected_object_ids();
        }

        $count = 0;
        $query = new \WP_Query( $query_args );
        if ( ! empty( $query->posts ) ) {

            if ( $count_only ) {
                return $query->found_posts;
            }

            $ped_posts = Post::get_posts_from_query( $query );
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

    /**
     * Get a list of all post IDs connected to this post
     *
     * @return array List of post IDs
     */
    public function get_all_connected_object_ids() {
        global $wpdb;

        if ( ! empty( $this->all_connected_object_ids[0] ) ) {
            return $this->all_connected_object_ids;
        }

        $post_ids                       = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT `p2p_to` FROM {$wpdb->p2p} WHERE `p2p_from` = %s",
                $this->get_id()
            )
        );
        $this->all_connected_object_ids = $post_ids;
        return $post_ids;
    }

    /**
     * Get the Twig context for this post
     *
     * @param array Existing context to filter
     * @return array Twig context
     */
    public function get_context( $context = [] ) {
        $context = parent::get_context( $context );

        $story = $this->get_primary_story();
        if ( $story ) {
            $context['overline']     = $story->get_the_title();
            $context['overline_url'] = $story->get_the_permalink();
        }

        $cluster_list_context = [
            'clusters' => $this->get_clusters_with_links(),
        ];
        ob_start();
        Timber::render( 'partials/cluster-list.twig', $cluster_list_context );
        $context['cluster_list'] = ob_get_clean();
        return $context;
    }

    /**
     * Get the Category term object associated with this entity
     *
     * @return WP_Term|false The category term object or false if not found
     */
    public function get_category_term() {
        $terms = wp_get_object_terms( $this->get_id(), 'pedestal_category' );
        if ( ! is_wp_error( $terms ) && ! empty( $terms[0] ) ) {
            return $terms[0];
        }
        return false;
    }
}
