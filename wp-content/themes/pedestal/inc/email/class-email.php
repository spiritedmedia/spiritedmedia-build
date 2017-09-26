<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\{
    Post,
    Newsletter
};
use Pedestal\Posts\Clusters\Cluster;
use Pedestal\Objects\ActiveCampaign;
use Pedestal\Utils\Utils;


class Email {

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook in to various actions
     */
    public function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'template_redirect', [ $this, 'action_template_redirect' ] );
        add_action( 'post_updated', [ $this, 'action_post_updated_activecampaign_list' ], 10, 3 );
        add_action( 'pedestal_email_tester_all', [ $this, 'action_pedestal_email_tester_all' ] );
        add_action( 'pedestal_email_tester_subscribe-confirmation', [ $this, 'action_pedestal_email_tester_subscribe_confirmation' ] );
    }

    /**
     * Hook in to various filters
     */
    public function setup_filters() {
        add_filter( 'query_vars', function( $query_vars ) {
            $query_vars[] = 'pedestal-test-email';
            $query_vars[] = 'pedestal-subscribe-to-email-list';
            $query_vars[] = 'pedestal-confirm-subscription';
            return $query_vars;
        });
    }

    /**
     * Register rewrite rules
     */
    public function action_init_register_rewrites() {
        add_rewrite_rule( 'subscribe-to-email-list/?$', 'index.php?pedestal-subscribe-to-email-list=1', 'top' );
        add_rewrite_rule( 'confirm-subscription/([^/]+)/?$', 'index.php?pedestal-confirm-subscription=$matches[1]', 'top' );
        add_rewrite_rule( 'test-email/?$', 'index.php?pedestal-test-email=all', 'top' );
        add_rewrite_rule( 'test-email/([^/]+)/?$', 'index.php?pedestal-test-email=$matches[1]', 'top' );
    }

    /**
     * Handle various requests from rewrite rules
     */
    public function action_template_redirect() {
        if ( '1' == get_query_var( 'pedestal-subscribe-to-email-list' ) ) {
            $this->handle_subscribe_to_email_list();
            if ( isset( $_REQUEST['ajax-request'] ) && 1 == $_REQUEST['ajax-request'] ) {
                // It's an AJAX request so returning a blank page is more performant here.
                exit;
            }
            locate_template( [ 'generic-confirm-email.php' ], true );
            exit;
        }

        if ( get_query_var( 'pedestal-confirm-subscription' ) ) {
            locate_template( [ 'confirm-subscription.php' ], true );
            exit;
        }

        $test_email_query_var = get_query_var( 'pedestal-test-email' );
        if ( $test_email_query_var && current_user_can( 'manage_options' ) ) {

            // Note: You need to call exit or die() yourself in this action
            do_action( 'pedestal_email_tester_' . $test_email_query_var, $test_email_query_var );

            echo '<img src="http://i.imgur.com/lnKvhQ7.jpg" alt="Invalid email type" title="dingus!"><br>';
            die();
        }
    }

    /**
     * Handle request to /test-email/
     */
    public function action_pedestal_email_tester_all() {
        $templates = [
            'newsletter',
            'breaking-news',
            'follow-update-story',
            'subscribe-confirmation',
        ];
        echo $this->get_email_template( 'tests-index', 'ac', [
            'items' => $templates,
            'shareable' => false,
        ] );

        die();
    }

    /**
     * Handle request to /test-email/subscribe-confirmation/
     */
    public function action_pedestal_email_tester_subscribe_confirmation() {
        echo $this->get_email_template( 'subscribe-confirmation', 'ses', [
            'email_address' => 'foo@example.com',
            'confirm_link' => 'http://example.com/confirm-subscription/abc123/?list_ids=255',
            'list_names' => 'Daily Newsletter and Breaking News',
            'blog_name' => PEDESTAL_BLOG_NAME,
        ] );
        die();
    }

    /**
     * Generate a link to send to a subscriber to confirm their email address
     *
     * @param  string $email    Subscriber's email address
     * @param  string $nonce    A token to associate with the email address for confrimation
     * @param  array $list_ids  One or more list ids to include in the link
     * @return string           The confrimation link
     */
    private function get_confirm_link( $email = '', $nonce = '', $list_ids = [] ) {
        $list_ids_token = implode( ',', $list_ids );
        if ( ! $nonce ) {
            $nonce = wp_create_nonce( $email . $list_ids_token );
        }
        $confirm_link = get_site_url() . '/confirm-subscription/' . $nonce . '/?list_ids=' . $list_ids_token;
        return $confirm_link;
    }

    /**
     * Get a rendered email template
     *
     * @param string $template_name
     * @param string $email_provider Name of the email provider, either 'ac' or 'ses'
     * @param array $vars
     * @return string
     */
    public static function get_email_template( string $template_name, string $email_provider = 'ac', $vars = [] ) {
        $vars['template_name'] = $template_name;
        $vars['email_provider'] = $email_provider;
        $full_path = get_template_directory() . '/email.php';
        if ( ! file_exists( $full_path ) ) {
            return '';
        }

        $item = $vars['item'] ?? null;
        if ( Types::is_post( $item ) ) {
            $vars['preview_text'] = $item->get_meta( 'email_preview_text' );
        }

        ob_start();
        Pedestal()->set_property( 'is_email', true );
        include $full_path;
        Pedestal()->set_property( 'is_email', false );
        return ob_get_clean();
    }

    /**
     * Handles subscribing an email address to an ActiveCampaign list
     */
    private function handle_subscribe_to_email_list() {
        // Perform honeypot check to weed out bots
        $this->handle_honeypot_check();

        // Do we have all the bits of info we need?
        if ( empty( $_REQUEST['email_address'] )
            || ( empty( $_POST['list-ids'] ) && empty( $_POST['cluster-id'] ) )
        ) {
            status_header( 400 );
            echo 'Missing information.';
            exit;
        }

        $activecampaign = new ActiveCampaign;
        // Get the list id
        $cluster_id = '';
        if ( ! empty( $_POST['cluster-id'] ) ) {
            $cluster_id = $_POST['cluster-id'];
            $list_ids = Email_Lists::get_list_ids_from_cluster( $cluster_id );
        }
        if ( ! empty( $_POST['list-ids'] ) ) {
            $list_ids = $_POST['list-ids'];
        }
        if ( ! is_array( $list_ids ) ) {
            $list_ids = [ $list_ids ];
        }
        $list_ids = array_map( 'intval', $list_ids );
        $args = [
            'ids' => $list_ids,
        ];

        // Get details of the list from ActiveCampaign
        $raw_lists = $activecampaign->get_lists( $args );
        $raw_lists_check = (array) $raw_lists;
        // Check if $raw_lists is an empty object
        if ( empty( $raw_lists_check ) && ! empty( $cluster_id ) ) {
            Email_Lists::delete_list_id_from_meta( $cluster_id );
            $list_ids = Email_Lists::get_list_ids_from_cluster( $cluster_id );
            $args['ids'] = $list_ids;
            $raw_lists = $activecampaign->get_lists( $args );
        }

        // Do some name formatting
        $list_names = [];
        $list_ids = [];
        foreach ( $raw_lists as $raw_list ) {
            $list_names[] = Email_Lists::scrub_list_name( $raw_list->name );
            $list_ids[] = intval( $raw_list->id );
        }
        switch ( count( $list_names ) ) {
            case 1:
                $list_names = $list_names[0];
                break;
            case 2:
                $list_names = implode( ' and ', $list_names );
                break;
            default:
                $index = count( $list_names ) - 1;
                $list_names[ $index ] = 'and ' . $list_names[ $index ];
                $list_names = implode( ', ', $list_names );
                break;

        }
        $email = sanitize_email( $_REQUEST['email_address'] );
        // Create a nonce that we will store in a transient until the subscriber confirms their email address
        // Note: We add $list_names to the mix so we don't have a collision if the same email is used to subscribe to multiple email lists
        $nonce = wp_create_nonce( $email . $list_names );
        $confirm_link = $this->get_confirm_link( $email, $nonce, $list_ids );
        $subject = sprintf( 'You have subscribed to %s’s “%s” emails', PEDESTAL_BLOG_NAME, $list_names );
        $body = $this->get_email_template( 'subscribe-confirmation', 'ses', [
            'email_address' => $email,
            'confirm_link' => esc_url( $confirm_link ),
            'list_names' => $list_names,
            'blog_name' => PEDESTAL_BLOG_NAME,
        ] );

        $confirmation_data = [
            'email' => $email,
            'list_ids' => $list_ids,
            'datetime' => date( 'Y-m-d H:i:s' ),
        ];
        $transient_key = 'pending_email_confirmation_' . $nonce;
        set_transient( $transient_key, $confirmation_data, 12 * HOUR_IN_SECONDS );
        add_filter( 'wp_mail_content_type', function() {
            return 'text/html';
        } );
        return wp_mail( $email, $subject, $body );
    }

    /**
     * Verify a $_POST request isn't from a dumb bot
     *
     * @return bool  True if the request passes the check
     */
    public function handle_honeypot_check() {
        if ( isset( $_POST['pedestal-current-year-check'] ) && isset( $_POST['pedestal-blank-field-check'] ) ) {
            if ( empty( $_POST['pedestal-blank-field-check'] ) && date( 'Y' ) == $_POST['pedestal-current-year-check'] ) {
                return true;
            }
        }

        status_header( 400 );
        echo sprintf( 'It seems you are a bot. If you are not a bot, please email %s.', PEDESTAL_EMAIL_CONTACT );
        exit;
    }

    /**
     * Rename a Cluster's ActiveCampaign list name if its title changes
     *
     * @param  int $post_id          ID of the post being saved
     * @param  WP Post $post_after   Post object after update
     * @param  WP Post $post_before  Post object before update
     */
    public function action_post_updated_activecampaign_list( $post_id, $post_after, $post_before ) {
        if ( $post_after->post_title === $post_before->post_title ) {
            return;
        }

        if ( ! Types::is_cluster( $post_after->post_type ) ) {
            return;
        }
        $post_after = Cluster::get( $post_id );
        $list_id = Email_Lists::get_list_ids_from_cluster( $post_id );
        $new_name = $post_after->get_activecampaign_list_name();

        $activecampaign = new ActiveCampaign;
        $response = $activecampaign->edit_list( $list_id, [
            'name' => $new_name,
        ] );
    }

    /**
     * Get the subscriber count for a given ActiveCampaign List ID
     *
     * @param  int $list_id  The ActiveCampaign List ID
     * @param  bool $force    If True, bypasses any caching to force refresh the number
     * @return int           The number of subscribers for the list
     */
    public static function get_subscriber_count( $list_id = 0, $force = false ) {
        $cluster = Email_Lists::get_clusters_from_list_ids( $list_id );
        $_valid_cluster = ( ! empty( $cluster ) && Types::is_cluster( $cluster[0] ) );
        $subscriber_count = false;

        if ( $_valid_cluster ) {
            $cluster = $cluster[0];
            $subscriber_count = $cluster->get_meta( 'subscriber_count' );
        } else {
            $option_key = 'activecampaign_subscriber_count_' . $list_id;
            $last_updated_option_key = 'activecampaign_subscriber_count_last_updated_' . $list_id;
            $subscriber_count = get_option( $option_key );
        }

        // The post meta / option returns false / null if not found but it is
        // also possible it has a value of ''
        if ( false !== $subscriber_count && ! $force ) {
            if ( empty( $subscriber_count ) ) {
                $subscriber_count = 0;
            }
            return $subscriber_count;
        }

        // No stored data found! Let's ask ActiveCampaign
        $activecampaign = new ActiveCampaign;
        $list = $activecampaign->get_list( $list_id );
        if ( ! $list || ! isset( $list->subscriber_count ) ) {
            // We have a problem so set count to -1
            $subscriber_count = -1;
        } else {
            $subscriber_count = $list->subscriber_count;
        }

        if ( $_valid_cluster ) {
            $cluster->set_meta( 'subscriber_count', $subscriber_count );
            $cluster->set_meta( 'subscriber_count_last_updated', time() );
        } else {
            update_option( $option_key, $subscriber_count );
            update_option( $last_updated_option_key, time() );
        }

        return $subscriber_count;
    }

    /**
     * Refresh subscriber counts for clusters and optionally primary lists
     *
     * @param  array|\WP_Query  $posts                 Array of IDs or \WP_Query
     * @param  boolean          $refresh_primary_lists Update the newsletter and breaking news subscriber counts as well?
     * @return array Associative array of cluster IDs and new subscriber counts
     */
    public static function refresh_subscriber_counts( $posts, $refresh_primary_lists = true ) {
        $result = [];
        $force = true;

        if ( $posts instanceof \WP_Query ) {
            $posts = Post::get_posts_from_query( $posts );
        } elseif ( is_array( $posts ) && is_numeric( $posts[0] ) ) {
            $posts = Post::get_posts_from_ids( $posts );
        } else {
            return;
        }

        foreach ( $posts as $ped_post ) {
            if ( ! Types::is_cluster( $ped_post ) ) {
                continue;
            }
            $result[ $ped_post->get_id() ] = $ped_post->get_following_users_count( $force );
        }

        if ( $refresh_primary_lists ) {
            $email_lists = new Email_Lists;
            foreach ( $email_lists->get_all_newsletters() as $id => $name ) {
                static::get_subscriber_count( $id, $force );
            }
        }

        return $result;
    }

    /**
     * Handle sending the email campaign
     *
     * @param  Array $args  Details about the campaign
     * @return Boolean      Did the camapign send successfully?
     */
    public static function send_email( $args = [] ) {
        $default_args = [
            'messages'              => false,
            'name'                  => false,     // Name to describe the campaign in ActiveCampaign
            'list'                  => false,     // List ID or List Name to send the email to via ActiveCampaign
            'test_email_addresses'  => [],        // List of test email addresses to send the email to
            'email_type'            => 'Unknown', // What type of email campaign is being sent? Newsletter, Breaking News, Folow Update etc.
        ];
        $args = wp_parse_args( $args, $default_args );
        // Sanity check
        if ( ! $args['list'] ) {
            // If we don't have the list to send the email to then we can't proceed
            return false;
        }
        if ( ! $args['messages'] || ! is_array( $args['messages'] ) ) {
            // Missing one or more key component for sending out an email, aren't we now?
            return false;
        }
        if ( ! $args['name'] ) {
            // Use the subject of the first message as the Campaign Name
            $args['name'] = $args['messages'][0]['subject'];
        }
        $test_emails = $args['test_email_addresses'];
        if ( is_string( $test_emails ) ) {
            $test_emails = array_map( 'trim', explode( ',', $test_emails ) );
        }
        if ( ! empty( $test_emails ) ) {
            // Ensure product@spiritedmedia.com gets sent the test emails for every send
            $test_emails[] = 'product@spiritedmedia.com';
            $to = implode( ',', $test_emails );
            add_filter( 'wp_mail_content_type', function() {
                return 'text/html';
            } );
            $args['test_email_addresses'] = $test_emails;
            foreach ( $args['messages'] as $message ) {
                $subject = '[TEST] ' . $message['subject'];
                $resp = wp_mail( $to, $subject, $message['html'] );
            }
            do_action( 'pedestal_sent_test_email_campaign', $args );
            return true;
        }
        $activecampaign = new ActiveCampaign;
        $resp = $activecampaign->send_campaign( $args );
        if ( isset( $resp->result_code ) && 1 !== $resp->result_code ) {
            return false;
        }
        do_action( 'pedestal_sent_email_campaign', $args );
        return true;
    }

    /**
     * Handle sanitizing email addresses beforing sending a test email
     *
     * @param  string|Array $addresses  Comma separated list of email addresses
     * @return Array                    Sanitized email addresses
     */
    public static function sanitize_test_email_addresses( $addresses = '' ) {
        if ( is_string( $addresses ) ) {
            $addresses = explode( ',', $addresses );
        }
        array_map( 'trim', $addresses );
        if ( empty( $addresses ) ) {
            $addresses = '';
        }
        return $addresses;
    }
}
