<?php

namespace Pedestal\CLI;

use function Pedestal\Pedestal;

use WP_CLI;

use joshtronic\LoremIpsum;

use Pedestal\Utils\Utils;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\User_Management;

use Pedestal\Posts\Post;

use Pedestal\Objects\Newsletter_Lists;

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
                // Varying icon colors for email
                'iconColors' => [
                    '#9f9f9f',
                ],
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
        //
        // If the string begins with a `#` character, indicating it's a hex
        // value, don't double wrap it in quotes because Sass needs to treat it
        // as a color
        array_walk_recursive( $sassy_config, function( &$value, $key ) {
            if ( is_string( $value ) && '#' !== substr( $value, 0, 1 ) ) {
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
     * Syncs newsletter IDs with ActiveCampaign
     *
     * ## EXAMPLES
     *
     *     wp pedestal sync-newsletter-ids
     *     wp pedestal sync-newsletter-ids --url=http://billypenn.dev
     *
     * @see /bin/wp-multisite-sync-newsletter-ids.sh
     *
     * @subcommand sync-newsletter-ids
     */
    public function sync_newsletter_ids( $args, $assoc_args ) {
        $newsletter_lists = Newsletter_Lists::get_instance();
        $newsletter_lists->delete_options();
        $lists = $newsletter_lists->get_all_newsletters();
        if ( ! $lists || ! is_array( $lists ) ) {
            WP_CLI::error( '$lists is bad! Oh No!' );
            return;
        }
        foreach ( $lists as $id => $name ) {
            WP_CLI::line( '  - ' . $name . ': ' . $id );
        }
        WP_CLI::success( 'Done!' );
    }

    /**
     * Delete all subscribers. We don't need them in our DB anymore.
     *
     * ## EXAMPLES
     *
     *     wp pedestal purge-subscribers
     *     wp pedestal purge-subscribers --url=http://billypenn.dev
     *
     * @subcommand purge-subscribers
     */
    public function purge_subscribers( $args, $assoc_args ) {
        global $wpdb;

        // Keep track of timings
        $time_start = microtime( true );

        // Get the IDs of all the subscriber users
        $args = [
            'number' => 9999,
            'fields' => 'ID',
            'role' => 'subscriber',
        ];
        $user_query = new \WP_User_Query( $args );
        if ( ! empty( $user_query->results ) ) {
            $user_ids_to_delete = [];

            // Remove Users that are authors of 1 or more posts
            foreach ( $user_query->results as $index => $id ) {
                // Use a SQL query because it is more efficient and we don't need to worry about post types
                $user_post_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE `post_author` = '%d'", [ $id ] ) );
                if ( 0 == $user_post_count ) {
                    $user_ids_to_delete[] = $id;
                }
            }
            $total_subscribers = count( $user_ids_to_delete );
            $removed_users = 0;
            $deleted_users = 0;
            $loop_count = 1;

            // Process all of the subscribers who aren't authors of any post
            foreach ( $user_ids_to_delete as $id ) {
                $id = intval( $id );

                // Remove the user from the site
                wp_delete_user( $id );
                $removed_users++;

                // Check if the user belongs to any sites in the multiste network
                $sites = get_blogs_of_user( $id );
                if ( empty( $sites ) ) {
                    // Remove the user from the Multisite instance
                    wpmu_delete_user( $id );
                    $deleted_users++;
                }
                $loop_count++;

                // Display some progress so we know its working
                if ( 0 === $loop_count % 100 ) {
                    $percentage_complete = number_format( ( $loop_count / $total_subscribers ) * 100, 2 );
                    $time_format = 'seconds';
                    $current_time = ( microtime( true ) - $time_start ); // seconds
                    if ( $current_time > 59 ) {
                        $time_format = 'minutes';
                        $current_time = $current_time / 60;
                    }
                    $current_time = number_format( $current_time, 1 );
                    WP_CLI::line( 'Progress: ' . $percentage_complete . '%, ' . number_format( $loop_count ) . ' processed (' . $current_time . ' ' . $time_format . ')' );
                }
            }
        }

        // Stats
        WP_CLI::success( 'Removed ' . number_format( $removed_users ) . ' users' );
        WP_CLI::success( 'Deleted ' . number_format( $deleted_users ) . ' users' );

        // All done. How long did it take?
        $time_end = microtime( true );
        $total_time = round( $time_end - $time_start, 1 );
        WP_CLI::line( 'Finished in ' . $total_time . ' seconds' );
    }

    /**
     * Migrate users to new roles
     *
     * @subcommand migrate-roles
     */
    public function migrate_roles() {
        foreach ( User_Management::$rename_roles as $old_role => $new_role ) {
            $count_users = 0;
            WP_CLI::line( "Changing `{$old_role}` to `{$new_role}`..." );
            $users = get_users( [ 'role' => $old_role ] );
            foreach ( $users as $user ) {
                $display_name = $user->display_name;
                $user->set_role( $new_role );
                $count_users++;
                WP_CLI::line( "Changed [$user->ID] {$user->display_name}'s' role from `{$old_role}` to `{$new_role}`." );
            }
            WP_CLI::success( "Changed role for {$count_users} `{$old_role}`s to `{$new_role}`s!" );
        }
    }

    /**
     * Migrate users to new roles
     *
     * @subcommand generate-user-slugs
     */
    public function generate_user_slugs() {
        $users = get_users();
        foreach ( $users as $user ) {
            $display_name_slug = sanitize_title( $user->display_name );
            update_user_meta( $user->ID, 'display_name_slug', $display_name_slug );
            WP_CLI::line( $user->display_name . ' --> ' . $display_name_slug );
        }
        WP_CLI::success( 'Done!' );
    }

    /**
     * Restore old attachment meta data
     *
     * ## EXAMPLES
     *
     *     wp pedestal restore-attachment-meta
     *     wp pedestal restore-attachment-meta --url=http://billypenn.dev
     *
     * @subcommand restore-attachment-meta
     */
    public function restore_attachment_meta() {
        global $wpdb;
        $data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}attachment_meta;" ) );
        $count = 0;
        foreach ( $data as $obj ) {
            $post_id = intval( $obj->post_id );
            $old_meta = maybe_unserialize( $obj->meta_value );
            if ( empty( $old_meta['image_meta'] ) ) {
                continue;
            }
            $old_image_meta = $old_meta['image_meta'];
            $old_credit = '';
            if ( empty( $old_image_meta['credit'] ) ) {
                continue;
            }
            $old_credit = $old_image_meta['credit'];
            $old_credit_link = '';
            if ( ! empty( $old_image_meta['credit_link'] ) ) {
                $old_credit_link = $old_image_meta['credit_link'];
            }

            $new_meta = get_post_meta( $post_id, '_wp_attachment_metadata', true );
            // WP_CLI::line( print_r( $new_meta, true ) );
            if ( ! $new_meta ) {
                continue;
            }
            $new_credit = '';
            if ( ! empty( $new_meta['image_meta']['credit'] ) ) {
                $new_credit = $new_meta['image_meta']['credit'];
            }
            $new_credit_link = '';
            if ( ! empty( $new_meta['image_meta']['credit_link'] ) ) {
                $new_credit_link = $new_meta['image_meta']['credit_link'];
            }

            if ( $new_credit != $old_credit ) {
                $count++;
                WP_CLI::line( $post_id . ' - ' . $old_credit );
                $new_meta['image_meta']['credit'] = $old_credit;
                $new_meta['image_meta']['credit_link'] = $old_credit_link;
                update_post_meta( $post_id, '_wp_attachment_metadata', $new_meta );
            }
        }
        WP_CLI::success( 'Restored ' . $count . ' credits!' );
    }

    /**
     * Save Image Credit meta to its own post meta field
     *
     * ## EXAMPLES
     *
     *     wp pedestal convert-credits
     *     wp pedestal convert-credits --url=http://billypenn.dev
     *
     * @subcommand convert-credits
     */
    public function convert_credits() {
        global $wpdb;
        $credit_count = 0;
        $credit_link_count = 0;
        $data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE `meta_key` = '_wp_attachment_metadata'" ) );
        foreach ( $data as $obj ) {
            $post_id = intval( $obj->post_id );
            $old_meta = maybe_unserialize( $obj->meta_value );
            if ( empty( $old_meta['image_meta'] ) ) {
                continue;
            }
            $old_image_meta = $old_meta['image_meta'];
            $old_credit = '';
            if ( empty( $old_image_meta['credit'] ) ) {
                continue;
            }
            $old_credit = $old_image_meta['credit'];
            $old_credit_link = '';
            if ( ! empty( $old_image_meta['credit_link'] ) ) {
                $old_credit_link = $old_image_meta['credit_link'];
            }

            $credit = get_post_meta( $post_id, 'credit', true );
            if ( ! $credit ) {
                update_post_meta( $post_id, 'credit', $old_credit );
                $credit_count++;
            }
            $credit_link = get_post_meta( $post_id, 'credit_link', true );
            if ( ! $credit_link ) {
                update_post_meta( $post_id, 'credit_link', $old_credit_link );
                $credit_link_count++;
            }
        }

        WP_CLI::success( 'Converted ' . $credit_count . ' credits' );
        WP_CLI::success( 'Converted ' . $credit_link_count . ' credit links' );
    }
}

WP_CLI::add_command( 'pedestal', '\Pedestal\CLI\CLI' );
