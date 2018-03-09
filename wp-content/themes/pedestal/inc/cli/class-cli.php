<?php

namespace Pedestal\CLI;

use function Pedestal\Pedestal;

use WP_CLI;
use joshtronic\LoremIpsum;

use Pedestal\Email\Follow_Update_Emails;
use Pedestal\Objects\{
    ActiveCampaign,
    MailChimp,
    Newsletter_Lists,
    User
};
use Pedestal\Posts\Post;
use Pedestal\Posts\Slots\Slot_Item;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\User_Management;
use Pedestal\Utils\Utils;

class CLI extends \WP_CLI_Command {

    /**
     * Migrate legacy Slot Item Sponsor meta to new format
     */
    /*
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
            wp_update_post( [
                'ID' => $slot_item->get_id(),
            ] );

            WP_CLI::line( "Slot Item \"{$slot_item->get_title()}\" with ID {$slot_item->get_id()} was successfully migrated to new format" );
            $slot_item_count_migrated++;

        endforeach;

        $success_message = "(ﾉ◕ヮ◕)ﾉ*:･ﾟ✧ Migrated {$slot_item_count_migrated} slot items to new format!";
        WP_CLI::success( $success_message );
    }
    */

    /**
     * Swap Org titles with alias
     */
    /*
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
    */

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

        // @TODO
        // @codingStandardsIgnoreStart

        // Define $assoc_args vars before extraction so we can check to see if
        // they've been set in $assoc_args
        $post_title = $post_status = $post_author = $story = $count = $maybe_story = $error = '';

        list( $post_type ) = $args;

        extract( $assoc_args );
        // @codingStandardsIgnoreEnd

        $lipsum = new LoremIpsum;
        $type = $post_type;
        $admins = get_users( [
            'role' => 'administrator',
        ] );
        $sources = get_terms( 'pedestal_source', [
            'fields' => 'ids',
        ] );
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
                    if ( Types::is_story( Types::get_post_type( Post::get( $story ) ) ) ) {
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
                [
                    'user_email' => $new_email,
                ], [
                    'ID' => $user->ID,
                ]
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
        $sites = get_sites( [
            'site__not_in' => '1',
        ] );
        foreach ( $sites as $site ) {
            switch_to_blog( $site->blog_id );

            $site_config = Pedestal()->get_site_config();
            $current_theme_path = PEDESTAL_WP_THEMES_PATH . '/' . PEDESTAL_THEME_NAME;
            $config['pedestal']['children'][ PEDESTAL_THEME_NAME ] = [
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
     *     wp pedestal sync-newsletter-ids --url=https://billypenn.dev
     *
     * @see /bin/wp-multisite-sync-newsletter-ids.sh
     *
     * @subcommand sync-newsletter-ids
     */
    public function sync_newsletter_ids( $args, $assoc_args ) {
        $newsletter_groups = Newsletter_Groups::get_instance();
        $newsletter_groups->delete_options();
        $lists = $newsletter_groups->get_all_newsletters();
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
     *     wp pedestal purge-subscribers --url=https://billypenn.dev
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
        if ( ! empty( $user_query->results ) ) :
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
        endif;

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
            $users = get_users( [
                'role' => $old_role,
            ] );
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
     *     wp pedestal restore-attachment-meta --url=https://billypenn.dev
     *
     * @subcommand restore-attachment-meta
     */
    public function restore_attachment_meta() {
        global $wpdb;
        $data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}attachment_meta;" ) );
        $count = 0;
        foreach ( $data as $obj ) :
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
        endforeach;
        WP_CLI::success( 'Restored ' . $count . ' credits!' );
    }

    /**
     * Save Image Credit meta to its own post meta field
     *
     * ## EXAMPLES
     *
     *     wp pedestal convert-credits
     *     wp pedestal convert-credits --url=https://billypenn.dev
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

    /**
     * Normalize exclude_from_home_stream meta data
     *
     * ## EXAMPLES
     *
     *     wp pedestal normalize-exclude-from-home-stream
     *     wp pedestal normalize-exclude-from-home-stream --url=https://billypenn.dev
     *
     * @subcommand normalize-exclude-from-home-stream
     */
    public function normalize_exclude_from_home_stream() {
        $args = [
            'post_type' => Types::get_entity_post_types(),
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key'     => 'exclude_from_home_stream',
                    'value'   => '',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];
        $posts = new \WP_Query( $args );
        WP_CLI::line( count( $posts->posts ) . ' found' );
        foreach ( $posts->posts as $post ) {
            update_post_meta( $post->ID, 'exclude_from_home_stream', '' );
        }

        WP_CLI::success( 'Done' );
    }

    /**
     * Normalize event_link meta data
     *
     * ## EXAMPLES
     *
     *     wp pedestal normalize-event-link-meta
     *     wp pedestal normalize-event-link-meta --url=https://billypenn.dev
     *
     * @subcommand normalize-event-link-meta
     */
    public function normalize_event_link_meta() {
        $args = [
            'post_type'      => 'pedestal_event',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ];
        $posts = new \WP_Query( $args );
        WP_CLI::line( number_format( count( $posts->posts ) ) . ' found' );
        foreach ( $posts->posts as $post ) {
            $event_link = get_post_meta( $post->ID, 'event_link', true );
            $event_details = get_post_meta( $post->ID, 'event_details', true );
            if ( empty( $event_details['url'] ) ) {
                $event_details['url'] = '';
            }
            if ( empty( $event_details['text'] ) ) {
                $event_details['text'] = '';
            }
            if ( ! empty( $event_link ) ) {
                $event_details['url'] = $event_link['url'];
                $event_details['text'] = $event_link['text'];
            }
            update_post_meta( $post->ID, 'event_details', $event_details );
            delete_post_meta( $post->ID, 'event_link' );
        }

        WP_CLI::success( 'Done' );
    }

    /**
     * Add contacts subscribed to any of the site's email lists to the ALL list
     *
     * ## EXAMPLES
     *
     *     wp pedestal update-all-activecampaign-list
     *     wp pedestal update-all-activecampaign-list --url=https://billypenn.dev
     *
     * @subcommand update-all-activecampaign-list
     */
    public function update_all_activecampaign_list() {
        $ac = ActiveCampaign::get_instance();

        // Get the ALL list
        $all_list_name = 'All Contacts - ' . PEDESTAL_BLOG_NAME;
        $all_list = $ac->get_list_by_name( $all_list_name );
        if ( ! $all_list ) {
            $all_list = $ac->add_list([
                'name' => $all_list_name,
            ]);
        }
        if ( ! $all_list ) {
            WP_CLI::error( 'Problem getting the ALL list, ' . $all_list_name );
            return false;
        }
        $all_list_id = $all_list->id;

        // Get all of the site's lists
        $lists = (array) $ac->get_lists([
            'filters[name]' => '- ' . PEDESTAL_BLOG_NAME,
        ]);
        $total_lists = count( $lists );
        $active_list_ids = [];
        foreach ( $lists as $list ) {
            if ( $list->subscriber_count > 0 && $list->id != $all_list_id ) {
                // WP_CLI::line( $list->id . ' | ' . $list->name . ' | ' . $list->subscriber_count );
                $active_list_ids[] = $list->id;
            }
        }
        WP_CLI::line( count( $active_list_ids ) . ' / ' . $total_lists . ' lists have subscribers.' );

        // Get all of the contacts who aren't on the ALL list already
        $contacts_to_add = [];
        $page = 1;
        $keep_going = true;
        while ( $keep_going ) {
            $contacts = (array) $ac->get_contacts([
                'full' => 1,
                'filters[listid]' => implode( ',', $active_list_ids ),
                'filters[status]' => '1',
                'page' => $page,
            ]);

            if ( empty( $contacts ) ) {
                $keep_going = false;
                break;
            } else {
                foreach ( $contacts as $contact ) {
                    $subscribed_lists = (array) $contact->lists;
                    $already_subscribed = false;
                    foreach ( $subscribed_lists as $list ) {
                        if ( $list->listid == $all_list_id ) {
                            $already_subscribed = true;
                            break;
                        }
                    }
                    if ( ! $already_subscribed ) {
                        $contacts_to_add[] = $contact->email;
                        $ac->subscribe_contact( $contact->email, $all_list_id );
                    }
                }
            }

            $contacts_to_add_count = number_format( count( $contacts_to_add ) );
            WP_CLI::line( 'Page ' . $page . ' | ' . $contacts_to_add_count . ' contacts added' );
            $page++;
        }

        $contacts_to_add_count = number_format( count( $contacts_to_add ) );
        WP_CLI::line( 'Done! ' . $contacts_to_add_count . ' contacts added' );
    }

    /**
     * Refresh the subscriber counts for one or more clusters
     *
     * ## OPTIONS
     *
     * <ids>...
     * : Post IDs of the clusters for which you want to refresh the count
     *
     * @subcommand refresh-subscribers
     */
    public function refresh_subscriber_count( $args, $assoc_args ) {
        $result = Follow_Update_Emails::refresh_subscriber_counts( $args );
        WP_CLI::success( 'Done! ' . count( $result ) . ' clusters updated' );
    }

    /**
     * Setup MailChimp for a site
     *
     * ## EXAMPLES
     *
     *     wp pedestal setup-mailchimp
     *     wp pedestal setup-mailchimp --url=https://billypenn.dev
     *
     * @subcommand setup-mailchimp
     */
    public function setup_mailchimp() {
        $mc = MailChimp::get_instance();

        // Make sure a list specific to this site is created
        $mc->get_site_list();

        $categories = [
            'Newsletters' => [
                'Daily Newsletter',
                'Breaking News',
            ],
        ];
        foreach ( $categories as $group_category => $groups ) {

            // Make sure the group category exists
            $mc->add_group_category( $group_category );

            // Add each group to the group category
            foreach ( $groups as $group ) {
                $mc->add_group( $group, $group_category );
            }
        }

        WP_CLI::success( 'Done! Verify at ' . $mc->get_admin_url( '/lists/' ) );
    }

    /**
     * Resurrect deleted stories
     *
     * ## EXAMPLES
     *
     *     wp pedestal resurrect-deleted-stories
     *     wp pedestal resurrect-deleted-stories --url=https://billypenn.dev
     *
     * @subcommand resurrect-deleted-stories
     */
    public function resurrect_deleted_stories() {
        if ( 2 !== get_current_blog_id() ) {
            WP_CLI::error( 'This can only be run on Billy Penn (Site ID == 2)' );
        }
        $input_json = '[
 {
   "id": 1261,
   "title": "#PHLvotes",
   "count": 55,
   "id_string": "1357,1390,1379,1373,1398,1400,1409,1410,1412,1413,1415,1418,1422,1424,1425,1426,1427,1429,1423,1434,1440,1443,1448,1449,1454,1455,1460,1462,1463,1464,1467,1470,1469,1473,1474,1477,1482,1483,1484,1490,1485,1503,1521,1525,1552,1553,1562,1572,1605,1628,1653,54417,55146,55519,55750"
 },
 {
   "id": 850,
   "title": "#PHLvotes: 2015 mayoral race",
   "count": 319,
   "id_string": "533,531,530,532,1104,1726,1854,2028,2058,2059,2116,2129,2177,2218,2283,2293,2405,2643,2686,2714,2813,2818,2826,2827,2838,3014,3314,3315,3321,3335,3343,3395,3565,3570,3583,3592,3602,3596,3625,3692,3721,3755,3790,3927,3930,3931,3934,3948,3958,3977,3993,4007,4024,4027,4035,4054,4055,4056,4119,4123,4138,4155,4177,4205,4266,4311,4318,4326,4334,4365,4369,4399,4400,4439,4407,4482,4502,4507,4538,4550,4562,4578,4579,4620,4636,4665,4675,4701,4706,4732,4772,4821,4845,4820,4850,4895,4898,4919,4930,5006,5015,5075,5124,5177,5200,5207,5210,5214,5235,5243,5295,5338,5382,5386,5420,5467,5469,5560,5507,5599,5614,5638,5710,5714,5722,5729,5746,5802,5838,5865,5879,5885,5926,5959,5998,6021,6033,6118,6133,6150,6183,6171,6188,6209,6227,6228,6274,6276,6334,6343,6377,6392,6386,6436,6485,6508,6553,6555,6565,6566,6567,6674,6800,6801,6824,6876,6882,6883,6920,6929,6967,6955,6992,7027,7102,7147,7264,7302,7303,7360,7526,7612,7620,7758,7807,7829,7843,7887,8025,8039,8099,8117,8122,8266,8284,8301,8303,8316,8334,8359,8355,8402,8426,8485,8438,853"
 },
 {
   "id": 38952,
   "title": "2016 PA Primary",
   "count": 20,
   "id_string": "39523,39521,39535,39542,39546,39547,39552,39556,39558,39553,39589,39622,39619,39598,39655,39829,39398,34788,37733,41873"
 },
 {
   "id": 66079,
   "title": "Anti-Semitic acts in Philly",
   "count": 24,
   "id_string": "66062,65997,65995,65993,66130,66151,66163,66225,66267,66297,66475,66477,66543,66610,66846,67543,68111,68743,68970,69810,70111,71336,86843,88459"
 },
 {
   "id": 823,
   "title": "Atlantic City is falling apart",
   "count": 30,
   "id_string": "643,639,555,536,522,893,1824,2162,2266,2481,3359,3426,3513,3576,3617,3652,3960,4218,4275,5086,6422,7478,7694,8133,10197,17507,20269,26491,31164,54623"
 },
 {
   "id": 67309,
   "title": "Beer Madness",
   "count": 6,
   "id_string": "67074,67884,68392,68875,69392,69694"
 },
 {
   "id": 839,
   "title": "Bike lane 101",
   "count": 4,
   "id_string": "565,543,544,545"
 },
 {
   "id": 990,
   "title": "Billy Penn Playlist",
   "count": 25,
   "id_string": "961,1272,1627,1873,2191,2412,2588,2815,3069,3531,3726,3991,4229,4767,5016,5232,6905,7396,7852,8482,9562,10566,11217,19235,26654"
 },
 {
   "id": 51833,
   "title": "Binary Bandits",
   "count": 8,
   "id_string": "51823,51626,53729,54720,60360,60426,60931,74601"
 },
 {
   "id": 1522,
   "title": "Carlesha Found",
   "count": 23,
   "id_string": "1510,1476,1453,1437,1420,1387,1529,1524,1555,1557,1558,1564,1570,1602,1604,1609,1626,1633,1710,1866,1907,5203,20408"
 },
 {
   "id": 749,
   "title": "Center City gay attack: The fallout",
   "count": 49,
   "id_string": "676,721,586,585,584,633,571,578,528,1241,1253,2856,2883,2902,2927,2928,2942,5725,6872,14967,21043,23770,23771,23786,26960,27936,28233,28320,28544,28576,28618,28646,28687,28695,28780,28893,29114,29151,32626,33148,33319,34787,35485,35682,39891,41395,44183,45569,88344"
 },
 {
   "id": 16572,
   "title": "Chaka Fattah convicted",
   "count": 79,
   "id_string": "16571,16570,16549,16557,9970,5987,3594,2725,1508,1503,1244,16579,16618,16627,16637,16676,16738,16771,16799,16828,17003,17019,17339,18066,18320,18343,18464,18658,19018,19089,19807,19862,19905,20301,20701,22530,26345,27912,28035,28672,31300,31301,31640,33479,34130,34783,35502,35525,35746,37139,37435,37495,37665,39542,39977,40878,41838,42839,43419,43957,44234,44235,44314,44328,44329,44392,44397,44534,49048,55489,59566,60067,60278,60706,61568,62404,62409,63398,63800"
 },
 {
   "id": 57974,
   "title": "Cheesesteak Bracket",
   "count": 7,
   "id_string": "57943,56949,58499,59069,59534,60099,60654"
 },
 {
   "id": 830,
   "title": "Comcast customer service",
   "count": 26,
   "id_string": "638,607,553,929,1222,1367,2179,3461,4189,4488,4795,4946,5275,6161,6505,6910,6915,7335,7676,8080,8172,10261,10606,11695,16318,28692"
 },
 {
   "id": 844,
   "title": "Cooper CEO fire",
   "count": 15,
   "id_string": "620,579,550,549,883,1274,1561,1840,1891,2066,2376,5176,6194,6214,40206"
 },
 {
   "id": 42961,
   "title": "Copa America",
   "count": 4,
   "id_string": "42959,43135,43154,43307"
 },
 {
   "id": 68285,
   "title": "DA Seth Williams guilty",
   "count": 41,
   "id_string": "68284,68244,68217,68080,67565,66690,66449,66040,65569,65492,61316,53626,52462,51148,49637,49624,49184,49121,19441,68579,68833,69185,69460,69770,72247,72365,73069,73345,76213,76556,76601,76685,76833,77182,77343,77402,77453,77588,77717,79214,86903"
 },
 {
   "id": 1861,
   "title": "Dead PGW deal",
   "count": 15,
   "id_string": "1860,1831,1654,1233,1191,1159,1865,2164,2285,2578,2626,2643,2852,3603,24077"
 },
 {
   "id": 49703,
   "title": "Eagles bracket",
   "count": 6,
   "id_string": "49658,50219,50892,51530,52334,53149"
 },
 {
   "id": 46989,
   "title": "Eagles Training Camp",
   "count": 5,
   "id_string": "46988,48933,48998,49218,50532"
 },
 {
   "id": 1068,
   "title": "Ebola prep",
   "count": 6,
   "id_string": "1067,993,979,772,616,2623"
 },
 {
   "id": 51480,
   "title": "Election 2016",
   "count": 303,
   "id_string": "51477,51390,50936,50933,50864,50863,50820,50681,50635,48019,47842,47820,47646,46734,46675,46611,46536,46430,46124,42978,41699,41490,41471,40568,40189,39889,39521,39553,39589,39619,39649,39655,39115,39045,39040,39026,38910,38789,38285,38286,37596,34846,34741,34490,34386,33225,33070,32144,32016,30091,28040,28070,28015,28002,27439,27273,26629,50342,49712,49036,48053,47313,45631,45077,44864,44784,42318,41029,39523,39398,37720,37602,37600,37102,32506,23781,23709,6737,51146,50151,49535,49516,48924,48667,48581,47870,47235,47138,41097,40681,39789,39737,39637,39546,37517,36312,35782,34173,21712,15904,15779,15720,6677,4181,4174,50877,51202,50284,47506,46590,45388,43779,43586,42582,42483,41907,32149,17689,17139,16272,14910,14475,14337,39552,39500,39412,37376,36925,35262,34120,30690,49174,49112,47097,51507,51789,51795,52031,52124,52125,52288,52540,52542,53085,53093,53145,53211,53420,53444,53644,53713,53716,53726,53876,54008,54016,54367,54404,54414,54420,54417,54443,54445,54505,54526,54564,54609,54633,54639,54693,54711,54"
 },
 {
   "id": 1295,
   "title": "Eric Frein Caught",
   "count": 15,
   "id_string": "1276,1278,1275,1269,1268,1325,1331,1340,1360,1417,1528,1657,1813,4136,5830"
 },
 {
   "id": 44709,
   "title": "Frozen Treats",
   "count": 6,
   "id_string": "44650,44153,43372,42775,45341,45826"
 },
 {
   "id": 854,
   "title": "Gun crisis",
   "count": 11,
   "id_string": "914,622,621,582,540,541,678,1701,1784,42088,62813"
 },
 {
   "id": 49625,
   "title": "Instagram of the Day",
   "count": 406,
   "id_string": "49571,49457,49442,52592,52507,52775,52837,52860,53027,53145,53258,53371,53495,53571,53585,53662,54169,54301,54339,54378,54743,54901,54994,55069,55254,55373,55627,55675,55728,55503,56047,56300,56335,56375,56677,56964,57139,57253,57316,57436,57956,58101,58146,58168,58289,58497,58614,58643,58669,58781,59054,59148,59162,59287,59136,59043,58897,59359,59422,59523,59641,59675,59741,59859,59915,59972,60079,60189,60220,60264,60338,60453,60716,60769,60817,60912,60969,61086,61113,61196,61215,61230,61324,61205,61388,61433,61526,61571,61608,61700,61776,61933,61936,61865,60649,60579,61996,62047,62067,62151,62216,62376,62518,62577,62611,62660,62833,63054,63144,63189,63247,63436,63512,63788,63738,63828,63904,63993,64254,64340,64373,64470,64577,64690,64736,64834,64868,64885,64949,65056,65198,65423,65447,65567,65587,65616,65711,65803,65920,65980,65999,66163,66302,66433,66596,66642,66671,66739,66805,66912,66995,67176,67231,67267,67400,67508,67601,67669,67802,67861,67882,68032,67881,68379,68559,68613,68644,68744,68863,68972,6907"
 },
 {
   "id": 1114,
   "title": "LOL Sixers",
   "count": 38,
   "id_string": "1102,1094,594,1637,1904,2057,2085,2171,2238,2242,2313,2423,2511,2534,2991,3029,3622,4630,4855,4893,4972,5079,6332,6832,11800,13403,15029,19191,27307,27291,27290,27505,27562,28229,28319,29143,37645,37556"
 },
 {
   "id": 849,
   "title": "Market East redevelopment",
   "count": 26,
   "id_string": "562,560,561,503,502,919,1103,2829,2832,3381,4704,4892,6225,6798,8088,10329,10562,10703,12193,18460,22774,39020,58375,73600,74085,84630"
 },
 {
   "id": 2889,
   "title": "MontCo shooting",
   "count": 15,
   "id_string": "2882,2873,2872,2870,2866,2895,2897,2904,2915,2920,2921,2913,2925,2932,3191"
 },
 {
   "id": 855,
   "title": "Mumia\'s speech-turned-suit",
   "count": 14,
   "id_string": "648,597,576,575,574,568,870,1685,2278,2297,3507,5213,6697,7674"
 },
 {
   "id": 867,
   "title": "Narco police corruption",
   "count": 20,
   "id_string": "509,508,2223,1843,6230,8747,9179,9182,10405,10455,10536,12837,14896,16326,16693,18512,26201,32485,51907,88989"
 },
 {
   "id": 41113,
   "title": "NBA Draft",
   "count": 18,
   "id_string": "41111,41627,41224,41208,43927,44155,44277,44348,44722,76149,76274,76448,76515,76744,76818,76832,76839,76840"
 },
 {
   "id": 45615,
   "title": "NFL Draft",
   "count": 90,
   "id_string": "45614,50356,50425,60931,61900,66249,66414,66550,66674,68059,68303,68313,68427,68490,68834,69451,69548,69719,69922,69919,69972,70113,70163,70499,70598,70653,70742,70778,70791,70830,70892,70984,71020,71141,71147,71152,71187,71312,71331,71343,71366,71440,71501,71489,71523,71538,71556,71576,71601,71659,71681,71722,71736,71737,71806,71842,71844,71845,71851,71881,71880,71921,71932,71934,71965,72007,72009,72012,72013,72014,72031,72036,72042,72044,72058,72073,72074,72146,72177,72214,72233,72420,72432,72453,73126,74723,76501,81935,82936,86562"
 },
 {
   "id": 30398,
   "title": "Officer Jesse Hartnett shooting",
   "count": 23,
   "id_string": "30386,30410,30419,30439,30448,30449,30456,30575,30641,30665,30648,30729,30800,30817,30835,30841,31082,31101,31381,32618,38119,50410,64483"
 },
 {
   "id": 13685,
   "title": "PA Budget",
   "count": 101,
   "id_string": "13684,13398,13361,12973,10859,6249,5403,5359,5316,5231,13635,10275,6234,5534,5355,5317,5228,5136,13726,13763,13893,14339,14681,14881,14918,15072,15365,16323,17017,17774,18484,18663,18664,18998,19033,19226,19480,21327,21354,21840,22837,22899,23099,23424,23613,23614,24246,24781,24811,25816,25852,26013,26202,26857,26859,26932,27331,27883,27933,28153,28201,28251,28526,28667,28668,29148,29179,29188,29272,29282,29583,29599,29641,29755,30295,30829,32272,32718,32786,32911,34279,35496,35813,35871,36429,36755,44310,44822,44950,45389,45787,46036,46126,46166,47877,60823,65749,74746,84308,84663,87318"
 },
 {
   "id": 727,
   "title": "PA porn scandal",
   "count": 78,
   "id_string": "551,552,601,637,652,653,667,764,804,1060,1096,1614,1662,1692,1821,1823,2072,2156,2161,2356,2677,3073,5760,19151,19227,19416,19484,20050,20361,20878,22503,22552,23115,23649,24073,24091,24110,24247,26020,26029,26073,26365,26448,27322,27350,27434,27438,27454,27572,27644,27641,27842,28108,28155,28252,28529,28729,28742,28751,28838,29191,29287,29536,31200,34144,34385,34719,35791,40159,40230,42366,42908,49135,58809,61422,66996,73191,78315"
 },
 {
   "id": 758,
   "title": "Philly Bike Share",
   "count": 44,
   "id_string": "589,701,588,587,702,2935,3504,4650,4653,5078,5298,6244,6397,6928,7101,7269,7285,7479,7538,7759,7789,8337,8714,10719,11204,11265,12158,13360,14275,14288,15099,15582,15366,17532,20279,27277,29106,35790,38996,41558,45061,69403,73872,73881"
 },
 {
   "id": 1046,
   "title": "Philly corruption sting",
   "count": 15,
   "id_string": "1047,986,920,2880,2914,2922,2995,3009,3091,12120,21612,26927,26935,28670,36859"
 },
 {
   "id": 746,
   "title": "Philly decriminalizes marijuana",
   "count": 15,
   "id_string": "659,656,513,595,591,590,703,4376,17468,19542,24111,38941,71136,83754,86776"
 },
 {
   "id": 72764,
   "title": "Philly Food Finds",
   "count": 6,
   "id_string": "72760,76067,79185,86782,87213,87362"
 },
 {
   "id": 79951,
   "title": "Philly street harassment",
   "count": 6,
   "id_string": "79783,69512,72438,66884,40054,81334"
 },
 {
   "id": 65572,
   "title": "PolitiFact Pennsylvania",
   "count": 57,
   "id_string": "65522,63895,63506,62819,61895,61244,61159,60930,58984,55914,55515,54505,54008,53726,52369,51795,50466,49712,49516,49166,48924,48796,48574,47870,47820,47514,46608,46448,46036,45642,44814,44473,43779,43648,42866,42582,42318,41730,41513,41471,41029,40656,40358,39737,39362,39115,38946,38687,38220,37874,37600,36912,36481,36322,36086,36060,75354"
 },
 {
   "id": 61815,
   "title": "Restaurant Reviews",
   "count": 8,
   "id_string": "61813,65169,68803,71795,74681,77543,79745,80725"
 },
 {
   "id": 68511,
   "title": "Rise of the El",
   "count": 9,
   "id_string": "68493,69361,69371,68481,69862,69946,70435,71012,73362"
 },
 {
   "id": 62601,
   "title": "Rittenhouse Square wall-sitting ban",
   "count": 10,
   "id_string": "62563,62385,62464,62474,62535,62672,62676,62707,62979,62987"
 },
 {
   "id": 82259,
   "title": "Sandwich Bracket",
   "count": 6,
   "id_string": "32085,32646,33071,34139,34610,35174"
 },
 {
   "id": 858,
   "title": "Schuylkill Banks Boardwalk",
   "count": 4,
   "id_string": "602,558,559,557"
 },
 {
   "id": 45268,
   "title": "SEPTA Silverliner V",
   "count": 16,
   "id_string": "45256,45236,45360,45412,45427,45536,45212,45634,45645,45672,45835,46581,47501,48025,54510,78207"
 },
 {
   "id": 56516,
   "title": "SEPTA strike 2016",
   "count": 37,
   "id_string": "56356,56411,55981,55883,54955,54766,56523,56526,56543,56572,56576,56605,56608,56642,56643,56662,56706,56758,56761,56795,56803,56852,56871,56867,57025,57044,57083,57088,57045,57163,57272,57297,57314,57393,57941,58485,61105"
 },
 {
   "id": 818,
   "title": "SEPTA strike averted",
   "count": 19,
   "id_string": "677,647,369,630,1009,1034,1037,1090,1221,1246,1317,1321,1322,1324,1327,1328,1329,1359,1640"
 },
 {
   "id": 64411,
   "title": "SEPTA\'s MFL Cracks",
   "count": 4,
   "id_string": "64374,64447,64640,64814"
 },
 {
   "id": 56499,
   "title": "Sixers Big Man Tracker",
   "count": 41,
   "id_string": "55701,56502,56863,57684,58326,58433,58771,59165,59351,59918,60601,60797,60806,61779,62754,62827,62835,63250,63325,63425,63528,63560,63720,64691,64887,65293,65696,65748,65759,65796,65849,66369,67671,67916,68273,68700,70009,73940,77166,78226,84261"
 },
 {
   "id": 762,
   "title": "SRC vs. Philly teachers",
   "count": 28,
   "id_string": "683,663,650,646,644,642,641,388,604,212,573,626,882,1058,1112,1156,1307,1511,1799,2153,2739,3955,4980,4982,5085,5505,5632,48945"
 },
 {
   "id": 872,
   "title": "Taney Dragons",
   "count": 18,
   "id_string": "888,634,538,524,523,525,1027,1243,1376,2338,2426,2596,3235,4486,4632,4651,5896,6834"
 },
 {
   "id": 39826,
   "title": "Tasty Fakes",
   "count": 7,
   "id_string": "39821,37746,41331,43244,45110,47750,62025"
 },
 {
   "id": 744,
   "title": "Tracking the Wolfpack",
   "count": 131,
   "id_string": "661,651,635,631,548,529,514,599,471,475,931,942,969,972,1005,1008,1021,1026,1076,1083,1123,1128,1130,1133,1149,1150,1166,1229,1263,1309,1332,1344,1346,1347,1348,1350,1353,1363,1369,1373,1379,1460,1462,1464,1467,1470,1469,1473,1490,1484,1483,1485,1521,1605,1695,1728,1747,1870,1875,1881,1994,2071,2117,2159,2225,2317,2406,2479,2586,2590,2627,2736,2839,2876,2910,3002,3092,3209,3559,3574,3678,3705,3718,3775,3796,3789,3804,3805,3803,3831,3833,3830,3838,3851,3860,3901,3904,3908,3944,3968,3987,3997,4010,4077,4111,4164,4206,4401,4523,4619,4629,4646,4787,5234,5231,5323,5355,5403,5954,6427,6653,7647,7890,10941,11051,11314,11453,12980,13398,14564,26657"
 },
 {
   "id": 13929,
   "title": "Trans in Philly",
   "count": 16,
   "id_string": "13918,14267,14377,14505,13896,14869,14982,22971,23116,34267,40570,59215,63186,79570,79582,81808"
 },
 {
   "id": 63815,
   "title": "Trump\'s travel ban and Philly",
   "count": 27,
   "id_string": "63771,63810,63809,63812,63805,63803,63769,63768,63757,63755,63818,63825,63869,63836,63892,63902,63904,63969,64051,64086,64139,64178,64220,64340,64412,65959,81194"
 },
 {
   "id": 40256,
   "title": "Ultimate Phillie",
   "count": 10,
   "id_string": "40125,40214,40017,38653,39952,39275,37973,40298,40475,41010"
 },
 {
   "id": 72047,
   "title": "USA250 in Philly",
   "count": 1,
   "id_string": "72046"
 },
 {
   "id": 31325,
   "title": "Winter Storm Jonas",
   "count": 16,
   "id_string": "31322,31302,31285,31262,31327,31332,31379,31384,31396,31416,31485,31497,31520,31535,31582,31621"
 }
]';
        $json = json_decode( $input_json );
        foreach ( $json as $item ) {
            $post_ids_to_connect = explode( ',', $item->id_string );
            $post_ids_to_connect = array_map( 'absint', $post_ids_to_connect );
            $topic = get_page_by_title( $item->title, 'OBJECT', 'pedestal_topic' );
            if ( ! $topic ) {
                $new_topic = [
                    'post_title'  => $item->title,
                    'post_status' => 'publish',
                    'post_type'   => 'pedestal_topic',
                ];
                $topic = wp_insert_post( $new_topic );
                if ( is_wp_error( $topic ) ) {
                    continue;
                }
                $topic = get_post( $topic );
                WP_CLI::line( $item->title . ' added as a new Topic' );
            }
            $from = $topic->ID;
            foreach ( $post_ids_to_connect as $to ) {
                p2p_type( 'entities_to_topics' )->connect( $from, $to );
            }
            WP_CLI::line( 'Added ' . count( $post_ids_to_connect ) . ' entities' . ' to ' . $item->title );
            WP_CLI::line( '---------------------------' );
        }
        WP_CLI::success( 'Done!' );
    }
}
WP_CLI::add_command( 'pedestal', '\Pedestal\CLI\CLI' );
