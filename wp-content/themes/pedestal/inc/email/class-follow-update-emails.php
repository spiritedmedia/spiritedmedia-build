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
use Pedestal\Email\Email;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Objects\ActiveCampaign;
use Pedestal\Utils\Utils;

class Follow_Update_Emails {

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
     * Setup various actions
     */
    public function setup_actions() {
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ], 10, 2 );
        add_action( 'save_post', [ $this, 'action_save_post' ], 100 );
        add_action( 'pedestal_email_tester_follow-update-story', [ $this, 'action_pedestal_email_tester' ] );
        add_action( 'post_updated', [ $this, 'action_post_updated_activecampaign_list' ], 10, 3 );
    }

    /**
     * Setup various filters
     */
    public function setup_filters() {
        add_filter( 'pedestal_cron_events', [ $this, 'filter_pedestal_cron_events' ] );
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
     * Send an email campaign to subscribers of a given cluster
     *
     * @param  Cluster $cluster  The cluster we are notifing subscribers about
     * @param  array $args       Options
     * @return boolean           Did the camapign send successfully?
     */
    public function send_email_to_list( $cluster, $args = [] ) {
        if ( ! Types::is_cluster( $cluster ) ) {
            return false;
        }
        $list_id = $cluster->get_activecampaign_list_id();
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
        $sent = Email::send_activecampaign_email( $sending_args );
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
        $cluster = Cluster::get( $post_id );
        $list_id = $cluster->get_activecampaign_list_id();
        $new_name = $cluster->get_activecampaign_list_name();

        $activecampaign = ActiveCampaign::get_instance();
        $response = $activecampaign->edit_list( $list_id, [
            'name' => $new_name,
        ] );
    }

    /**
     * Setup cron event to refresh subscriber counts
     *
     * @param  array  $events Cron events we want to register
     * @return array          Cron events we want to register
     */
    public function filter_pedestal_cron_events( $events = [] ) {
        $events['refresh_subscriber_count'] = [
            'timestamp'  => date( 'U', mktime( date( 'H' ) + 1, 5, 0 ) ), // Next top of the hour + 5 minutes
            'recurrence' => 'hourly',
            'callback'   => [ $this, 'handle_refresh_subscriber_count' ],
        ];
        return $events;
    }

    /**
     * Get a Cluster object from a given ActiveCampaign List ID
     * @param  array $list_ids  One or more ActiveCampaign List IDs
     * @return array            Array of Cluster objects
     */
    public static function get_clusters_from_list_ids( $list_ids = [] ) {
        $output = [];
        if ( is_numeric( $list_ids ) ) {
            $list_ids = [ $list_ids ];
        }
        $list_ids = array_map( 'intval', $list_ids );
        $args = [
            'post_type'     => 'any',
            'meta_key'      => 'activecampaign-list-id',
            'meta_value'    => $list_ids,
            'fields'        => 'ids',
            'no_found_rows' => true,
        ];
        $meta_query = new \WP_Query( $args );
        $post_ids = $meta_query->posts;
        if ( empty( $post_ids ) ) {
            return $output;
        }
        foreach ( $post_ids as $post_id ) {
            $cluster = Cluster::get( $post_id );
            if ( Types::is_cluster( $cluster ) ) {
                $output[] = $cluster;
            }
        }
        return $output;
    }

    /**
     * Refresh stored subscriber count for email lists
     *
     * Refreshes the 25 least recently updated stories
     */
    public function handle_refresh_subscriber_count() {
        $query = new \WP_Query( [
            'meta_key'       => 'subscriber_count_last_updated',
            'order'          => 'ASC',
            'orderby'        => 'meta_value_num',
            'post_type'      => 'pedestal_story',
            'posts_per_page' => 25,
        ] );
        self::refresh_subscriber_counts( $query );
    }

    /**
     * Refresh subscriber counts for clusters
     *
     * @param  array|\WP_Query  $posts  Array of IDs or \WP_Query
     * @return array Associative array of cluster IDs and new subscriber counts
     */
    public static function refresh_subscriber_counts( $posts ) {
        $result = [];

        if ( $posts instanceof \WP_Query ) {
            $posts = Post::get_posts_from_query( $posts );
        } elseif ( is_array( $posts ) && is_numeric( $posts[0] ) ) {
            $posts = Post::get_posts_from_ids( $posts );
        } else {
            return $result;
        }

        foreach ( $posts as $ped_post ) {
            if ( ! Types::is_cluster( $ped_post ) ) {
                continue;
            }
            // Get fresh values by deleting the current values and refetching the subscriber count
            $ped_post->delete_subscriber_count();
            $result[ $ped_post->get_id() ] = $ped_post->get_subscriber_count();
        }

        return $result;
    }
}
