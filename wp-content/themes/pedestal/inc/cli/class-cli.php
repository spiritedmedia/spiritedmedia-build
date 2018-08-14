<?php

namespace Pedestal\CLI;

use function Pedestal\Pedestal;

use WP_CLI;
use joshtronic\LoremIpsum;
use Sunra\PhpSimple\HtmlDomParser;

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
     * Migrate Denverite users to Pedestal
     *
     * ## EXAMPLES
     *
     *    wp pedestal denverite-user-migration --url=https://denverite.dev
     *
     * @subcommand denverite-user-migration
     */
    public function denverite_user_migration( $args, $assoc_args ) {
        global $wpdb;
        $original_user_table = 'wp_iq43vv_users_original';
        $original_usermeta_table = 'wp_iq43vv_usermeta_original';

        // Does the site have denverite in the URL somewhere? Otherwise we could be in a world of hurt
        if ( ! strpos( strtolower( get_site_url() ), 'denverite' ) ) {
            WP_CLI::error( 'Not a denverite site: ' . get_site_url() );
        }

        // Check to make sure the tables we are expecting actually exist
        $found_user_table = $wpdb->get_row( "SELECT * FROM $original_user_table", ARRAY_A ); // phpcs:ignore
        if ( empty( $found_user_table ) ) {
            WP_CLI::error( 'Missing or empty user table from Denverite (' . $original_user_table . ')' );
        }
        $found_usermeta_table = $wpdb->get_row( "SELECT * FROM $original_usermeta_table", ARRAY_A ); // phpcs:ignore
        if ( empty( $found_user_table ) ) {
            WP_CLI::error( 'Missing or empty usermeta table from Denverite (' . $original_usermeta_table . ')' );
        }

        $old_users = $wpdb->get_results( "SELECT * FROM $original_user_table" ); // phpcs:ignore
        foreach ( $old_users as $old_user ) {
            $old_id = absint( $old_user->ID );
            if ( 0 === $old_id ) {
                WP_CLI::line( 'A user was skipped due to a bad user id.' );
                continue;
            }
            $user_data = [
                'user_pass'       => '',
                'user_login'      => $old_user->user_login,
                'user_nicename'   => $old_user->user_nicename,
                'user_email'      => $old_user->user_email,
                'user_url'        => $old_user->user_url,
                'user_registered' => $old_user->user_registered,
                'display_name'    => $old_user->display_name,
                'role'            => 'reporter',
            ];
            $new_id = wp_insert_user( $user_data );
            if ( is_wp_error( $new_id ) ) {
                if ( 'existing_user_login' == $new_id->get_error_code() ) {
                    $existing_user = get_user_by( 'email', $old_user->user_email );
                    $new_id = $existing_user->ID;
                } else {
                    WP_CLI::error( 'Error inserting new user: ' . $new_id->get_error_message() );
                }
            }
            $old_meta_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT `meta_key`, `meta_value` FROM $original_usermeta_table WHERE `user_id` = %d", // phpcs:ignore
                [ $old_id ]
            ) );

            $whitelisted_meta_keys = [
                'nickname',
                'first_name',
                'last_name',
                'rich_editing',
                'comment_shortcuts',
                'admin_color',
                'show_admin_bar_front',
                'show_welcome_panel',
            ];

            // Maps ( Denverite keys => Pedestal keys )
            $meta_keys_to_transform = [
                'twitter'               => 'twitter_username',
                'facebook'              => 'facebook_profile',
                'description'           => 'user_bio_extended',
                'wp_iq43vv_user_avatar' => 'wp_4_user_img', // Map attachment ID to use as author avatar
            ];
            foreach ( $old_meta_rows as $old ) {
                if ( in_array( $old->meta_key, $whitelisted_meta_keys ) ) {
                    update_user_meta( $new_id, $old->meta_key, $old->meta_value );
                }

                if ( isset( $meta_keys_to_transform[ $old->meta_key ] ) ) {
                    $new_key = $meta_keys_to_transform[ $old->meta_key ];
                    $value = $old->meta_value;
                    if ( 'user_bio_extended' == $new_key ) {
                        $value = wpautop( $value );
                    }
                    update_user_meta( $new_id, $new_key, $value );
                }
            }

            // Set Denverite as the users primary site
            update_user_meta( $new_id, 'primary_blog', '4' );
            update_user_meta( $new_id, 'public_email', $old_user->user_email );

            // Update old post_author values
            $wpdb->update(
                $wpdb->posts,
                [
                    'post_author' => $new_id,
                ],
                [
                    'post_author' => $old_id,
                ],
                [
                    '%d',
                ],
                [
                    '%d',
                ]
            );

            WP_CLI::success( $old_user->display_name . ' was migrated' );
        }

        WP_CLI::line( 'All done!' );
    }

    /**
     * Cleanup old Denverite shortcodes
     *
     * ## EXAMPLES
     *
     *    wp pedestal denverite-cleanup-shortcodes --url=https://denverite.dev
     *
     * @subcommand denverite-cleanup-shortcodes
     */
    public function denverite_cleanup_shortcodes( $args, $assoc_args ) {
        global $wpdb;

        // [irp]
        $like = '%' . $wpdb->esc_like( '[irp' ) . '%';
        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->posts}` WHERE `post_content` LIKE %s",
            [
                $like,
            ]
        ) );
        WP_CLI::line( 'Stripping [irp] from ' . count( $posts ) . ' posts' );
        foreach ( $posts as $post ) {
            $new_content = preg_replace( '/\[irp.+\](\s+)?/im', '', $post->post_content );
            $new_content = preg_replace( '/\[irp\]/im', '', $new_content );
            $new_post = [
                'ID' => $post->ID,
                'post_content' => trim( $new_content ),
            ];
            wp_update_post( $new_post );
            // var_dump( $new_post );
            WP_CLI::line( get_permalink( $post ) );
        }

        // [denverite_inline_post]
        $like = '%' . $wpdb->esc_like( '[denverite_inline_post' ) . '%';
        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->posts}` WHERE `post_content` LIKE %s",
            [
                $like,
            ]
        ) );
        WP_CLI::line( 'Stripping [denverite_inline_post] from ' . count( $posts ) . ' posts' );
        foreach ( $posts as $post ) {

            // If the post is published, store the posts attribute value as post meta in case we ever need it in the future
            if ( 'publish' == $post->post_status ) {
                preg_match_all( '/\[denverite_inline_post(.+)\]/i', $post->post_content, $matches );
                if ( ! empty( $matches[1] ) ) {
                    $meta_ids = [];
                    foreach ( $matches[1] as $attr ) {
                        $meta_id = str_replace( [ 'posts=', '"' ], '', $attr );
                        $meta_id = trim( $meta_id );
                        $meta_id = intval( $meta_id );
                        $meta_ids[] = $meta_id;
                    }
                    if ( ! empty( $meta_ids ) ) {
                        $meta_ids_str = implode( ',', $meta_ids );
                        update_post_meta( $post->ID, 'denverite_inline_post', $meta_ids_str );
                    }
                }
            }

            // Strip the shortcode
            $new_content = preg_replace( '/\[denverite_inline_post.+\](\s+)?/im', '', $post->post_content );
            $new_post = [
                'ID' => $post->ID,
                'post_content' => trim( $new_content ),
            ];
            wp_update_post( $new_post );
            // var_dump( $new_post );
            WP_CLI::line( get_permalink( $post ) );
        }
    }

    /**
     * If no featured image is specified, find the first image in the post and make that the featured image
     *
     * ## EXAMPLES
     *
     *    wp pedestal denverite-add-featured-images --url=https://denverite.dev
     *
     * @subcommand denverite-add-featured-images
     */
    public function denverite_add_featured_images( $args, $assoc_args ) {
        $args = [
            'post_type'      => [ 'pedestal_article' ],
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query' => [
                [
                    'key'     => '_thumbnail_id',
                    'value'   => '',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];
        $posts = new \WP_Query( $args );
        WP_CLI::line( number_format( $posts->found_posts ) . ' posts need featured images' );
        foreach ( $posts->posts as $post ) {
            // Make sure a featured image isn't already set
            $attachment_id = get_post_meta( $post->ID, '_thumbnail_id', true );
            if ( $attachment_id ) {
                continue;
            }

            preg_match_all( '/\[img(.+)\]/i', $post->post_content, $matches );
            if ( ! empty( $matches[1] ) ) {
                foreach ( $matches[1] as $attr ) {
                    $attachment_id = false;
                    preg_match( '/attachment="(\d+)/i', $attr, $parts );
                    if ( ! empty( $parts[1] ) ) {
                        $attachment_id = $parts[1];
                        $attachment_id = trim( $attachment_id );
                        $attachment_id = intval( $attachment_id );

                        // Make sure the attachment isn't a gif, which is probably animated
                        $url = wp_get_attachment_url( $attachment_id );
                        $filetype = wp_check_filetype( $url );
                        if ( 'gif' != $filetype['ext'] ) {
                            update_post_meta( $post->ID, '_thumbnail_id', $attachment_id );

                            // Strip the shortcode
                            $shortcode_to_replace = '[img' . $attr . ']';
                            $delimiter = '/';
                            $regex = '/' . preg_quote( $shortcode_to_replace, $delimiter ) . '(\s+)?/i';
                            $new_content = preg_replace( $regex, '', $post->post_content );
                            $new_post = [
                                'ID'           => $post->ID,
                                'post_content' => trim( $new_content ),
                            ];
                            wp_update_post( $new_post );
                            WP_CLI::success( get_permalink( $post ) );
                            break;
                        }
                    }
                }
            }
        }
        WP_CLI::line( 'All done!' );
    }

    /**
     * Convert tags and their associations to clusters
     *
     * ## EXAMPLES
     *
     *    wp pedestal denverite-migrate-tags-to-clusters --url=https://denverite.dev
     *
     * @subcommand denverite-migrate-tags-to-clusters
     */
    public function denverite_migrate_tags_to_clusters( $args, $assoc_args ) {
        // Rename tag data
        $rename = [
            // old => new
            'aca'                           => 'ACA',
            'Besty DeVos'                   => 'Betsy DeVos',
            'betsy devos'                   => 'Betsy Devos',
            'colfax week'                   => 'Colfax Week',
            'dnc'                           => 'DNC',
            'Dufford &amp; Brown'           => 'Dufford & Brown',
            'dunbar kitchen &amp; taphouse' => 'Dunbar Kitchen & Taphouse',
            'lgbtq'                         => 'LGBTQ',
            'mayor michael hancock'         => 'Michael Hancock',
            'nhl'                           => 'NHL',
            'tbt'                           => 'TBT',
            'VISIT DENVER'                  => 'Visit Denver',
        ];

        // Tags that should be merged
        $merge = [
            // Target tag => Tag(s) that will be merged into the target
            '#denvergotoplessday' => 'denver go topless day',
            'Donald Trump'        => 'Trump',
            'elections'           => 'election',
            'Hillary Clinton'     => [
                'Clinton',
                'Hillary',
                'Hillary Clinton Denver visit',
                'Hillary Clinton emails',
            ],
            'hobbies'            => 'hobby',
            'Republicans'        => 'republicansr',
            'Betsy Devos'        => [
                'betsy devos',
                'Besty DeVos',
            ],
        ];

        // Reformat the data so we can better identify if a tag is going to be replaced
        $to_be_replaced = [];
        foreach ( $merge as $item ) {
            if ( is_array( $item ) ) {
                foreach ( $item as $thing ) {
                    $to_be_replaced[] = $thing;
                }
            } else {
                $to_be_replaced[] = $item;
            }
        }
        $to_be_replaced = array_unique( $to_be_replaced );

        // Get the Uncategorized locality type term
        $locality_type_term = get_term_by( 'name', 'Uncategorized', 'pedestal_locality_type' );
        // If it doesn't exist then insert it
        if ( ! $locality_type_term ) {
            $inserted = wp_insert_term( 'Uncategorized', 'pedestal_locality_type' );
            if ( is_wp_error( $inserted ) ) {
                WP_CLI::line( 'Problem inserting Locality Type term' );
                WP_CLI::line( $inserted->get_error_message() );
                return;
            }
            $locality_type_term = get_term( $inserted['term_id'], 'pedestal_locality_type' );
        }

        // Download the CSV of data so we don't need to worry about storing it anywhere
        // See https://stackoverflow.com/a/33727897/1119655
        $spreadsheet_url = 'https://docs.google.com/spreadsheets/d/1gyH1Py7KuBn2s-bJTnhSaiHEAJlx9PlTubCLVqG2q8k/gviz/tq?tqx=out:csv&sheet=denverite-tags-frequency-20180219';
        $filename = wp_tempnam( $spreadsheet_url );
        wp_remote_get( $spreadsheet_url, [
            'timeout'  => 15,
            'stream'   => true,
            'filename' => $filename,
        ] );

        // Get the total number of rows in a memory efficent way
        // See https://stackoverflow.com/a/43075929/1119655
        $file = new \SplFileObject( $filename, 'r' );
        $file->seek( PHP_INT_MAX );
        $total_rows = $file->key() + 1;

        // Open the csv
        $file_handle = fopen( $filename, 'r' );
        $count = 0;
        // phpcs:ignore
        while ( false !== ( $row = fgetcsv( $file_handle, 0, ',') ) ) {
            $count++;
            $tag_name = $row[1];
            $slug     = $row[2];
            $url      = $row[3];
            $cluster  = $row[4];

            // Skip processing tags that will be replaced
            if ( in_array( $tag_name, $to_be_replaced ) ) {
                continue;
            }

            $args = [
                'post_type'      => 'any',
                'post_status'    => 'public',
                'fields'         => 'ids',
                'posts_per_page' => 999,
                'tax_query' => [
                    [
                        'taxonomy' => 'post_tag',
                        'field'    => 'slug',
                        'terms'    => $slug,
                    ],
                ],
            ];

            // If a tag has other tags that wil lbe merged into it
            // get those post IDs as well
            if ( isset( $merge[ $tag_name ] ) ) {
                $additional_tags = $merge[ $tag_name ];
                // Make sure we're always dealing with an array of values
                if ( ! is_array( $additional_tags ) ) {
                    $additional_tags = [ $additional_tags ];
                }

                foreach ( $additional_tags as $additional_tag ) {
                    $args['tax_query'][] = [
                        'taxonomy' => 'post_tag',
                        'field'    => 'name',
                        'terms'    => $additional_tag,
                    ];
                }
                $args['tax_query']['relation'] = 'OR';
            }

            $query = new \WP_Query( $args );
            $post_ids = $query->posts;

            if ( empty( $post_ids ) ) {
                WP_CLI::line( $count . ') ' . $tag_name . ' is empty!' );
                continue;
            }

            // Rename the tag name if applicable
            if ( isset( $rename[ $tag_name ] ) ) {
                $tag_name = $rename[ $tag_name ];
            }

            $cluster_obj = get_page_by_title( $tag_name, 'OBJECT', Types::get_cluster_post_types() );

            // If cluster doesn't exist yet create it
            if ( ! $cluster_obj ) {

                // Post type wil lbe Topics by default
                $post_type = 'pedestal_topic';
                switch ( $cluster ) {
                    case 'Stories':
                        $post_type = 'pedestal_story';
                        break;

                    case 'People':
                        $post_type = 'pedestal_person';
                        break;

                    case 'Organizations':
                        $post_type = 'pedestal_org';
                        break;

                    case 'Places':
                        $post_type = 'pedestal_place';
                        break;

                    case 'Localities':
                        $post_type = 'pedestal_locality';
                        break;
                }

                if ( ! $post_type ) {
                    WP_CLI::warning( $count . ') "' . $tag_name . '": failed to identify post type' );
                    WP_CLI::warning( '$cluster: ' . $cluster );
                    WP_CLI::warning( '-----------------------' );
                    continue;
                }

                $new_cluster_args = [
                    'post_title'  => $tag_name,
                    'post_type'   => $post_type,
                    'post_status' => 'publish',
                    'post_name'   => $slug,
                    'guid'        => $url,
                ];
                $new_cluster_id = wp_insert_post( $new_cluster_args );
                if ( is_wp_error( $new_cluster_id ) ) {
                    $message = $new_cluster_id->get_error_message();
                    WP_CLI::warning( $count . ') "' . $tag_name . '": failed to insert cluster' );
                    WP_CLI::warning( $message );
                    WP_CLI::warning( '-----------------------' );
                    continue;
                }
                $cluster_obj = get_post( $new_cluster_id );

                // Set the locality type
                if ( 'pedestal_locality' == $post_type ) {
                    wp_set_object_terms( $new_cluster_id, $locality_type_term->term_id, 'pedestal_locality_type' );
                    update_post_meta( $new_cluster_id, 'locality_type', $locality_type_term->term_id );

                }
            }

            // Associate posts with cluster
            $connection_type = Types::get_connection_type( 'entity', $cluster_obj );
            $p2p = p2p_type( $connection_type );
            if ( ! $p2p ) {
                WP_CLI::warning( 'Bad connection type: ' . $connection_type );
            }

            if ( $p2p ) :
                foreach ( $post_ids as $id ) {
                    $connection_id = $p2p->connect( $id, $cluster_obj->ID );
                    if ( is_wp_error( $connection_id ) ) {
                        // $message = $connection_id->get_error_message();
                        // WP_CLI::warning( 'Failed to connect ' . $id . ' to ' . $tag_name );
                        // WP_CLI::warning( $message );
                        // WP_CLI::warning( '-----------------------' );
                    }
                }
            endif;

            // Display our progress periodically so we know things are working
            $percent_complete = $count / $total_rows * 100;
            $percent_complete = round( $percent_complete, 1 );
            if ( 0 == $count % 500 ) {
                WP_CLI::line( $percent_complete . '% done, ' . $count . '/' . $total_rows );
            }
        }
    }

    /**
     * Set exclude_from_home_stream post_meta values
     *
     * ## EXAMPLES
     *
     *    wp pedestal denverite-setup-homestream-exclusions --url=https://denverite.dev
     *
     * @subcommand denverite-setup-homestream-exclusions
     */
    public function denverite_setup_homestream_exclusions( $args, $assoc_args ) {
        $args = [
            'post_type'      => [ 'pedestal_article' ],
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query' => [
                [
                    'key'     => 'exclude_from_home_stream',
                    'value'   => '',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];
        $posts = new \WP_Query( $args );
        foreach ( $posts->posts as $post_id ) {
            update_post_meta( $post_id, 'exclude_from_home_stream', 'show' );
        }

        WP_CLI::success( 'Done!' );
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

    /**
     * Clear the stored metabox settings for all users
     *
     * This only needs to be run on a single site as it affects global tables.
     *
     * - Clear order data
     * - Clear expanded/collapsed state
     * - Set up some boxes to be collapsed by default until changed by a user
     *
     * ## EXAMPLES
     *
     *     wp pedestal reset-metabox-state --url=https://denverite.com/
     *
     * @subcommand reset-metabox-state
     */
    public function reset_metabox_state() {
        global $wpdb;

        WP_CLI::line( 'Clearing metabox order data...' );
        $wpdb->query( $wpdb->prepare(
            'DELETE from wp_usermeta WHERE meta_key LIKE %s',
            '%meta-box-order%'
        ) );

        WP_CLI::line( 'Clearing metabox visibility state...' );
        $wpdb->query( $wpdb->prepare(
            'DELETE from wp_usermeta WHERE meta_key LIKE %s',
            '%closedpostboxes%'
        ) );

        WP_CLI::line( 'Setting up collapsed metaboxes defaults for every user...' );
        $user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );
        foreach ( $user_ids as $user_id ) {
            $user = new User( $user_id );
            if ( ! $user instanceof User ) {
                WP_CLI::warning( "Unable to instantiate User object for user {$user_id}! Continuing..." );
                continue;
            }
            $user->setup_default_collapsed_metaboxes();
            WP_CLI::log( "Set up collapsed metaboxes defaults for user {$user_id}." );
        }

        WP_CLI::success( 'Done!' );
    }

    /**
     * Copy excerpt to summary
     *
     * ## EXAMPLES
     *
     *     wp pedestal copy-excerpt-to-summary
     *     wp pedestal copy-excerpt-to-summary --url=https://billypenn.com/
     *
     * @subcommand copy-excerpt-to-summary
     */
    public function excerpt_to_summary() {
        global $wpdb;

        $args = [
            'post_type'              => get_post_types(),
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ];
        $post_ids = new \WP_Query( $args );
        $post_ids = $post_ids->posts;
        $total_posts = count( $post_ids );
        $current_count = 0;

        WP_CLI::line( "Copying excerpt to summary field for {$total_posts} posts..." );
        foreach ( $post_ids as $post_id ) {
            $current_count++;
            $progress_str = "[{$current_count}/{$total_posts}]";
            $post = get_post( $post_id );

            $excerpt = $post->post_excerpt;
            if ( $excerpt ) {
                update_post_meta( $post_id, 'summary', $excerpt );
                WP_CLI::log( "{$progress_str} Copied existing excerpt to summary field for post {$post->ID}." );
            }
        }
        WP_CLI::success( 'Done!' );
    }

    /**
     * Migrate some post meta for admin UI update
     *
     * ## EXAMPLES
     *
     *     wp pedestal update-admin-ui-post-meta
     *     wp pedestal update-admin-ui-post-meta --url=https://billypenn.com/
     *
     * @subcommand update-admin-ui-post-meta
     */
    public function update_admin_ui_post_meta() {
        global $wpdb;

        $args = [
            'post_type'              => get_post_types(),
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ];
        $post_ids = new \WP_Query( $args );
        $post_ids = $post_ids->posts;
        $total_posts = count( $post_ids );
        $current_count = 0;

        WP_CLI::line( "Copying excerpt to summary field for {$total_posts} posts..." );
        foreach ( $post_ids as $post_id ) {
            $current_count++;
            $progress_str = "[{$current_count}/{$total_posts}]";
            $post = get_post( $post_id );

            $excerpt = $post->post_excerpt;
            if ( $excerpt ) {
                update_post_meta( $post_id, 'summary', $excerpt );
                WP_CLI::log( "{$progress_str} Copied existing excerpt to summary field for post {$post->ID}." );
            }
        }
        WP_CLI::success( 'Done copying excerpt to summary field.' );

        $hide_from_home_stream = $wpdb->get_results( $wpdb->prepare( "
            SELECT meta_id, meta_value
            FROM $wpdb->postmeta
            WHERE meta_key = 'exclude_from_home_stream'
                AND meta_value = 1
        " ) );
        foreach ( $hide_from_home_stream as $row ) {
            $wpdb->update(
                $wpdb->postmeta,
                [
                    'meta_value' => 'hide',
                ],
                [
                    'meta_id' => $row->meta_id,
                ]
            );
        }

        $show_in_home_stream = $wpdb->get_results( $wpdb->prepare( "
            SELECT meta_id, meta_value
            FROM $wpdb->postmeta
            WHERE meta_key = 'exclude_from_home_stream'
                AND meta_value != 'hide'
        " ) );
        foreach ( $show_in_home_stream as $row ) {
            $wpdb->update(
                $wpdb->postmeta,
                [
                    'meta_value' => 'show',
                ],
                [
                    'meta_id' => $row->meta_id,
                ]
            );
        }

        WP_CLI::success( 'Migrated `exclude_from_home_stream` post meta to string keys.' );
        WP_CLI::success( 'Done!' );
    }

    /**
     * Generate a CSV of data points about original content
     *
     * ## EXAMPLES
     *
     *     wp pedestal content-analysis --url=https://billypenn.com/
     *
     * @subcommand content-analysis
     */
    public function content_analysis() {
        $args = [
            'post_type'              => Types::get_original_post_types(),
            'post_status'            => 'public',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ];
        $posts = new \WP_Query( $args );
        $file_name = PEDESTAL_BLOG_NAME . '--original-content-analysis--' . date( 'Y-m-d' );
        $file_name = sanitize_title( $file_name ) . '.csv';
        $file = fopen( $file_name, 'w' );
        fputcsv( $file, [
            'Paragraphs',
            'Word Count',
            'Shortcodes',
            'Images',
            'Title',
            'Permalink',
            'Date',
        ]);
        foreach ( $posts->posts as $post_id ) {
            $post = get_post( $post_id );
            if ( empty( $post->post_content ) ) {
                continue;
            }
            // Get an HTML version of the post_content
            $html = apply_filters( 'the_content', $post->post_content );

            // Count the number of paragraphs
            $graf_count = 0;
            $dom = HtmlDomParser::str_get_html( $html );
            if ( is_object( $dom ) ) {
                $nodes = $dom->find( 'p' );
                $graf_count = count( $nodes );
            }

            // Count the total number of words
            $word_count = str_word_count( strip_tags( $html ) );

            // Count the number of shortcodes used in the post
            preg_match_all( '/' . get_shortcode_regex() . '/', $post->post_content, $matches, PREG_SET_ORDER );
            $shortcodes = count( $matches );

            // Tally shortcode tags
            $images = 0;
            foreach ( $matches as $match ) {
                $shortcode_tag = $match[2];
                if ( 'img' == $shortcode_tag ) {
                    $images++;
                }
            }

            // Get a live version of the permalink
            $permalink = get_permalink( $post_id );
            $permalink = str_replace( '.dev', '.com', $permalink );

            $row = [
                'paragraphs' => $graf_count,
                'word_count' => $word_count,
                'shortcodes' => $shortcodes,
                'images'     => $images,
                'title'      => $post->post_title,
                'permalink'  => $permalink,
                'date'       => $post->post_date,
            ];
            fputcsv( $file, $row );

            // Hopefully this will help with memory management
            unset( $post );
            unset( $html );
            unset( $dom );
        }
        fclose( $file );

        WP_CLI::success( 'Done!' );
    }

    /**
     * Categorize content and setup various categories
     *
     * ## EXAMPLES
     *
     *     wp pedestal categorize --url=https://billypenn.dev
     *
     * @subcommand categorize
     */
    public function categorize() {

        /**
         * Get posts IDs associated with a given cluster name
         *
         * @return array Post IDs found
         */
        function get_ids_from_cluster_name( $name = '' ) {
            global $wpdb;
            $name = trim( $name );
            if ( empty( $name ) ) {
                return [];
            }

            $post_types = Types::get_cluster_post_types();
            $in_placeholders = array_fill( 0, count( $post_types ), '%s' );
            $in_placeholders = implode( ', ', $in_placeholders );
            $args = array_merge( [
                $wpdb->esc_like( $name ),
            ], $post_types );

            // WordPress.WP.PreparedSQL.NotPrepared
            $cluster_ids = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `ID` FROM $wpdb->posts WHERE `post_status` = 'publish' AND `post_title` = '%s' AND `post_type` IN ($in_placeholders) LIMIT 1", // phpcs:ignore
                    $args
                )
            );
            if ( empty( $cluster_ids[0] ) ) {
                WP_CLI::line( '!!! Problem getting cluster ID from ' . $name );
                return 0;
            }
            return (int) $cluster_ids[0]->ID;
        }

        function get_category_id_by_name( $name = '' ) {
            $name = trim( $name );
            $term = get_term_by( 'name', $name, 'pedestal_category' );
            if ( $term && isset( $term->term_id ) ) {
                return $term->term_id;
            }

            // Category not found, lets add it!
            $new_term = wp_insert_term( $name, 'pedestal_category' );
            if ( is_wp_error( $new_term ) ) {
                return false;
            }
            return $new_term['term_id'];
        }

        function associate_clusters_to_category( $cluster_name = '', $category_name = '' ) {
            $category_id = get_category_id_by_name( $category_name );
            if ( ! $category_id ) {
                WP_CLI::error( 'Couldn\'t get category id for ' . $category_name );
            }
            $cluster_id = get_ids_from_cluster_name( $cluster_name );
            $ped_post = Post::get( $cluster_id );
            if ( ! Types::is_cluster( $ped_post ) ) {
                // WP_CLI::error( $name . ' is not a cluster!' );
                return;
            }
            $connected = $ped_post->get_entities_query([
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'post_type'      => Types::get_entity_post_types(),
                'fields'         => 'ids',
            ]);
            foreach ( $connected->posts as $id ) {
                $append = false;
                $result = wp_set_object_terms( $id, $category_id, 'pedestal_category', $append );
                if ( is_wp_error( $result ) ) {
                    WP_CLI::error( 'Problem setting category for "' . $cluster_name . '"' );
                }
            }
            WP_CLI::line( 'Set ' . count( $connected->posts ) . ' entities associated with ' . $cluster_name . ' to ' . $category_name );
        }

        // Setup some per site variables based on how the data is structured
        $sheet_name = '';
        switch ( get_current_blog_id() ) {
            case 2:
                $sheet_name = 'Billy+Penn';
                $category_column = 1;
                $cluster_column = 2;
                break;

            case 3:
                $sheet_name = 'The+Incline';
                $category_column = 1;
                $cluster_column = 3;
                break;

            default:
                WP_CLI::error( get_site_url() . ' has nothing to categorize!' );
                break;
        }

        /*
        // Get all of the cluster names
        $all_cluster_names = [];
        $args = [
            'post_type'              => [ 'pedestal_story', 'pedestal_topic' ],
            'post_status'            => 'public',
            'posts_per_page'         => -1,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ];
        $posts = new \WP_Query( $args );
        if ( empty( $posts->posts ) ) {
            WP_CLI::error( 'No posts found!' );
        }
        foreach ( $posts->posts as $post ) {
            $all_cluster_names[ $post->post_title ] = $post->ID;
        }
        */

        // Download the CSV of data so we don't need to worry about storing it anywhere
        // See https://stackoverflow.com/a/33727897/1119655
        $spreadsheet_url = 'https://docs.google.com/spreadsheets/d/1o3B1zMkVjZ3dy5OP8rVCJmWX8YEjgpcUAxd_SKyhwhE/gviz/tq?tqx=out:csv';
        $spreadsheet_url = add_query_arg( 'sheet', $sheet_name, $spreadsheet_url );
        WP_CLI::line( '=== Processing ' . $spreadsheet_url );
        $filename = wp_tempnam( $spreadsheet_url );
        wp_remote_get( $spreadsheet_url, [
            'timeout'  => 15,
            'stream'   => true,
            'filename' => $filename,
        ] );

        // Open the csv
        $file_handle = fopen( $filename, 'r' );
        $count = 0;
        // phpcs:ignore
        while ( false !== ( $row = fgetcsv( $file_handle, 0, ',') ) ) {
            $count++;
            if ( 1 >= $count ) {
                // Skip the first row which is headers
                continue;
            }
            $category_name = $row[ $category_column ];
            $clusters      = $row[ $cluster_column ];
            $cluster_names = explode( ',', $clusters );
            $cluster_names = array_map( 'trim', $cluster_names );
            foreach ( $cluster_names as $cluster_name ) {
                associate_clusters_to_category( $cluster_name, $category_name );
            }
        }

        // Download the CSV of data so we don't need to worry about storing it anywhere
        // See https://stackoverflow.com/a/33727897/1119655
        $spreadsheet_url = 'https://docs.google.com/spreadsheets/d/13ifnc30FSDH2jfYgPw7Ss2rHVKbhmziGHedtZoEg1oc/gviz/tq?tqx=out:csv';
        $spreadsheet_url = add_query_arg( 'sheet', $sheet_name, $spreadsheet_url );
        WP_CLI::line( '=== Processing ' . $spreadsheet_url );
        $filename = wp_tempnam( $spreadsheet_url );
        wp_remote_get( $spreadsheet_url, [
            'timeout'  => 15,
            'stream'   => true,
            'filename' => $filename,
        ] );

        // Open the csv
        $file_handle = fopen( $filename, 'r' );
        $count = 0;
        // phpcs:ignore
        while ( false !== ( $row = fgetcsv( $file_handle, 0, ',') ) ) {
            $count++;
            if ( 1 >= $count ) {
                // Skip the first row which is headers
                continue;
            }
            $category_name = $row[1];
            $cluster_name  = trim( $row[0] );
            associate_clusters_to_category( $cluster_name, $category_name );
        }

        WP_CLI::success( 'Done!' );
    }

    /**
     * Get a CSV of uncategorized posts
     *
     * ## EXAMPLES
     *
     *     wp pedestal get-uncategorized-content --url=https://billypenn.dev
     *
     * @subcommand get-uncategorized-content
     */
    public function get_uncategorized_content() {
        global $wpdb;
        $taxonomy = 'pedestal_category';
        $term_ids = get_terms( $taxonomy, [
            'fields' => 'ids',
        ] );
        $args = [
            'post_type'      => 'pedestal_article',
            'posts_per_page' => 3000,
            'post_status'    => 'publish',
            'tax_query'      => [
                [
                    'taxonomy'    => $taxonomy,
                    'field'       => 'id',
                    'terms'       => $term_ids,
                    'operator'    => 'NOT IN',
                ],
            ],
        ];
        $query = new \WP_Query( $args );

        $file_name = PEDESTAL_BLOG_NAME . '--uncategorized--' . date( 'Y-m-d' );
        $file_name = sanitize_title( $file_name ) . '.csv';
        $file = fopen( $file_name, 'w' );
        fputcsv( $file, [
            'Title',
            'Post Type',
            'Permalink',
            'Cluster Names',
            'Cluster Permalinks',
            'Date',
        ]);

        foreach ( $query->posts as $post ) {
            // WP_CLI::line( $post->post_title );
            $ped_post = Post::get( $post->ID );
            $permalink = $ped_post->get_the_permalink();
            $permalink = str_replace( '.dev', '.com', $permalink );
            $cluster_args = [
                'types'   => Types::get_cluster_post_types(),
                'flatten' => true,
            ];
            $clusters = $ped_post->get_clusters( $cluster_args );
            $cluster_names = [];
            $cluster_links = [];
            if ( ! empty( $clusters ) ) {
                foreach ( $clusters as $cluster ) {
                    if ( ! Types::is_cluster( $cluster ) ) {
                        continue;
                    }
                    $cluster_names[] = $cluster->get_the_title();
                    $cluster_links[] = str_replace( '.dev', '.com', $cluster->get_the_permalink() );
                }
            }
            $row = [
                'title'         => $ped_post->get_the_title(),
                'post_type'     => $ped_post->get_post_type_name(),
                'permalink'     => $permalink,
                'cluster_names' => implode( ', ', $cluster_names ),
                'cluster_links' => implode( ', ', $cluster_links ),
                'date'          => $post->post_date,

            ];
            fputcsv( $file, $row );

            unset( $row );
            unset( $cluster_names );
            unset( $cluster_links );
            unset( $ped_post );
            $wpdb->flush();
        }
        fclose( $file );
        WP_CLI::success( 'Done!' );
    }
}
WP_CLI::add_command( 'pedestal', '\Pedestal\CLI\CLI' );
