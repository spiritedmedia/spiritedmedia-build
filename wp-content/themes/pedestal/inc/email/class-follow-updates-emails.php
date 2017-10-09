<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;
use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Posts\Clusters\{
    Cluster,
    Story
};
use Pedestal\Posts\Clusters\Geospaces\Localities\Neighborhood;
use Pedestal\Email\{
    Email,
    Email_Lists
};
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Utils\Utils;

class Follow_Updates_Emails {

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
        add_action( 'save_post', [ $this, 'action_save_post' ], 100 );
        add_action( 'pedestal_email_tester_follow-update-story', [ $this, 'action_pedestal_email_tester' ] );
    }

    /**
     * Setup the Follow Updates metabox
     *
     * @param string $post_type  The post type of the post being edited
     * @param WP_Post $post      A WordPress post object
     */
    public function action_add_meta_boxes( $post_type, $post ) {
        if ( ! Types::is_cluster( $post_type ) ) {
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
        $force = true;
        $cluster_count = $cluster->get_following_users_count( $force );
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
     * Action to check if we should send a follow update email
     *
     * @param  integer $post_id ID of the post being edited
     */
    public function action_save_post( $post_id = 0 ) {
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
        $result = $this->send_email_to_list( $cluster, $args );
        if ( $result && ! $is_test_email ) {
            // Set the last sent email date
            $cluster->set_last_email_notification_date();
        }
    }

    /**
     * Send an email campaign to those following a given cluster
     *
     * @param  Cluster $cluster  The cluster we are notifing followers about
     * @param  Array $args       Options
     * @return Boolean           Did the camapign send successfully?
     */
    public function send_email_to_list( $cluster, $args = [] ) {
        if ( ! Types::is_cluster( $cluster ) ) {
            return false;
        }
        $cluster_id = $cluster->get_id();
        $list_id = Email_Lists::get_list_ids_from_cluster( $cluster_id );
        $body = Email::get_email_template( 'follow-update', 'ac', [
            'item'       => $cluster,
            'entities'   => $cluster->get_unsent_entities( true ),
            'email_type' => $cluster->get_email_type(),
            'shareable'  => true,
        ] );
        $subject = sprintf( 'Update: %s', $cluster->get_title() );
        $sending_args = [
            'messages'   => [
                [
                    'html'    => $body,
                    'subject' => $subject,
                ],
            ],
            'list'       => $list_id,
            'email_type' => 'Follow Update',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        $sent = Email::send_email( $sending_args );
        if ( $sent ) {
            $expiration = Utils::get_fuzzy_expire_time( HOUR_IN_SECONDS / 2 );
            set_transient( 'pedestal_cluster_unsent_entities_count_' . $cluster_id, 0, $expiration );
            update_post_meta( $cluster_id, 'unsent_entities_count', 0 );
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
        if ( Types::is_story( $story ) ) {
            echo Email::get_email_template( 'follow-update', 'ac', [
                'item'       => $story,
                'entities'   => $story->get_unsent_entities( true ),
                'email_type' => $story->get_email_type(),
                'shareable'  => true,
            ] );
        }
        die();
    }
}
