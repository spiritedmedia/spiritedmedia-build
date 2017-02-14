<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use Timber\Timber;

use Pedestal\Utils\Utils;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Objects\Newsletter_Lists;
use Pedestal\Posts\Post;
use Pedestal\Posts\Newsletter;

use Pedestal\Posts\Clusters\Cluster;
use Pedestal\Posts\Clusters\Geospaces\Localities\Neighborhood;
use Pedestal\Posts\Clusters\Story;

use Pedestal\Objects\Notifications;

use Pedestal\Objects\ActiveCampaign;

class Subscriptions {

    private static $default_notify_channel = PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL;

    private static $instance;

    // The meta key where we store associated list IDs for a given post
    private static $list_id_meta_key = 'activecampaign-list-id';

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Subscriptions;
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Set up subscription actions
     */
    private function setup_actions() {
        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'template_redirect', [ $this, 'action_template_redirect' ] );
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ], 10, 2 );
        add_action( 'post_updated', [ $this, 'action_post_updated_activecampaign_list' ], 10, 3 );
        add_action( 'save_post', [ $this, 'action_save_post_send_email' ], 100 );
        add_action( 'admin_footer', function() {
            $post = get_post();
            if ( ! $post instanceof \WP_Post ) {
                return;
            }
            if ( 'pedestal_newsletter' !== $post->post_type ||  'publish' === $post->post_status ) {
                return;
            }
            ?>
            <script>
                jQuery(document).ready(function($) {
                    var $publish = $('#publish');
                    var buttonValue = $publish.val();
                    if ( buttonValue !== 'Send' && buttonValue !== 'Send Newsletter' ) {
                        return;
                    }
                    $publish.on('click', function(e) {
                        return confirm('Are you sure you want to send the newsletter?');
                    });
                });
            </script>
            <?php
        }, 10, 1 );
    }

    /**
     * Set up subscription filters
     */
    private function setup_filters() {
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
     * Handle various requests
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

        if ( get_query_var( 'pedestal-test-email' ) && current_user_can( 'manage_options' ) ) {
            $this->handle_test_email_request();
            exit;
        }
    }

    /**
     * Add subscription-related meta boxes
     */
    public function action_add_meta_boxes( $post_type, $post ) {
        if ( ! current_user_can( 'send_emails' ) || ! in_array( $post_type, Types::get_emailable_post_types() ) ) {
            return;
        }

        $callback = '';
        $callback_args = [];
        $email_type = '';

        if ( post_type_supports( $post_type, 'breaking' ) ) {
            // Don't show the Breaking News meta box if the post isn't published
            if ( 'publish' !== $post->post_status ) {
                return;
            }
            $email_type = 'Breaking News';
            $callback = 'handle_notify_breaking_news_subscribers_meta_box';
        } elseif ( 'pedestal_newsletter' === $post_type ) {
            $email_type = 'Newsletter';
            $callback = 'handle_notify_primary_list_subscribers_meta_box';
        } elseif ( Types::is_cluster( $post_type ) ) {
            $email_type = Types::get_post_type_labels( $post_type )['singular_name'];
            $callback = 'handle_notify_cluster_subscribers_meta_box';
        }

        if ( empty( $email_type ) || ! method_exists( $this, $callback ) ) {
            return;
        }

        $email_template = sanitize_title( $email_type );
        $callback_args = [
            'email_type' => $email_type,
            'template'   => $email_template,
        ];

        add_meta_box( 'pedestal-' . $email_template . '-notify-subscribers',
            esc_html__( 'Notify ' . $email_type . ' Subscribers', 'pedestal' ),
            [ $this, $callback ],
            $post_type, 'side', 'default', $callback_args
        );
    }

    /**
     * Handle the meta box to trigger an email send to cluster subscribers
     *
     * @param  object $post WP_Post
     * @return string HTML
     */
    public function handle_notify_cluster_subscribers_meta_box( $post ) {
        $post_id = $post->ID;
        $cluster = Cluster::get_by_post_id( (int) $post_id );
        $type = $cluster->get_type();
        $entities = $cluster->get_entities_since_last_email_notification();
        $entity_count = count( $entities );

        $last_sent = '';
        $last_sent_human_diff = 'N/A';
        if ( $last_sent_date = $cluster->get_last_email_notification_date() ) {
            $last_sent = get_date_from_gmt( date( 'Y-m-d H:i:s', $last_sent_date ), 'm/d/Y g:i a' );
            $last_sent_human_diff = human_time_diff( $last_sent_date ) . ' ago';
        }

        $attributes = [
            'style' => 'display: block; width: 100%;',
        ];
        $test_button = '';
        if ( empty( $entities ) ) {
            $attributes['disabled'] = 'disabled';
        } else {
            $test_button = get_submit_button(
                esc_html__( 'Send Test Email', 'pedestal' ),
                'secondary',
                'pedestal-cluster-send-test-email',
                false,
                $attributes
            );
        }

        $send_button = '';
        $follower_label = 'Followers';
        $cluster_count = $cluster->get_following_users_count( $force = true );
        if ( 0 == $cluster_count ) {
            $attributes['disabled'] = 'disabled';
        }
        if ( 1 == $cluster_count ) {
            $follower_label = 'Follower';
        }
        $send_button = get_submit_button(
            sprintf( esc_html__( 'Send Email To %d %s', 'pedestal' ), $cluster_count, $follower_label ),
            'primary',
            'pedestal-cluster-notify-subscribers',
            true,
            $attributes
        );

        $context = [
            'entities' => $entities,
            'entity_count' => number_format( $entity_count ),
            'last_sent' => $last_sent,
            'last_sent_human_diff' => $last_sent_human_diff,
            'test_button' => $test_button,
            'send_button' => $send_button,
        ];
        Timber::render( 'partials/admin/metabox-send-email-cluster.twig', $context );
    }

    /**
     * Handle the meta box to trigger a newsletter email send to subscribers
     *
     * @param  object $post WP_Post
     */
    public function handle_notify_primary_list_subscribers_meta_box( $post, $metabox ) {
        $post = Post::get_by_post_id( (int) $post->ID );
        $sent_date = $post->get_sent_date();
        $status = $post->get_status();
        $args = $metabox['args'];

        if ( ! current_user_can( 'send_emails' ) ) {
            return;
        }

        if ( empty( $args['email_type'] ) || empty( $args['template'] ) ) {
            echo 'Something went wrong. Please contact #product.';
            return;
        }

        $context = [
            'item'            => $post,
            'template'        => $args['template'],
            'disabled'        => false,
            'message'         => '',
            'confirm_message' => '',
            'btn_send_test'   => get_submit_button(
                esc_html__( 'Send Test Email', 'pedestal' ),
                'secondary',
                'pedestal-' . $args['template'] . '-send-test-email',
                $wrap = false
            ),
        ];

        if ( $sent_date ) {

            $sent_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $sent_date ), PEDESTAL_DATETIME_FORMAT );
            $sent_confirm = sprintf( 'The %s email was sent on %s.',
                strtolower( $args['email_type'] ),
                $sent_date
            );
            $context['message'] = esc_html__( $sent_confirm, 'pedestal' );

        }

        Timber::render( 'partials/admin/metabox-send-email-primary.twig', $context );
    }

    /**
     * Handle the meta box to trigger a breaking news email send to subscribers
     *
     * @param  object $post WP_Post
     */
    public function handle_notify_breaking_news_subscribers_meta_box( $post, $metabox ) {
        $post = Post::get_by_post_id( (int) $post->ID );
        $sent_date = $post->get_sent_date();
        $status = $post->get_status();
        $args = $metabox['args'];

        if ( empty( $args['email_type'] ) || empty( $args['template'] ) ) {
            echo 'Something went wrong. Please contact #product.';
            return;
        }

        $send_button_text = esc_html__( sprintf(
            'Send %s To %d Subscribers',
            $args['email_type'],
            $this->get_breaking_news_subscriber_count()
        ), 'pedestal' );

        $context = [
            'item'            => $post,
            'template'        => $args['template'],
            'message'         => '',
            'confirm_message' => '',
            'btn_send'        => get_submit_button(
                $send_button_text,
                'primary',
                'pedestal-' . $args['template'] . '-notify-subscribers',
                $wrap = true
            ),
            'btn_send_test'   => get_submit_button(
                esc_html__( 'Send Test Email', 'pedestal' ),
                'secondary',
                'pedestal-' . $args['template'] . '-send-test-email',
                $wrap = false
            ),
        ];

        if ( $sent_date ) {

            $sent_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $sent_date ), PEDESTAL_DATETIME_FORMAT );
            $sent_confirm = sprintf( 'The %s email was sent on %s.',
                strtolower( $args['email_type'] ),
                $sent_date
            );
            $context['message'] = wpautop( esc_html( $sent_confirm ) );
            // Breaking News was already sent, don't show the Send button
            $context['btn_send'] = '';

        }

        Timber::render( 'partials/admin/metabox-send-email-breaking-news.twig', $context );
    }

    /**
     * Rename a Cluster's ActiveCampaign list name if its title changes
     */
    public function action_post_updated_activecampaign_list( $post_id, $post_after, $post_before ) {
        if ( ! Types::is_cluster( $post_after->post_type ) ) {
            return;
        }

        if ( $post_after->post_title === $post_before->post_title ) {
            return;
        }

        $post_after = Cluster::get_by_post_id( $post_id );
        $list_id = self::get_list_ids_from_cluster( $post_id );
        $new_name = $post_after->get_activecampaign_list_name();

        $activecampaign = new ActiveCampaign;
        $response = $activecampaign->edit_list( $list_id, [
            'name' => $new_name,
        ] );
    }

    /**
     * Handle requests to send email notifications
     */
    public function action_save_post_send_email( $post_id ) {
        $post_type = get_post_type( $post_id );
        if ( ! in_array( $post_type, Types::get_emailable_post_types() ) ) {
            return;
        }

        $is_test_email = false;

        // Clusters
        if ( ! empty( $_POST['pedestal-cluster-notify-subscribers'] ) || ! empty( $_POST['pedestal-cluster-send-test-email'] ) ) {
            $cluster = Cluster::get_by_post_id( (int) $post_id );
            $args = [];
            if ( ! empty( $_POST['pedestal-cluster-send-test-email'] ) ) {
                $is_test_email = true;
                $args['test_email_addresses'] = array_map( 'trim', explode( ',', $_POST['test-email-addresses'] ) );
            }
            $result = $this->send_email_to_users_following_cluster( $cluster, $args );

            if ( $result && ! $is_test_email ) {
                // Set the last sent email date
                $cluster->set_last_email_notification_date();
            }
        }

        // Newsletters
        if ( 'pedestal_newsletter' === $post_type ) {
            $newsletter = Newsletter::get_by_post_id( (int) $post_id );
            $args = [];
            if ( ! empty( $_POST['pedestal-newsletter-send-test-email'] ) ) {
                $is_test_email = true;
                $args['test_email_addresses'] = array_map( 'trim', explode( ',', $_POST['test-email-addresses'] ) );
            }

            // If the newsletter isn't being published, then bail...
            if ( 'publish' !== $newsletter->get_status() && ! $is_test_email ) {
                return;
            }

            // If the newsletter was already sent then bail...
            if ( $newsletter->get_sent_date() && ! $is_test_email ) {
                return;
            }

            if ( $newsletter->get_meta( 'newsletter_is_test' ) ) {
                $args['status'] = 0;
            }
            $result = $this->send_email_to_newsletter_followers( $newsletter, $args );
            if ( $result && ! $is_test_email ) {
                // Set the last sent email date
                $newsletter->set_sent_flag( 'newsletter' );
                $newsletter->set_sent_date( time() );
            }
        }

        // Breaking News
        $breaking_news_confirm = ( ! empty( $_POST['confirm-send-email'] ) && 'SEND BREAKING NEWS' === strtoupper( $_POST['confirm-send-email'] ) );
        if ( ( ! empty( $_POST['pedestal-breaking-news-notify-subscribers'] )
                && $breaking_news_confirm
             ) || ! empty( $_POST['pedestal-breaking-news-send-test-email'] )
        ) {
            $post = Post::get_by_post_id( (int) $post_id );
            $args = [];
            if ( ! empty( $_POST['pedestal-breaking-news-send-test-email'] ) ) {
                $is_test_email = true;
                $args['test_email_addresses'] = array_map( 'trim', explode( ',', $_POST['test-email-addresses'] ) );
            }
            $result = $this->send_email_to_breaking_news_followers( $post, $args );
            if ( $result && ! $is_test_email ) {
                // Set the last sent email date
                $post->set_sent_flag( 'breaking-news' );
                $post->set_sent_date( time() );
            }
        }
    }

    /**
     * Send an email campaign to those following a given cluster
     * @param  Cluster $cluster  The cluster we are notifing followers about
     * @param  Array $args       Options
     * @return Boolean           Did the camapign send successfully?
     */
    private function send_email_to_users_following_cluster( $cluster, $args = [] ) {
        if ( ! Types::is_cluster( $cluster ) ) {
            return false;
        }
        $cluster_id = $cluster->get_id();
        $list_id = self::get_list_ids_from_cluster( $cluster_id );
        $body = $this->get_email_template( 'follow-update', 'ac', [
            'item'       => $cluster,
            'entities'   => $cluster->get_entities_since_last_email_notification(),
            'email_type' => $cluster->get_email_type(),
            'shareable'  => true,
        ] );

        $subject = sprintf( '%s Update: %s', PEDESTAL_BLOG_NAME, $cluster->get_title() );
        $sending_args = [
            'html'       => $body,
            'subject'    => $subject,
            'list'       => $list_id,
            'email_type' => 'Follow Update',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        return $this->send_email( $sending_args );
    }

    /**
     * Send an email campaign to newsletter subscribers
     * @param  Newsletter $newsletter  The newsletter we are notifing subscribers about
     * @param  Array $args             Options
     * @return Boolean                 Did the camapign send successfully?
     */
    private function send_email_to_newsletter_followers( $newsletter, $args ) {
        $body = $this->get_email_template( 'newsletter', 'ac', [
            'item'       => $newsletter,
            'email_type' => 'Daily',
            'shareable'  => true,
        ] );

        $subject = sprintf( '%s Daily: %s', PEDESTAL_BLOG_NAME, $newsletter->get_title() );
        $newsletter_lists = Newsletter_Lists::get_instance();
        $daily_newsletter_id = $newsletter_lists->get_newsletter_list_id( 'Daily Newsletter' );
        $sending_args = [
            'html'       => $body,
            'subject'    => $subject,
            'list'       => $daily_newsletter_id,
            'email_type' => 'Daily Newsletter',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        return $this->send_email( $sending_args );
    }

    /**
     * Send an email campaign to breaking news subscribers
     * @param  Post $post   The entity we are notifing subscribers about
     * @param  Array $args  Options
     * @return Boolean      Did the camapign send successfully?
     */
    private function send_email_to_breaking_news_followers( $post, $args ) {
        $body = $this->get_email_template( 'breaking-news', 'ac', [
            'item'       => $post,
            'email_type' => 'Breaking News',
            'shareable'  => true,
        ] );
        $subject = sprintf( 'BREAKING NEWS: %s', $post->get_title() );
        $newsletter_lists = Newsletter_Lists::get_instance();
        $breaking_newsletter_id = $newsletter_lists->get_newsletter_list_id( 'Breaking News' );
        $sending_args = [
            'html'       => $body,
            'subject'    => $subject,
            'name'       => $subject,
            'list'       => $breaking_newsletter_id,
            'email_type' => 'Breaking News',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        return $this->send_email( $sending_args );
    }

    /**
     * Handle sending the email campaign
     * @param  Array $args  Details about the campaign
     * @return Boolean      Did the camapign send successfully?
     */
    private function send_email( $args = [] ) {
        $default_args = [
            'html'                  => false,     // The HTML body of the email
            'subject'               => false,     // The subject line of the email
            'name'                  => false,     // Name to describe the campaign in ActiveCampaign
            'list'                  => false,     // List ID or List Name to send the email to via ActiveCampaign
            'test_email_addresses'  => [],        // List of test email addresses to send the email to
            'email_type'            => 'Unknown', // What type of email campaign is being sent? Newsletter, Breaking News, Folow Update etc.
        ];
        $args = wp_parse_args( $args, $default_args );
        // Sanity check
        if ( ! $args['html'] || ! $args['subject'] || ! $args['list'] ) {
            // Missing one or more key component for sending out an email, aren't we now?
            return false;
        }
        if ( ! $args['name'] ) {
            $args['name'] = $args['subject'];
        }
        $test_emails = $args['test_email_addresses'];
        if ( is_string( $test_emails ) ) {
            $test_emails = array_map( 'trim', explode( ',', $test_emails ) );
        }
        if ( ! empty( $test_emails ) ) {
            $args['subject'] = '[TEST] ' . $args['subject'];
            // Ensure product@spiritedmedia.com gets sent the test emails for every send
            $test_emails[] = 'product@spiritedmedia.com';
            $to = implode( ',', $test_emails );
            add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
            $args['test_email_addresses'] = $test_emails;
            $resp = wp_mail( $to, $args['subject'], $args['html'] );
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
            $list_ids = self::get_list_ids_from_cluster( $cluster_id );
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
            self::delete_list_id_from_meta( $cluster_id );
            $list_ids = self::get_list_ids_from_cluster( $cluster_id );
            $args['ids'] = $list_ids;
            $raw_lists = $activecampaign->get_lists( $args );
        }

        // Do some name formatting
        $list_names = [];
        $list_ids = [];
        foreach ( $raw_lists as $raw_list ) {
            $list_names[] = $this->scrub_list_name( $raw_list->name );
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
        add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
        return wp_mail( $email, $subject, $body );
    }

    /**
     * Generate a link to send to a subscriber to confirm their email address
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
     * Get list IDs from a given cluster
     * @param  integer $cluster_id                   Post ID of the Cluster
     * @param  boolean $create_list_if_doesnt_exist  Whether to create a list if it doesn't exist
     * @return False|integer                         List id or False if no list id found
     */
    public static function get_list_ids_from_cluster( $cluster_id = 0, $create_list_if_doesnt_exist = true ) {
        $cluster_id = intval( $cluster_id );
        if ( ! $cluster_id ) {
            return false;
        }

        $list_id = self::get_list_id_from_meta( $cluster_id );
        if ( $list_id ) {
            return intval( $list_id );
        }

        // Looks like we'll need to fetch the List ID from ActiveCampaign
        $activecampaign = new ActiveCampaign;
        $cluster = Post::get_by_post_id( $cluster_id );
        if ( ! Types::is_cluster( $cluster ) ) {
            return false;
        }

        $list_name = $cluster->get_activecampaign_list_name();

        // Check if list already exists
        $resp = $activecampaign->get_list( $list_name );
        // List not found, let's add a new list
        if ( ! $resp && $create_list_if_doesnt_exist ) {
            $args = [
                'name' => $list_name,
            ];
            $resp = $activecampaign->add_list( $args );
        }
        $list_id = intval( $resp->id );
        $cluster->add_meta( self::$list_id_meta_key, $list_id );
        // Clear any subscriber count transients that might be set
        delete_transient( 'activecampaign_subscriber_count_' . $list_id );
        return $list_id;
    }

    /**
     * Get a Cluster object from a given ActiveCampaign List ID
     * @param  array $list_ids  One or more ActiveCampaign List IDs
     * @return array            Array of Cluster objects
     */
    public static function get_clusters_from_list_ids( $list_ids = [] ) {
        $output = [];
        if ( is_string( $list_ids ) ) {
            $list_ids = [ $list_ids ];
        }
        $list_ids = array_map( 'intval', $list_ids );
        $args = [
        	'post_type' => 'any',
        	'meta_key' => self::$list_id_meta_key,
        	'meta_value' => $list_ids,
        	'fields' => 'ids',
        	'no_found_rows' => true,
        ];
        $meta_query = new \WP_Query( $args );
        $post_ids = $meta_query->posts;
        if ( empty( $post_ids ) ) {
            return $output;
        }
        foreach ( $post_ids as $post_id ) {
            $cluster = Cluster::get_by_post_id( $post_id );
            if ( Types::is_cluster( $cluster ) ) {
                $output[] = $cluster;
            }
        }
        return $output;
    }

    /**
     * Get an ActiveCampaign List ID stored as post_meta
     * @param  integer $post_id  Post ID to check
     * @return string            List ID
     */
    public static function get_list_id_from_meta( $post_id = 0 ) {
        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return false;
        }
        return get_post_meta( $post_id, self::$list_id_meta_key, true );
    }

    /**
     * Delete the ActiveCampaign List ID stored as post_meta
     * @param  integer $post_id  Post ID to check
     * @return boolean           False for failure. True for success.
     */
    public static function delete_list_id_from_meta( $post_id = 0 ) {
        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return false;
        }
        return delete_post_meta( $post_id, self::$list_id_meta_key );
    }

    /**
     * Verify a $_POST request isn't from a dumb bot
     * @return Boolean  True if the request passes the check
     */
    private function handle_honeypot_check() {
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
     * Remove '- <blog name>' from list name which we do to make them unique within ActiveCampaign
     * @param  string $haystack  String to scrub
     * @return string            Scrubbed list name
     */
    private function scrub_list_name( $haystack = '' ) {
        $needle = '- ' . PEDESTAL_BLOG_NAME;
        $parts = explode( $needle, $haystack );
        return trim( $parts[0] );
    }

    /**
     * Get the number of users subscribed to the Daily Newsletter list
     *
     * @return int
     */
    public function get_daily_newsletter_subscriber_count() {
        $newsletter_lists = Newsletter_Lists::get_instance();
        $list_id = $newsletter_lists->get_newsletter_list_id( 'Daily Newsletter' );
        return $this->get_subscriber_count( $list_id );
    }

    /**
     * Get the number of users subscribed to the Breaking News list
     *
     * @return int
     */
    public function get_breaking_news_subscriber_count() {
        $newsletter_lists = Newsletter_Lists::get_instance();
        $list_id = $newsletter_lists->get_newsletter_list_id( 'Breaking News' );
        return $this->get_subscriber_count( $list_id );
    }

    /**
     * Get the subscriber count for a given ActiveCampaign List ID
     * @param  integer $list_id  The ActiveCampaign List ID
     * @param  boolean $force    If True, bypasses any caching to force refresh the number
     * @return integer           The number of subscribers for the list
     */
    public static function get_subscriber_count( $list_id = 0, $force = false ) {
        $key = 'activecampaign_subscriber_count_' . $list_id;

        // Prevent the transients from all expiring at once by fuzzing the expiration time
        $fuzz = rand( 0, 10 ) * 0.1;
        $expiration = ( 1 + $fuzz ) * HOUR_IN_SECONDS;

        $subscriber_count = get_transient( $key );
        // The transient returns false if not found but it is also possible the transient has a value of ''
        if ( false !== $subscriber_count && ! $force ) {
            if ( empty( $subscriber_count ) ) {
                $subscriber_count = 0;
            }
            return $subscriber_count;
        }

        // No transient found! Let's ask ActiveCampaign
        $activecampaign = new ActiveCampaign;
        $list = $activecampaign->get_list( $list_id );
        if ( ! $list || ! isset( $list->subscriber_count ) ) {
            // We have a problem so return -1
            set_transient( $key, -1, $expiration );
            return -1;
        }
        set_transient( $key, $list->subscriber_count, $expiration );
        return $list->subscriber_count;
    }

    /**
     * Get a rendered email template
     *
     * @param string $template_name
     * @param string $email_provider Name of the email provider, either 'ac' or 'ses'
     * @param array $vars
     * @return string
     */
    private function get_email_template( string $template_name, string $email_provider, $vars = [] ) {
        $vars['template_name'] = $template_name;
        $vars['email_provider'] = $email_provider;
        $full_path = get_template_directory() . '/email.php';
        if ( ! file_exists( $full_path ) ) {
            return '';
        }

        ob_start();
        include $full_path;
        return ob_get_clean();
    }

    /**
     * Notify Slack of the current newsletter subscriber count
     */
    public function notify_newsletter_subscriber_count( $notification_args = [] ) {
        $count = $this->get_daily_newsletter_subscriber_count();

        if ( empty( $count ) ) {
            return;
        }

        $notification_args = wp_parse_args( $notification_args, [
            'channel' => PEDESTAL_SLACK_CHANNEL_NEWSLETTER,
        ] );

        $msg = sprintf( 'There are currently %d email addresses subscribed to the Daily Newsletter.', $count );
        $notifier = new Notifications;
        $notifier->send( $msg, $notification_args );
    }

    /**
     * Render test email templates when /test-email/ is requested
     */
    public function handle_test_email_request() {
        switch ( get_query_var( 'pedestal-test-email' ) ) {
            case 'all':
                $templates = [
                    'newsletter',
                    'breaking-news',
                    'follow-update-story',
                    'subscribe-confirmation',
                ];
                echo $this->get_email_template( get_query_var( 'pedestal-test-email' ), 'ac', [
                    'items' => $templates,
                    'shareable' => false,
                ] );
                break;

            case 'newsletter':
                $newsletters = new \WP_Query( [
                    'post_type'      => 'pedestal_newsletter',
                    'posts_per_page' => 1,
                ] );
                if ( empty( $newsletters->posts ) ) {
                    echo 'No published newsletters to test with.';
                    break;
                }
                $newsletter = new Newsletter( $newsletters->posts[0] );
                echo $this->get_email_template( 'newsletter', 'ac', [
                    'item' => $newsletter,
                    'email_type' => 'Daily',
                    'shareable' => true,
                ] );
                break;

            case 'breaking-news':
                $breaking_news = new \WP_Query( [
                    'post_type'      => Types::get_emailable_post_types(),
                    'meta_query'     => [
                        [
                            'key'   => 'sent_email',
                            'value' => 'breaking-news',
                        ],
                    ],
                    'posts_per_page' => 1,
                ] );
                if ( empty( $breaking_news->posts ) ) {
                    echo 'No breaking news emails to test with.';
                    break;
                }
                $post = Post::get_by_post_id( $breaking_news->posts[0]->ID );
                echo $this->get_email_template( 'breaking-news', 'ac', [
                    'item' => $post,
                    'email_type' => 'Breaking News',
                    'shareable' => true,
                ] );
                break;

            case 'subscribe-confirmation':
                echo $this->get_email_template( get_query_var( 'pedestal-test-email' ), 'ses', [
                    'email_address' => 'foo@example.com',
                    'confirm_link' => 'http://example.com/confirm-subscription/abc123/?list_ids=255',
                    'list_names' => 'Daily Newsletter and Breaking News',
                    'blog_name' => PEDESTAL_BLOG_NAME,
                ] );
                break;

            case 'follow-update-story':
                $stories = new \WP_Query( [
                    'post_type'      => 'pedestal_story',
                    'posts_per_page' => 1,
                ] );
                if ( empty( $stories->posts ) ) {
                    echo 'No published stories to test with.';
                    break;
                }
                $story = new Story( $stories->posts[0] );
                echo $this->get_email_template( 'follow-update', 'ac', [
                    'item' => $story,
                    'entities' => $story->get_entities_since_last_email_notification(),
                    'email_type' => $story->get_email_type(),
                    'shareable' => true,
                ] );
                break;

            default:
                echo '<img src="http://i.imgur.com/lnKvhQ7.jpg" alt="Invalid email type" title="dingus!"><br>';
                break;
        }
    }
}
