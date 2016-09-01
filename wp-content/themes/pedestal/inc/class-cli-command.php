<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use WP_CLI;

use joshtronic\LoremIpsum;

use Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

use \Pedestal\Objects\User;

class CLI_Command extends \WP_CLI_Command {

    /**
     * Migrate legacy Slot Item Sponsor meta to new format
     *
     * https://github.com/spiritedmedia/spiritedmedia/issues/1468
     */
    public function migrate_sponsorship_meta( $args, $assoc_args ) {
        $count_migrated = 0;
        $count_drafted = 0;

        $slot_items = new \Pedestal\Objects\Stream( [
            'post_type'      => 'pedestal_slot_item',
            'posts_per_page' => 500,
        ] );
        $slot_items = $slot_items->get_stream();

        foreach ( $slot_items as $slot_item ) {
            $legacy_url = $slot_item->get_meta( 'slot_item_details_url' );
            $legacy_attachment_id = $slot_item->get_meta( 'slot_item_details_img_upload' );
            $post_title = $slot_item->get_title();

            if ( empty( $legacy_url ) || empty( $legacy_attachment_id )
                || ! is_string( $legacy_url ) || ! is_string( $legacy_attachment_id ) ) {
                wp_update_post( [
                    'ID'          => $slot_item->get_id(),
                    'post_status' => 'draft',
                ] );
                WP_CLI::warning( "Slot Item \"{$post_title}\" with ID {$slot_item->get_id()} was saved as draft due to invalid legacy data!" );
                $count_drafted++;
                continue;
            }

            $term_sponsors = get_term_by( 'slug', 'sponsors-partners', 'pedestal_slot_item_type' );
            if ( ! empty( $term_sponsors ) && is_a( $term_sponsors, 'WP_Term' ) ) {
                $term_sponsors_id = (int) $term_sponsors->term_id;
            } else {
                WP_CLI::error( "The 'sponsors-partners' term does not exist! Something is amiss..." );
            }

            $slot_item->set_meta( 'slot_item_type', [
                'type' => $term_sponsors_id,
                'sponsorship' => [
                    'url'    => $legacy_url,
                    'label'  => 'Sponsored By',
                    'upload' => $legacy_attachment_id,
                ],
            ] );
            $slot_item->set_taxonomy_terms( 'pedestal_slot_item_type', 'sponsors-partners' );

            WP_CLI::line( "Slot Item \"{$post_title}\" with ID {$slot_item->get_id()} was successfully migrated to new format" );
            $count_migrated++;
        }

        $success_message = "Migrated {$count_migrated} slot item sponsors to new format.";
        if ( 0 !== $count_drafted ) {
            $success_message .= " {$count_drafted} slot items were saved as drafts due to invalid legacy data.";
        }
        WP_CLI::success( $success_message );
    }

    /**
     * Swap Org titles with alias
     */
    public function cluster_swap_alias_title( $args, $assoc_args ) {
        $count = 0;
        $orgs = new \Pedestal\Objects\Stream( [
            'post_type'      => 'pedestal_org',
            'posts_per_page' => -1,
        ] );
        $orgs = $orgs->get_stream();
        foreach ( $orgs as $org ) {
            $old_title = $org->get_title();
            $alias = $org->get_org_details_field( 'alias' );
            if ( empty( $alias ) ) {
                continue;
            }
            update_post_meta( $org->get_id(), 'org_details_full_name', $old_title );
            wp_update_post( [
                'ID' => $org->get_id(),
                'post_title' => $alias,
            ] );
            WP_CLI::line( "Swapped alias \"{$alias}\" and title \"{$old_title}\" for org {$org->get_id()}." );
            $count++;
        }
        WP_CLI::success( "Swapped alias and title for {$count} organizations." );
    }

    /**
     * Generate entities or stories
     *
     * ## OPTIONS
     *
     * <type>
     * : Post type to generate. Can be `article`, `link`, `embed`, `event`, or
     * `story`. Can also be `entity`, which will generate an entity of a random
     * type.
     *
     * [--count=<num>]
     * : Number of posts to generate. Defaults to 5.
     *
     * [--story=<id>]
     * : Story ID to connect these posts to. Post type must not be `story`.
     *
     * [--post_title=<title>]
     * : Title for the generated posts. Defaults to randomly generated lipsum.
     *
     * [--post_status=<status>]
     * : Status for the generated posts. Defaults to `publish`.
     *
     * [--post_author=<id>]
     * : Author ID for the posts. Defaults to author with ID `1`.
     *
     * [--maybe_story]
     * : When this flag is set, the story will only be set for a random number
     * of generated entities. The `--story-<id>` option must also be set.
     *
     * ## EXAMPLES
     *
     *     wp pedestal generate event --count=10
     *
     * @synopsis <type> [--count=<num>] [--story=<id>] [--post_title=<title>] [--post_status=<status>] [--post_author=<id>] [--maybe_story]
     */
    public function generate( $args, $assoc_args ) {

        // Define $assoc_args vars before extraction so we can check to see if
        // they've been set in $assoc_args
        $post_title = $post_status = $post_author = $story = $count = $maybe_story = $error = '';

        list( $post_type ) = $args;

        // @TODO
        // @codingStandardsIgnoreStart
        extract( $assoc_args );
        // @codingStandardsIgnoreEnd

        $lipsum = new LoremIpsum;
        $type = $post_type;
        $admins = get_users( [ 'role' => 'administrator' ] );
        $sources = get_terms( 'pedestal_source', [ 'fields' => 'ids' ] );
        $post_status = $post_status ? $post_status : 'publish';
        $count = $count ? $count : 1;

        if ( in_array( 'pedestal_' . $post_type, Types::get_post_types() ) ) {
            $post_type = 'pedestal_' . $post_type;
        }

        for ( $i = 0; $i < $count; $i++ ) :

            $post_content = '';

            if ( ! $post_title || 1 < $count ) {
                $post_title = ucfirst( $lipsum->words( mt_rand( 6, 15 ) ) );
            }

            // Set post author to a random admin user if not specified
            if ( ! $post_author ) {
                $post_author = $admins[ mt_rand( 0, count( $admins ) - 1 ) ]->ID;
            }

            $maybe_excerpt = mt_rand( 0, 1 ) ? $lipsum->sentence() : '';

            switch ( $type ) {

                case 'article':
                    $post_content = $lipsum->paragraphs( 5, 'p' );
                    break;

                case 'entity':
                    // Get a random entity post type
                    $entity_types = Types::get_entity_post_types();
                    $post_type = $entity_types[ mt_rand( 0, count( $entity_types ) - 1 ) ];
                    break;

            }

            if ( 'event' === $type ) {
                $post_excerpt = $lipsum->sentence();
            } else {
                $post_excerpt = $maybe_excerpt;
            }

            $post_type_class = Types::get_post_type_class( $post_type );
            $post = $post_type_class::create( [
                'post_title'   => $post_title,
                'post_status'  => $post_status,
                'post_content' => $post_content,
                'post_excerpt' => $post_excerpt,
                'post_author'  => $post_author,
            ] );

            switch ( $post->get_type() ) {

                case 'embed':
                    $post->set_embed_url( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' );
                    $post->update_embed_data();
                    break;

                case 'link':
                    $post->set_external_url( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' );
                    $source_id = (int) $sources[ mt_rand( 0, count( $sources ) - 1 ) ];
                    wp_set_object_terms( $post->get_id(), $source_id, 'pedestal_source' );
                    break;

                case 'event':
                    $post->set_event_details( [
                        'venue_name' => $lipsum->words( mt_rand( 2, 6 ) ),
                        'address'    => $lipsum->words( mt_rand( 3, 6 ) ),
                        'cost'       => 'Free',
                        'start_time' => time(),
                        'end_time'   => mt_rand( 0, 1 ) ? time() + 3600 : 0,
                    ] );
                    break;

                case 'story':
                    if ( mt_rand( 0, 1 ) ) {
                        $post->set_meta( 'headline', ucfirst( $lipsum->words( mt_rand( 4, 10 ) ) ) );
                    }
                    break;

            }

            $message = "Added new {$post->get_type()} with ID {$post->get_id()}";

            if ( Types::is_entity( $post_type ) || 'entity' === $post_type ) {
                if ( ( $story && ! $maybe_story ) || ( $story && $maybe_story && mt_rand( 0, 1 ) ) ) {
                    if ( Types::is_story( Post::get_post_type( Post::get_by_post_id( $story ) ) ) ) {
                        p2p_type( 'entities_to_stories' )->connect( $post->get_id(), $story );
                        $message .= " in story with ID {$story}";
                    } else {
                        $error = 'The `$story` specified is not a valid story ID. Posts were created but not connected to story.';
                    }
                }
            }

            WP_CLI::line( $message );

        endfor;

        if ( ! $error ) {
            if ( $count > 1 ) {
                WP_CLI::success( "Added {$count} new {$type} posts" );
            } else {
                WP_CLI::success( $message );
            }
        } else {
            WP_CLI::error( $error );
        }

    }

    /**
     * Subscribe users to daily newsletter and breaking news
     *
     * @subcommmand subscribe-users
     */
    public function subscribe_users() {

        $subscribed_count = 0;
        foreach ( get_users() as $user ) {

            if ( ! in_array( 'subscriber', $user->roles ) ) {
                WP_CLI::line( "Skipped - User isn't a subscriber" );
                continue;
            }

            $user = new \Pedestal\Objects\User( $user );
            if ( $user->is_subscribed_daily_newsletter() ) {
                WP_CLI::line( 'Skipped - User is already subscribed' );
                continue;
            }

            if ( wp_get_object_terms( $user->get_id(), 'pedestal_subscriptions' ) ) {
                WP_CLI::line( 'Skipped - User is already following a story' );
                continue;
            }

            $user->subscribe_daily_newsletter();
            WP_CLI::line( 'Subscribed user to daily newsletter and breaking news' );
            $subscribed_count++;

        }
        WP_CLI::success( "Subscribed {$subscribed_count} users to the newsletter" );

    }

    /**
     * Create some default terms
     *
     * ## EXAMPLES
     *
     *     wp pedestal scaffold-terms
     *
     * @subcommand scaffold-terms
     */
    public function scaffold_terms() {

        $default_terms = [
            'article_type' => [
                'lists' => [
                    'name' => 'List',
                    'meta' => [
                        'plural' => 'Lists',
                    ],
                ],
                'explainers' => [
                    'name' => 'Explainer',
                    'meta' => [
                        'plural' => 'Explainers',
                    ],
                ],
                'q-a' => [
                    'name' => 'Q&A',
                    'meta' => [
                        'plural' => 'Q&A',
                    ],
                ],
                'announcements' => [
                    'name' => 'Announcement',
                    'meta' => [
                        'plural' => 'Announcements',
                    ],
                ],
                'cta' => [
                    'name' => 'Call to Action',
                    'meta' => [
                        'plural' => 'Call to Action',
                    ],
                ],
                'partner-content' => [
                    'name' => 'Partner Content',
                    'meta' => [
                        'plural' => 'Partner Content',
                    ],
                ],
            ],
        ];

        $count_all_terms = 0;
        $count_taxs = 0;
        foreach ( $default_terms as $tax => $terms ) :
            $tax = 'pedestal_' . $tax;
            WP_CLI::line( "Registering terms for {$tax}..." );

            $count_terms = 0;
            foreach ( $terms as $slug => $term ) {
                $name = $term['name'];
                if ( empty( term_exists( $name, $tax ) ) ) {
                    $args = [ 'slug' => $slug ];
                    $new_term = wp_insert_term( $name, $tax, $args );
                    add_term_meta( $new_term['term_id'], 'plural', $term['meta']['plural'] );
                    WP_CLI::line( "Added term {$name}." );
                    $count_terms++;
                    $count_all_terms++;
                }
            };

            $count_taxs++;

        endforeach;

        WP_CLI::success( "Added {$count_all_terms} terms in {$count_taxs} taxonomies." );
    }

    /**
     * Scaffold default clusters
     *
     * @subcommand scaffold-clusters
     */
    public function scaffold_clusters( $args, $assoc_args ) {
        global $wpdb;

        $data = file_get_contents( get_template_directory() . '/data/clusters.json' );
        $data = json_decode( $data );

        // Insert term, add its plural label, connect to parent if any
        $func_term_adder = function( $term, $tax, $parent = 0 ) {
            $args = [ 'slug' => $term->slug ];
            $msg = "Added type {$term->singular}.";
            if ( 0 !== $parent ) {
                $msg = "Added type {$term->singular} with parent {$parent}.";
                $args['parent'] = $parent;
            }
            $new_term = wp_insert_term( $term->singular, $tax, $args );
            add_term_meta( $new_term['term_id'], 'plural', $term->plural );
            WP_CLI::line( $msg );
            return $new_term['term_id'];
        };

        // Set up Locality Type and the FM term select field
        //
        // The FM term select field must be set up explicitly or else, seeing
        // the lack of a defined value, it will overwrite the post's Locality
        // Type upon first post save
        $func_set_locality_type = function( $post_id, $locality_type ) {
            wp_set_object_terms( $post_id, $locality_type, 'pedestal_locality_type' );
            $term_obj = get_term_by( 'slug', $locality_type, 'pedestal_locality_type' );
             // Although `term_id` is a numeric string, FM also stores the term
             // id as a numeric string
            $term_id = $term_obj->term_id;
            update_post_meta( $post_id, 'locality_type', $term_id );
        };

        // Register types for those clusters that have them
        foreach ( $data as $k => $v ) {
            if ( property_exists( $v, 'types' ) ) {
                $taxonomy_name = 'pedestal_' . $k . '_type';
                WP_CLI::line( "Registering {$k} types for {$taxonomy_name}..." );
                $count_terms = 0;
                foreach ( $v->types as $type ) {
                    if ( empty( term_exists( $type->singular, $taxonomy_name ) ) ) {
                        $parent = $func_term_adder( $type, $taxonomy_name );
                        $count_terms++;
                        if ( ! empty( $type->children ) ) {
                            foreach ( $type->children as $child ) {
                                if ( empty( term_exists( $child->singular, $taxonomy_name ) ) ) {
                                    $func_term_adder( $child, $taxonomy_name, $parent );
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

        // Delete all existing Neighborhoods
        WP_CLI::confirm( 'Existing Neighborhoods must be deleted to use new structure. Continue?' );

        $hoods_query = new \WP_Query( [
            'posts_per_page' => -1,
            'post_type' => 'pedestal_hood',
        ] );
        if ( ! empty( $hoods_query->posts ) ) {
            $hoods_count = count( $hoods_query->posts );
            WP_CLI::line( "Deleting {$hoods_count} pedestal_hood posts..." );
            foreach ( $hoods_query->posts as $hood ) {
                $hood_id = $hood->ID;
                wp_delete_post( $hood_id, true );
                WP_CLI::line( "Deleted hood {$hood_id} \"{$hood->post_title}\"" );
            }
            WP_CLI::success( "Deleted {$hoods_count} existing hoods." );
        }

        $count_data = 0;
        $connectable = [];
        $data_len = count( $data );
        foreach ( $data as $k => $v ) :

            $post_type = 'pedestal_' . $k;
            $count_items = 0;
            WP_CLI::line( "Creating {$post_type} posts..." );
            foreach ( $v->items as $item ) {
                $post_args = [
                    'post_title'  => (string) $item->title,
                    'post_type'   => $post_type,
                    'post_status' => 'publish',
                ];

                if ( property_exists( $item, 'slug' ) ) {
                    $post_args['post_name'] = $item->slug;
                }

                if ( 'locality' === $k ) {
                    if ( ! property_exists( $item, 'type' ) ) {
                        WP_CLI::error( "Locality type was not found for {$post_type} \"{$item->title}\"!" );
                    }

                    if ( 'states' === $item->type ) {
                        $post_args['post_name'] = $item->abbr;
                    }
                }

                // Create the posts
                $post_id = wp_insert_post( $post_args );
                if ( empty( $post_id ) ) {
                    WP_CLI::error( "There was an error creating the {$post_type} \"{$item->title}\"!" );
                }

                // Set up post meta and cluster types taxonomy terms
                switch ( $k ) {
                    case 'person':
                        add_post_meta( $post_id, 'person_name_prefix', $item->prefix );
                        add_post_meta( $post_id, 'person_name_first', $item->first );
                        add_post_meta( $post_id, 'person_name_middle', $item->middle );
                        add_post_meta( $post_id, 'person_name_nickname', $item->nickname );
                        add_post_meta( $post_id, 'person_name_last', $item->last );
                        add_post_meta( $post_id, 'person_name_suffix', $item->suffix );
                        break;

                    case 'locality':
                        $func_set_locality_type( $post_id, $item->type );

                        if ( 'states' === $item->type ) {
                            add_post_meta( $post_id, 'state_details_abbr', $item->abbr );
                        }

                        // Add EveryBlock slug(s) to meta key `everyblock-slug`
                        if ( 'neighborhoods' === $item->type && property_exists( $item, 'everyblock_slug' ) ) {
                            $eb_slugs = $item->everyblock_slug;
                            if ( is_array( $eb_slugs ) ) {
                                $eb_slugs_str = implode( ', ', $eb_slugs );
                            } elseif ( is_string( $eb_slugs ) ) {
                                $eb_slugs_str = $eb_slugs;
                            }
                            add_post_meta( $post_id, 'everyblock-slug', $eb_slugs );
                            add_post_meta( $post_id, 'everyblock-updated', 0 );
                            add_post_meta( $post_id, 'powered-by', 'everyblock' );
                        }
                        break;

                    case 'org':
                        foreach ( $item->types as $type ) {
                            wp_set_object_terms( $post_id, $type, 'pedestal_org_type', true );
                        }
                        if ( ! empty( $item->alias ) ) {
                            add_post_meta( $post_id, 'org_details_alias', $item->alias );
                        }
                        break;

                    case 'place':
                        foreach ( $item->types as $type ) {
                            wp_set_object_terms( $post_id, $type, 'pedestal_place_type', true );
                        }
                        break;
                }

                // If the item needs connections, save them for later after all
                // posts have been created
                if ( property_exists( $item, 'connections' ) ) {
                    $connectable[] = [
                        'id'          => $post_id,
                        'connections' => $item->connections,
                    ];
                }

                WP_CLI::line( "Added \"{$item->title}\" with ID {$post_id}." );
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

                if ( ! property_exists( $connection, 'to' ) ) {
                    WP_CLI::error( "The `to` property was not set for one of {$post_type} {$item['id']}'s connections!" );
                }

                if ( ! property_exists( $connection, 'rel' ) ) {
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
                    'name'           => $connection->to,
                    'post_type'      => 'pedestal_locality',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                ] );

                if ( empty( $to_obj->posts ) ) {
                    WP_CLI::error( "Slug {$connection->to} specified in the `to` property for one of {$post_type} {$item['id']}'s connections did not match an existing Locality!" );
                }

                $to_obj = $to_obj->posts[0];
                p2p_type( $from )->connect( $item['id'], $to_obj->ID, [
                    'rel' => $connection->rel,
                ] );
                WP_CLI::line( "Connected {$post_type} {$item['id']} \"{$post_title}\" to locality {$to_obj->ID} \"{$to_obj->post_title}\" with relationship {$connection->rel}." );
            }

        endforeach;

        WP_CLI::success( 'Locality connections made!' );

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

        WP_CLI::success( 'Cluster scaffolding complete!' );
    }

    /**
     * Clear and update the oEmbed caches for a set of posts
     *
     * ## EXAMPLES
     *
     *     wp pedestal reset-oembed-cache $(wp post list --post_type=pedestal_article --format=ids)
     *
     * @subcommand reset-oembed-cache
     */
    public function reset_oembed_cache( $args, $assoc_args ) {
        $count = 0;
        foreach ( $args as $post_id ) {
            $GLOBALS['wp_embed']->delete_oembed_caches( $post_id );
            $GLOBALS['wp_embed']->cache_oembed( $post_id );
            WP_CLI::line( "Reset oEmbed cache for post {$post_id}." );
            $count++;
        }
        WP_CLI::success( "Reset oEmbed caches for {$count} posts." );
    }

    /**
     * Neutralize a database cloned from Live
     *
     * When cloning from a live database, we need to be able to work with users
     * safely without sending emails to real people.
     *
     * ## EXAMPLES
     *
     *     wp pedestal neutralize-db
     *
     * @subcommand neutralize-db
     */
    public function neutralize_db( $args, $assoc_args ) {
        global $wpdb;
        $count = 0;

        $users = $wpdb->get_results( "SELECT * FROM {$wpdb->users}" );
        foreach ( $users as $user ) {
            $new_email = Pedestal()->get_internal_email( $user->user_login );
            $wpdb->update( $wpdb->users,
                [ 'user_email' => $new_email ],
                [ 'ID' => $user->ID ]
            );
            WP_CLI::line( "Set email address for user {$user->ID} to {$new_email}." );
            $count++;
        }

        WP_CLI::success( "Neutralized email addresses for {$count} users." );
    }

    /**
     * Generate site-wide config.json file
     *
     * @subcommand generate-config
     */
    public function generate_config_file( $args, $assoc_args ) {
        if ( 'pedestal' !== wp_get_theme()->get_template() ) {
            WP_CLI::error( 'Pedestal is not the current theme template!' );
        }

        $pedestal_family = [];
        foreach ( wp_get_themes() as $theme ) {
            if ( 'pedestal' === $theme->get_template() ) {
                $pedestal_family[] = $theme->get_stylesheet();
            }
        }

        $image_sizes = [];
        $desired_image_sizes = [ 'thumbnail', 'medium', 'large', 'medium-square' ];
        foreach ( Utils::get_image_sizes() as $name => $details ) {
            if ( in_array( $name, $desired_image_sizes ) ) {
                unset( $details['crop'] );
                $image_sizes[ $name ] = $details;
            }
        }

        $config = [
            'wp' => [
                'themesPath' => PEDESTAL_WP_THEMES_PATH,
            ],
            'pedestal' => [
                'URI'        => get_template_directory_uri(),
                'liveURI'    => SPIRITEDMEDIA_PEDESTAL_LIVE_DIR,
                'stagingURI' => SPIRITEDMEDIA_PEDESTAL_STAGING_DIR,
                'path'       => PEDESTAL_WP_THEMES_PATH . '/pedestal',
                'family'     => $pedestal_family,
                'imageSizes' => $image_sizes,
                'children'   => [],
            ],
        ];

        // Set up some site-specific settings
        $sites = get_sites( [ 'site__not_in' => '1' ] );
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );

            $site_config = Pedestal()->get_site_config();
            $current_theme = wp_get_theme()->get_stylesheet();
            $current_theme_path = PEDESTAL_WP_THEMES_PATH . '/' . $current_theme;
            $config['pedestal']['children'][ $current_theme ] = [
                'siteName'     => $site_config['site_name'],
                'siteURL'      => get_site_url(),
                'siteLiveURL'  => $site_config['site_live_url'],
                'themeURI'     => get_stylesheet_directory_uri(),
                'themePath'    => $current_theme_path,
                'themeLiveURL' => $site_config['site_live_url'] . $current_theme_path,
                'brandColor'   => $site_config['site_branding_color'],
            ];

            restore_current_blog();
        }

        file_put_contents( ABSPATH . 'config/config.json', json_encode( $config, JSON_PRETTY_PRINT ) );
        WP_CLI::success( 'Generated config.json file.' );

        $sassy_config = $config;

        // Wrap string array values in single quotes for compatability with Sass
        // https://github.com/Updater/node-sass-json-importer#importing-strings
        array_walk_recursive( $sassy_config, function( &$value, $key ) {
            if ( is_string( $value ) ) {
                $value = "'" . $value . "'";
            }
        } );

        // Prefix first-level Sassy keys with `spiritedmedia`
        foreach ( $sassy_config as $key => $value ) {
            unset( $sassy_config[ $key ] );
            $sassy_config[ 'spiritedmedia-' . $key ] = $value;
        }

        file_put_contents( ABSPATH . 'config/config-sassy.json', json_encode( $sassy_config, JSON_PRETTY_PRINT ) );
        WP_CLI::success( 'Generated config-sassy.json file.' );
    }
}

WP_CLI::add_command( 'pedestal', '\Pedestal\CLI_Command' );
