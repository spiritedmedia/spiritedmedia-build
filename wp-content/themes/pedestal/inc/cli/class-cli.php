<?php

namespace Pedestal\CLI;

use function Pedestal\Pedestal;

use WP_CLI;
use joshtronic\LoremIpsum;

use Pedestal\Objects\{
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
     * Setup MailChimp for a site
     *
     * Group cateogry names use the plural version of the post_type name.
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

        $categories = [];
        $post_types = Types::get_mailchimp_integrated_post_types();
        foreach ( $post_types as $post_type ) {
            $plural_name = Types::get_post_type_name( $post_type );
            $post_titles = [];
            $args = [
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ];
            $posts = new \WP_Query( $args );
            foreach ( $posts->posts as $post ) {
                $post_titles[] = $post->post_title;
            }
            $categories[ $plural_name ] = $post_titles;
        }

        $categories['Newsletters'] = [
            'Daily Newsletter',
            'Breaking News',
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
     * Migrate legacy `user_img` fields to per-site fields
     *
     * @link https://github.com/spiritedmedia/spiritedmedia/pull/1627
     *
     * ## EXAMPLES
     *
     *     wp pedestal migrate-user-img
     *     wp pedestal migrate-user-img --url=https://billypenn.dev
     *
     * @subcommand migrate-user-img
     */
    public function migrate_user_img() {
        global $wpdb;
        $user_img_key = $wpdb->prefix . 'user_img';

        $skipped_count = 0;
        $migrated_count = 0;
        $no_image_count = 0;
        $users = get_users( [
            'fields' => [ 'ID' ],
        ] );
        foreach ( $users as $user ) {
            $user_id = $user->ID;
            $site_user_img = get_user_meta( $user_id, $user_img_key, true );
            if ( $site_user_img ) {
                $skipped_count++;
                WP_CLI::line( "User {$user_id} already has a user image in the proper format. Skipping..." );
                continue;
            }
            $global_user_img = get_user_meta( $user_id, 'user_img', true );
            if ( $global_user_img ) {
                update_user_meta( $user_id, $user_img_key, $global_user_img );
                delete_user_meta( $user_id, 'user_img' );
                $migrated_count++;
                WP_CLI::line( "User {$user_id}'s legacy user image was migrated to the proper format!" );
                continue;
            }
            $no_image_count++;
            WP_CLI::line( "User {$user_id} doesn't have an image uploaded anywhere." );
        }
        WP_CLI::success( $skipped_count . ' users already had images in the proper format and were skipped.' );
        WP_CLI::success( $no_image_count . ' users didn\'t have an old or new image defined, so they were skipped too.' );
        WP_CLI::success( $migrated_count . ' users had images in the old format and were migrated to the new format!' );
        WP_CLI::success( 'Done!' );
    }
}
WP_CLI::add_command( 'pedestal', '\Pedestal\CLI\CLI' );
