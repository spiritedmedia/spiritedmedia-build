<?php

namespace Pedestal\CLI;

use function Pedestal\Pedestal;

use WP_CLI;

use joshtronic\LoremIpsum;

use Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

use Pedestal\Posts\Slots\Slot_Item;

use Pedestal\Objects\{Stream, User, ActiveCampaign};

class CLI extends \WP_CLI_Command {

    /**
     * Migrate legacy Slot Item Sponsor meta to new format
     */
    public function migrate_sponsorship_meta( $args, $assoc_args ) {
        $slot_item_count_migrated = 0;
        $slot_item_count_drafted = 0;

        $slot_items = Stream::get( [
            'post_type'      => 'pedestal_slot_item',
            'posts_per_page' => 500,
            // We don't want to process the really old format
            'meta_query'     => [
                [
                    'key'     => 'slot_item_details_slot',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ] );

        if ( empty( $slot_items ) ) {
            WP_CLI::line( 'No slot items found (；一_一)' );
        }

        foreach ( $slot_items as $slot_item ) :

            $defaults = $slot_item->get_meta( 'slot_item_placement_defaults' );
            $placements = $slot_item->get_placement_rules();

            // Specific migration changes go here, between the getting and setting of placements
            // ...

            $slot_item->set_meta( 'slot_item_placement_defaults', $defaults );
            $slot_item->set_meta( 'slot_item_placement_rules', $placements );
            $_POST['slot_item_placement_defaults'] = $defaults;
            $_POST['slot_item_placement_rules'] = $placements;

            // We update the post here to trigger the `save_post_pedestal_slot_item` hook
            wp_update_post( [ 'ID' => $slot_item->get_id() ] );

            WP_CLI::line( "Slot Item \"{$slot_item->get_title()}\" with ID {$slot_item->get_id()} was successfully migrated to new format" );
            $slot_item_count_migrated++;

        endforeach;

        $success_message = "(ﾉ◕ヮ◕)ﾉ*:･ﾟ✧ Migrated {$slot_item_count_migrated} slot items to new format!";
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
                    if ( Types::is_story( Types::get_post_type( Post::get_by_post_id( $story ) ) ) ) {
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

    /**
     * Update Amazon S3 meta data for media items
     *
     * The WP Offload S3 plugin we use requires the presence of this meta data
     * before rewriting URLs.
     *
     * ## EXAMPLES
     *
     *     wp pedestal update-aws-media-meta
     *
     * @subcommand update-aws-media-meta
     */
    public function update_s3_meta_for_media( $args, $assoc_args ) {
        global $wpdb;

        // Get the options for the WP Offload S3 plugin
        $options = get_site_option( 'tantan_wordpress_s3' );
        if ( ! isset( $options['bucket'] ) || ! $options ) {
            WP_CLI::error( 'No S3 Bucket is defined. Exiting...' );
            exit;
        }
        $bucket_name = $options['bucket'];

        // The array we're going to store for each item
        $meta_value = [
            'bucket' => $bucket_name,
            'key' => '',
            'region' => '',
        ];

        // Get the path prefix for the S3 key value
        // i.e. /wp-content/uploads/sites/2
        $wp_upload_dir = wp_upload_dir();
        $path_prefix = str_replace( trailingslashit( get_site_url() ), '', $wp_upload_dir['baseurl'] );
        $path_prefix = trailingslashit( $path_prefix );
        if ( '/' == $path_prefix[0] ) {
            $path_prefix = ltrim( $path_prefix . '/' );
        }

        // Get all of the items that have a '_wp_attached_file' meta_key set and
        // don't have the  'amazonS3_info' already set
        $rows = $wpdb->get_results( $wpdb->prepare( "
            SELECT `post_id`, `meta_key`, `meta_value`
            FROM `$wpdb->postmeta`
            WHERE `meta_key` = '_wp_attached_file'
                AND `post_id` NOT IN (
                    SELECT `post_id`
                    FROM `$wpdb->postmeta`
                    WHERE `meta_key` = '%s'
                )
        ", [ 'amazonS3_info' ] ) );
        $total_rows = count( $rows );
        foreach ( $rows as $index => $row ) {
            if ( '_wp_attached_file' != $row->meta_key ) {
                continue;
            }
            $meta_value['key'] = $path_prefix . $row->meta_value;
            $post_id = intval( $row->post_id );
            $updated = update_post_meta( $post_id, 'amazonS3_info', $meta_value );

            // Provide some indication the script is working
            if ( 0 === $index % 500 ) {
                $message = number_format( $index ) . ' done';

                if ( 0 === $index ) {
                    $message = number_format( $total_rows ) . ' items to update';
                    $message = WP_CLI::colorize( '%Y' . $message . '%n' ); // Yellow text
                }

                WP_CLI::line( $message );
            }
        }
        WP_CLI::success( 'All done!' );
    }

    /**
     * Migrate email subscribers and the lists they belong to to ActiveCamapign
     *
     * ## EXAMPLES
     *
     *     wp pedestal migrate-subscribers-to-activecampaign
     *     wp pedestal migrate-subscribers-to-activecampaign --url=http://billypenn.dev
     *
     * @subcommand migrate-subscribers-to-activecampaign
     */
    public function migrate_subscribers_to_activecampaign( $args, $assoc_args ) {
        // If Pedestal isn't the current theme then bail
        if ( 'pedestal' !== wp_get_theme()->get_template() ) {
            WP_CLI::error( 'Pedestal is not the current theme template!' );
        }
        // Keep track of timings
        $time_start = microtime( true );
        $activecampaign = new ActiveCampaign;

        // A cache of List nNames => List IDs
        $list_lookup = [];
        $args = [
            'number' => 9999,
            'fields' => 'all_with_meta',
        ];
        $user_query = new \WP_User_Query( $args );
        if ( ! empty( $user_query->results ) ) {
            $total_subscribers = count( $user_query->results );
            $loop_count = 1; // Track iterations

            WP_CLI::line( 'Migrating ' . number_format( $total_subscribers ) . ' subscribers' );

            foreach ( $user_query->results as $user ) {
                // Get basic details
                $email = $user->get( 'user_email' );
                $first_name = $user->get( 'first_name' );
                $last_name = $user->get( 'last_name' );

                // We have some date data that we can transfer to ActiveCampaign
                $date = intval( $user->get( 'subscribed_daily_newsletter' ) );
                if ( ! $date ) {
                    $date = strtotime( $user->get( 'user_registered' ) );
                }
                $date_format = 'Y-m-d H:i:s';
                $date = get_date_from_gmt( date( 'Y-m-d H:i:s', $date ), $date_format );

                $fields = [
                    'email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'tags' => '',
                ];
                $tags = [ 'Import:Mandrill' ];

                // Get all of the subscriptions associated with a User
                $subscriptions = [];
                $subscription_terms = wp_get_object_terms( $user->ID, 'pedestal_subscriptions' );
                foreach ( $subscription_terms as $term ) {
                    $subscriptions[] = $term->name . ' - ' . PEDESTAL_BLOG_NAME;
                }
                // Use the Pedestal User Class for a convenient method
                $ped_user = new User( $user->ID );
                $clusters = $ped_user->get_following_clusters();
                foreach ( $clusters as $cluster ) {
                    $cluster_type = $cluster->get_type_name();
                    if ( $cluster_type ) {
                        $cluster_type = ' - ' . $cluster_type;
                    }
                    $subscriptions[] = $cluster->get_title() . ' - ' . PEDESTAL_BLOG_NAME . $cluster_type;
                }

                if ( empty( $subscriptions ) ) {
                    WP_CLI::line( 'No Subscribers: ' . $email );
                    // If the subscriber has no subscriptions then add them to the Daily Newsletter list at least
                    $subscriptions[] = 'Daily Newsletter' . ' - ' . PEDESTAL_BLOG_NAME;
                    $tags[] = 'Import:Mandrill:No Subscriptions';
                }

                foreach ( $subscriptions as $list_name ) {
                    $list_id = false;

                    // Is the List ID cached?
                    if ( ! isset( $list_lookup[ $list_name ] ) ) {
                        // Nope! We need to fetch it from ActiveCampaign
                        $list = $activecampaign->get_list( $list_name );
                        if ( isset( $list->id ) ) {
                            $list_id = $list->id;
                        } else {
                            // Looks like the List doesn't exist, lets add it
                            $list_args = [
                                'name' => $list_name,
                                'sender_url' => $cluster->get_permalink(),
                            ];
                            $list = $activecampaign->add_list( $list_args );
                            if ( isset( $list->id ) ) {
                                $list_id = $list->id;
                            }
                        }
                        // Cache the List ID so we don't need to do this work again
                        $list_lookup[ $list_name ] = $list_id;
                    } else {
                        // We have a hit from our cache
                        $list_id = $list_lookup[ $list_name ];
                    }

                    // If we still have problems then output a warning and skip
                    if ( ! $list_id ) {
                        $warning = 'Bad List: ' . $list_name;
                        $warning = WP_CLI::colorize( '%Y' . $warning . '%n' ); // Yellow text
                        WP_CLI::line( $warning );
                        continue;
                    }

                    // Add list details to our group of $fields
                    $p_key = 'p[' . $list_id . ']';
                    $fields[ $p_key ] = $list_id;
                    $s_key = 'sdate[' . $list_id . ']';
                    $fields[ $s_key ] = $date;
                }
                // Stringify the tags
                $fields['tags'] = implode( ',', $tags );

                // Add the contact to ActiveCampaign
                $resp = $activecampaign->add_contact( $fields );
                $loop_count++;

                // Display some progress so we know its working
                if ( 0 === $loop_count % 75 ) {
                    $percentage_complete = number_format( ( $loop_count / $total_subscribers ) * 100, 2 );
                    $current_time = ( microtime( true ) - $time_start ) / 60;
                    $current_time = number_format( $current_time, 1 );
                    WP_CLI::line( 'Progress: ' . $percentage_complete . '%, ' . number_format( $loop_count ) . ' imported (' . $current_time . ' minutes)' );
                }
            }
        }

        // All done. How long did it take?
        $time_end = microtime( true );
        $total_time = round( $time_end - $time_start, 1 );
        WP_CLI::line( 'Finished in ' . $total_time . ' seconds' );
    }
}

WP_CLI::add_command( 'pedestal', '\Pedestal\CLI\CLI' );
