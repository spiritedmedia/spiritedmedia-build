<?php

namespace Pedestal\CLI;

use function Pedestal\Pedestal;

use WP_CLI;

use Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

class CLI_Clusters extends \WP_CLI_Command {

    /**
     * Insert term, add its plural label, connect to parent if any
     *
     * @param  string  $slug   Term slug
     * @param  array   $term   Term data
     * @param  string  $tax    Taxonomy name
     * @param  integer $parent Optional parent term
     * @return integer         New term ID
     */
    private function _term_adder( $slug, $term, $tax, $parent = 0 ) {
        $args = [ 'slug' => $slug ];
        $msg = "Added type {$term['singular']}.";
        if ( 0 !== $parent ) {
            $msg = "Added type {$term['singular']} with parent {$parent}.";
            $args['parent'] = $parent;
        }
        $new_term = wp_insert_term( $term['singular'], $tax, $args );
        add_term_meta( $new_term['term_id'], 'plural', $term['plural'] );
        WP_CLI::line( $msg );
        return $new_term['term_id'];
    }

    /**
     * Set up Locality Type and the FM term select field
     *
     * The FM term select field must be set up explicitly or else, seeing
     * the lack of a defined value, it will overwrite the post's Locality
     * Type upon first post save.
     *
     * @param integer $post_id       Locality ID
     * @param string  $locality_type Locality Type slug
     */
    private function _set_locality_type( $post_id, $locality_type ) {
        wp_set_object_terms( $post_id, $locality_type, 'pedestal_locality_type' );
        $term_obj = get_term_by( 'slug', $locality_type, 'pedestal_locality_type' );
         // Although `term_id` is a numeric string, FM also stores the term
         // id as a numeric string
        $term_id = $term_obj->term_id;
        update_post_meta( $post_id, 'locality_type', $term_id );
    }

    /**
     * Scaffold default clusters
     *
     * @subcommand scaffold
     */
    public function scaffold_clusters( $args, $assoc_args ) {
        global $wpdb;

        WP_CLI::line( 'Loading default clusters and merging with site-specific clusters...' );
        $defaults = json_decode( file_get_contents( get_template_directory() . '/clusters-default.json' ), true );
        $site_clusters = json_decode( file_get_contents( get_stylesheet_directory() . '/clusters.json' ), true );
        $data = Utils::array_merge_recursive( $defaults, $site_clusters );

        // Register types for those clusters that have them
        WP_CLI::line( 'Registering Cluster Types...' );
        foreach ( $data as $k => $v ) {
            if ( isset( $v['types'] ) ) {
                $taxonomy_name = 'pedestal_' . $k . '_type';
                WP_CLI::line( "Registering {$k} types for {$taxonomy_name}..." );
                $count_terms = 0;
                foreach ( $v['types'] as $type_slug => $type ) {
                    if ( empty( term_exists( $type['singular'], $taxonomy_name ) ) ) {
                        $parent = $this->_term_adder( $type_slug, $type, $taxonomy_name );
                        $count_terms++;
                        if ( ! empty( $type['children'] ) ) {
                            foreach ( $type['children'] as $child_slug => $child ) {
                                if ( empty( term_exists( $child['singular'], $taxonomy_name ) ) ) {
                                    $this->_term_adder( $child_slug, $child, $taxonomy_name, $parent );
                                    $count_terms++;
                                }
                            }
                        }
                    }
                }
                WP_CLI::success( "Added {$count_terms} {$k} types." );
            } else {
                WP_CLI::line( "No types to create for {$k}." );
            }
        }

        $count_data = 0;
        $connectable = [];
        $data_len = count( $data );
        foreach ( $data as $k => $v ) :

            $post_type = 'pedestal_' . $k;
            $count_items = 0;
            WP_CLI::line( "Creating {$post_type} posts..." );
            foreach ( $v['items'] as $item_slug => $item ) {
                $post_args = [
                    'post_title'  => (string) $item['title'],
                    'post_name'   => $item_slug,
                    'post_type'   => $post_type,
                    'post_status' => 'publish',
                ];

                // Localities must have their type set
                if ( 'locality' === $k && ! isset( $item['type'] ) ) {
                    WP_CLI::error( "Locality type was not found for {$post_type} \"{$item['title']}\"!" );
                }

                // If an Org has an alias set, then set the post title to the
                // alias -- the configured title will be saved to the Full Name
                // post meta
                if ( 'org' === $k && ! empty( $item['alias'] ) ) {
                    $post_args['post_title'] = $item['alias'];
                }

                // Create the posts
                $post_id = wp_insert_post( $post_args );
                if ( empty( $post_id ) ) {
                    WP_CLI::error( "There was an error creating the {$post_type} \"{$item['title']}\"!" );
                }

                // Set up post meta and cluster types taxonomy terms
                switch ( $k ) {
                    case 'person':
                        add_post_meta( $post_id, 'person_name_prefix', $item['prefix'] );
                        add_post_meta( $post_id, 'person_name_first', $item['first'] );
                        add_post_meta( $post_id, 'person_name_middle', $item['middle'] );
                        add_post_meta( $post_id, 'person_name_nickname', $item['nickname'] );
                        add_post_meta( $post_id, 'person_name_last', $item['last'] );
                        add_post_meta( $post_id, 'person_name_suffix', $item['suffix'] );
                        break;

                    case 'locality':
                        $this->_set_locality_type( $post_id, $item['type'] );
                        if ( 'states' === $item['type'] ) {
                            add_post_meta( $post_id, 'state_details_abbr', strtoupper( $item_slug ) );
                        }
                        break;

                    case 'org':
                        // If the Org has an alias defined, then set the Org's
                        // full name to the configured title
                        if ( ! empty( $item['alias'] ) ) {
                            add_post_meta( $post_id, 'org_details_full_name', $item['title'] );
                        }

                        foreach ( $item['types'] as $type ) {
                            wp_set_object_terms( $post_id, $type, 'pedestal_org_type', true );
                        }
                        break;

                    case 'place':
                        foreach ( $item['types'] as $type ) {
                            wp_set_object_terms( $post_id, $type, 'pedestal_place_type', true );
                        }
                        break;
                }

                // If the item needs connections, save them for later after all
                // posts have been created
                if ( isset( $item['connections'] ) ) {
                    $connectable[] = [
                        'id'          => $post_id,
                        'connections' => $item['connections'],
                    ];
                }

                WP_CLI::line( "Added \"{$item['title']}\" with ID {$post_id}." );
                $count_items++;

            }

            WP_CLI::success( "Added {$count_items} {$k}." );

        endforeach;

        // All the posts are created so now we can make connections
        WP_CLI::line( 'Creating connections between localities and other clusters...' );
        foreach ( $connectable as $item ) :

            $post = Post::get_by_post_id( $item['id'] );
            $post_type = $post->get_type();
            $post_title = $post->get_title();

            foreach ( $item['connections'] as $connection ) {

                if ( ! isset( $connection['to'] ) ) {
                    WP_CLI::error( "The `to` property was not set for one of {$post_type} {$item['id']}'s connections!" );
                }

                if ( ! isset( $connection['rel'] ) ) {
                    WP_CLI::error( "The `rel` property was not set for one of {$post_type} {$item['id']}'s connections!" );
                }

                switch ( $post_type ) {
                    case 'org':
                        $from = 'organizations';
                        break;
                    case 'place':
                        $from = 'places';
                        break;
                    case 'locality':
                        $from = 'localities';
                        break;
                    default:
                        WP_CLI::error( "There is no registered locality connection type for the {$post_type} cluster type." );
                        break;
                }

                $from = $from . '_to_localities';
                $to_obj = new \WP_Query( [
                    'name'           => $connection['to'],
                    'post_type'      => 'pedestal_locality',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                ] );

                if ( empty( $to_obj->posts ) ) {
                    WP_CLI::error( "Slug {$connection['to']} specified in the `to` property for one of {$post_type} {$item['id']}'s connections did not match an existing Locality!" );
                }

                $to_obj = $to_obj->posts[0];
                p2p_type( $from )->connect( $item['id'], $to_obj->ID, [
                    'rel' => $connection['rel'],
                ] );
                WP_CLI::line( "Connected {$post_type} {$item['id']} \"{$post_title}\" to locality {$to_obj->ID} \"{$to_obj->post_title}\" with relationship {$connection['rel']}." );
            }

        endforeach;

        WP_CLI::success( 'Locality connections made!' );

        WP_CLI::success( 'Cluster scaffolding complete!' );
    }

    /**
     * Flip the direction of the Posts to Posts connections
     *
     * @subcommand flip-p2p-direction
     */
    public function flip_p2p_direction( $args, $assoc_args ) {
        WP_CLI::confirm( 'Do you want to update the P2P direction IDs in the database?' );

        $count_rows = 0;
        $stories_to_entities = $wpdb->get_results( "
            SELECT *
            FROM `wp_p2p`
            WHERE `p2p_type` = 'stories_to_entities'
            ORDER BY `p2p_id` ASC
        " );
        foreach ( $stories_to_entities as $row ) {
            $wpdb_update_data = [
                'p2p_from' => $row->p2p_to,
                'p2p_to'   => $row->p2p_from,
            ];
            $wpdb_update_where = [ 'p2p_id' => $row->p2p_id ];
            $wpdb->update( 'wp_p2p', $wpdb_update_data, $wpdb_update_where );
            WP_CLI::line( "Swapped `from`={$row->p2p_from} with `to`={$row->p2p_to} for connection {$row->p2p_id}." );
            $count_rows++;
        }

        WP_CLI::success( "Swapped 'from' IDs with 'to' IDs for {$count_rows} connections!" );
    }
}

WP_CLI::add_command( 'pedestal clusters', '\Pedestal\CLI\CLI_Clusters' );
