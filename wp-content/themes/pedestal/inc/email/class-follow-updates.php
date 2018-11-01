<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;
use Timber\Timber;

use Pedestal\Icons;
use Pedestal\Posts\Post;
use Pedestal\Posts\Clusters\{
    Cluster,
    Story
};
use Pedestal\Email\{
    Email,
    Email_Groups
};
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Utils\Utils;
use Pedestal\Objects\MailChimp;

class Follow_Updates {

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Setup various actions
     */
    public function setup_actions() {
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ], 10, 2 );
        add_action( 'save_post', [ $this, 'action_save_post_maybe_create_group' ], 10, 3 );
        add_action( 'save_post', [ $this, 'action_save_post_maybe_send_email' ], 100 );
        add_action( 'pedestal_email_tester_follow-update-story', [ $this, 'action_pedestal_email_tester' ] );
        add_action( 'post_updated', [ $this, 'action_post_updated_rename_mailchimp_group' ], 10, 3 );
    }

    /**
     * Setup the Follow Updates metabox
     *
     * @param string $post_type  The post type of the post being edited
     * @param WP_Post $post      A WordPress post object
     */
    public function action_add_meta_boxes( $post_type, $post ) {
        if ( ! Types::is_followable_post_type( $post_type ) ) {
            return;
        }
        if ( 'publish' !== $post->post_status ) {
            return;
        }
        $email_type = Types::get_post_type_labels( $post_type )['singular_name'];
        $email_template = sanitize_title( $email_type );

        add_meta_box( 'pedestal-cluster-notify-subscribers',
            esc_html__( 'Notify ' . $email_type . ' Subscribers', 'pedestal' ),
            [ $this, 'handle_meta_box' ],
            $post_type,
            'side',
            'default'
        );
    }

    /**
     * Handle the meta box to trigger an email send to cluster subscribers
     *
     * @param  object $post WP_Post
     */
    public function handle_meta_box( $post ) {
        $post_id = $post->ID;
        $cluster = Cluster::get( (int) $post_id );
        if ( ! Types::is_cluster( $cluster ) ) {
            return;
        }
        $type = $cluster->get_type();
        $entities = $cluster->get_unsent_entities( true );
        $entity_count = count( $entities );

        $last_sent = '';
        $last_sent_human_diff = 'N/A';
        $last_sent_date = $cluster->get_last_email_notification_date();
        if ( $last_sent_date ) {
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
                'Send Test Email',
                'secondary',
                'pedestal-cluster-send-test-email',
                $wrap = false,
                $attributes
            );
        }

        $send_button = '';
        $follower_label = 'Followers';
        $cluster_count = $cluster->get_subscriber_count();
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
            $wrap = true,
            $attributes
        );

        $context = [
            'entities'             => $entities,
            'entity_count'         => number_format( $entity_count ),
            'last_sent'            => $last_sent,
            'last_sent_human_diff' => $last_sent_human_diff,
            'btn_send_test'        => $test_button,
            'send_button'          => $send_button,
        ];
        Timber::render( 'partials/admin/metabox-send-email-cluster.twig', $context );
    }

    /**
     * Maybe create a new MailChimp group when a post is saved?
     *
     * @param  integer $post_id ID of the post being saved
     * @param  WP_Post  $post    WP_Post object being saved
     * @param  boolean $update  Whether the post being saved is an update or not
     */
    public function action_save_post_maybe_create_group( $post_id = 0, $post, $update = false ) {
        if ( 'publish' != $post->post_status ) {
            return;
        }
        if ( ! Types::is_followable_post_type( $post->post_type ) ) {
            return;
        }

        $cluster = Cluster::get( $post_id );
        $group = $cluster->get_mailchimp_group();
        // We already have a group, all is well
        if ( is_object( $group ) ) {
            return;
        }

        $mc = MailChimp::get_instance();
        $group_name = $cluster->get_title();
        $group_category = $cluster->get_mailchimp_group_category();
        $mc->add_group( $group_name, $group_category );

        // Flush the local group category cache
        $email_groups = Email_Groups::get_instance();
        $email_groups->delete_option( $group_category );
        $email_groups->get_groups( $group_category );
    }

    /**
     * Action to check if we should send a follow update email
     *
     * @param  integer $post_id ID of the post being edited
     */
    public function action_save_post_maybe_send_email( $post_id = 0 ) {
        if ( empty( $_POST['pedestal-cluster-notify-subscribers'] ) && empty( $_POST['pedestal-cluster-send-test-email'] ) ) {
            return;
        }

        $cluster = Cluster::get( (int) $post_id );
        $is_test_email = false;
        $args = [];
        if ( ! empty( $_POST['pedestal-cluster-send-test-email'] ) ) {
            $is_test_email = true;
            $args['test_email_addresses'] = Email::sanitize_test_email_addresses( $_POST['test-email-addresses'] );
        }
        $result = $this->send_email_to_group( $cluster, $args );
        if ( $result && ! $is_test_email ) {
            // Set the last sent email date
            $cluster->set_last_email_notification_date();
        }
    }

    /**
     * Send an email campaign to subscribers of a given cluster
     *
     * @param  Cluster $cluster  The cluster we are notifing subscribers about
     * @param  array $args       Options
     * @return boolean           Did the camapign send successfully?
     */
    public function send_email_to_group( $cluster, $args = [] ) {
        if ( ! Types::is_cluster( $cluster ) ) {
            return false;
        }
        $entities = $cluster->get_unsent_entities( true );
        if ( empty( $entities ) ) {
            // Nothing to send
            return false;
        }
        $body = Email::get_email_template( 'follow-update', 'mc', [
            'item'       => $cluster,
            'entities'   => $entities,
            'email_type' => $cluster->get_email_type(),
            'shareable'  => true,
        ] );
        $subject = sprintf( 'Update: %s', $cluster->get_title() );
        $sending_args = [
            'messages'       => [
                [
                    'html'    => $body,
                    'subject' => $subject,
                ],
            ],
            'groups'         => [ $cluster->get_mailchimp_group_id() ],
            'group_category' => $cluster->get_mailchimp_group_category(),
            'email_type'     => 'Follow Update',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        $sent = Email::send_mailchimp_email( $sending_args );
        if ( $sent ) {
            $expiration = Utils::get_fuzzy_expire_time( HOUR_IN_SECONDS / 2 );
            set_transient( 'pedestal_cluster_unsent_entities_count_' . $cluster->get_id(), 0, $expiration );
            $cluster->set_meta( 'unsent_entities_count', 0 );
        }
        return $sent;
    }

    /**
     * Handle request to /test-email/follow-update-story/
     */
    public function action_pedestal_email_tester() {
        $stories = new \WP_Query( [
            'post_type'      => 'pedestal_story',
            'posts_per_page' => 1,
        ] );
        if ( empty( $stories->posts ) ) {
            echo 'No published stories to test with.';
            die();
        }
        $story = Story::get( $stories->posts[0] );
        if ( ! Types::is_story( $story ) ) {
            echo 'Invalid story ID.';
            die();
        }
        $subject = sprintf( 'Update: %s', $story->get_title() );
        echo Email::get_email_template( 'follow-update', 'mc', [
            'item'       => $story,
            'subject'    => $subject,
            'entities'   => $story->get_unsent_entities( true ),
            'email_type' => $story->get_email_type(),
            'shareable'  => true,
        ] );
        die();
    }

    /**
     * Rename a Cluster's MailChimp group name if its title changes
     *
     * @param  int $post_id          ID of the post being saved
     * @param  WP Post $post_after   Post object after update
     * @param  WP Post $post_before  Post object before update
     */
    public function action_post_updated_rename_mailchimp_group( $post_id, $post_after, $post_before ) {
        if ( $post_after->post_title === $post_before->post_title ) {
            return;
        }

        if ( ! Types::is_followable_post_type( $post_after->post_type ) ) {
            return;
        }

        $cluster = Cluster::get( $post_id );
        $mc = MailChimp::get_instance();
        $new_name = $post_after->post_title;
        $old_name = $post_before->post_title;
        $group_category = $cluster->get_mailchimp_group_category();
        $mc->edit_group_name( $new_name, $old_name, $group_category );
    }

    /**
     * Get a signup form for follow updates
     *
     * @param  array   $args       Arguments to manipulate the signup form
     * @param  integer $cluster_id The post ID of the cluster readers are signing up for
     * @return string              HTML markup of the signup form or nothing
     *                             if the form can't be rendered
     */
    public static function get_signup_form( $args = [], $cluster_id = 0 ) {
        if ( ! $cluster_id ) {
            $cluster_id = get_the_ID();
        }
        $cluster = Post::get( $cluster_id );
        if ( ! Types::is_cluster( $cluster ) ) {
            return;
        }
        $defaults = [
            'action_url'      => get_site_url() . '/subscribe-to-email-group/',
            'nonce'           => wp_create_nonce( PEDESTAL_THEME_NAME ),

            'ga_category'     => 'cluster-prompt',
            'ga_action'       => 'subscribe',

            'input_icon_name' => 'envelope-o',
            'input_icon'      => '',

            'title'           => self::get_cta_text( $cluster_id ),
            'button_text'     => self::get_submit_button_text( $cluster_id ),

            'group_ids'       => [
                $cluster->get_mailchimp_group_id(),
            ],
            'group_category'  => $cluster->get_mailchimp_group_category(),
            'cluster_id'      => $cluster->get_id(),
        ];
        $site_defaults = apply_filters( 'pedestal_cluster_signup_form_args', [] );
        $defaults      = wp_parse_args( $site_defaults, $defaults );
        $context       = wp_parse_args( $args, $defaults );

        // Can't show a sign up form if it can't be associated with a group
        $context['group_ids'] = array_filter( $context['group_ids'], 'is_string' );
        if ( empty( $context['group_ids'] ) ) {
            return;
        }

        // Sanity checks to ensure we have consistent markup
        $context['title'] = wpautop( $context['title'] );

        if ( empty( $context['input_icon'] ) && ! empty( $context['input_icon_name'] ) ) {
            $context['input_icon'] = Icons::get_icon( $context['input_icon_name'], 'signup-email__input-icon input-group__addon' );
        }

        ob_start();
        Timber::render( 'views/forms/cluster-signup-form.twig', $context );
        return ob_get_clean();
    }

    /**
     * Helper method to get the text for the signup form submit button
     *
     * @param  integer $cluster_id Post ID of the cluster
     * @return string              Default or customized text
     */
    public static function get_submit_button_text( $cluster_id = 0 ) {
        $default_text = 'Get Alerts';
        $cluster = Post::get( $cluster_id );
        if ( ! Types::is_cluster( $cluster ) ) {
            return $default_text;
        }
        $custom_text = $cluster->get_fm_field( 'signup_form_settings', 'button_text' );
        return $custom_text ?: $default_text;
    }

    /**
     * Helper method to get the call to action text for the signup form
     *
     * @param  integer $cluster_id Post ID of the cluster
     * @return string              Default or customized text
     */
    public static function get_cta_text( $cluster_id = 0 ) {
        $default_text = 'Get email notifications';
        $cluster = Post::get( $cluster_id );
        if ( ! Types::is_cluster( $cluster ) ) {
            return $default_text;
        }
        $default_text = "Get email notifications whenever we write about <strong>{$cluster->get_the_title()}</strong>";
        $custom_text = $cluster->get_fm_field( 'signup_form_settings', 'cta_text' );
        return $custom_text ?: $default_text;
    }
}
