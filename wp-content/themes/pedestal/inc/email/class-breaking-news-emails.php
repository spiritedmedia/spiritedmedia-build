<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;

use Timber\Timber;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Posts\Post;

use Pedestal\Email\{
    Email,
    Email_Groups
};

class Breaking_News_Emails {

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
     * Hook into WordPress via actions
     */
    public function setup_actions() {
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ], 10, 2 );
        add_action( 'save_post', [ $this, 'action_save_post_maybe_send_email' ], 100 );
        add_action( 'pedestal_email_tester_breaking-news', [ $this, 'action_pedestal_email_tester' ] );
    }

    /**
     * Setup the Breaking News metabox
     *
     * @param string $post_type  The post type of the post being edited
     * @param WP_Post $post      A WordPress post object
     */
    public function action_add_meta_boxes( $post_type = '', $post ) {
        // If the post type doesn't support Breaking News updates then bail
        if ( ! post_type_supports( $post_type, 'breaking' ) ) {
            return;
        }

        // Don't show the Breaking News meta box if the post isn't published
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        if ( ! current_user_can( 'send_emails' ) ) {
            return;
        }

        add_meta_box( 'pedestal-breaking-news-notify-subscribers',
            'Notify Breaking News Subscribers',
            [ $this, 'handle_meta_box' ],
            $post_type,
            'side',
            'default'
        );
    }

    /**
     * Render the breaking news metabox
     *
     * @param  object $post WP_Post
     */
    public function handle_meta_box( $post ) {
        $post         = Post::get( (int) $post->ID );
        $sent_date    = $post->get_sent_date();
        $email_groups = Email_Groups::get_instance();

        $send_button_text = sprintf(
            'Send Breaking News To %d Subscribers',
            $email_groups->get_subscriber_count( 'Breaking News', 'Newsletters' )
        );

        $context = [
            'item'            => $post,
            'template'        => 'breaking-news',
            'message'         => '',
            'confirm_message' => '',
            'btn_send'        => get_submit_button(
                $send_button_text,
                'primary',
                'pedestal-breaking-news-notify-subscribers',
                $wrap = true
            ),
            'btn_send_test'   => get_submit_button(
                'Send Test Email',
                'secondary',
                'pedestal-breaking-news-send-test-email',
                $wrap = false
            ),
        ];

        if ( $sent_date ) {
            $sent_date          = get_date_from_gmt( date( 'Y-m-d H:i:s', $sent_date ), PEDESTAL_DATETIME_FORMAT );
            $sent_confirm       = sprintf( 'The breaking news email was sent on %s.',
                $sent_date
            );
            $context['message'] = wpautop( esc_html( $sent_confirm ) );
            // Breaking News was already sent, don't show the Send button
            $context['btn_send'] = '';
        }

        Timber::render( 'partials/admin/metabox-send-email-breaking-news.twig', $context );
    }

    /**
     * Action to check if we should send a breaking news email
     *
     * @param  integer $post_id ID of the post being edited
     */
    public function action_save_post_maybe_send_email( $post_id = 0 ) {
        // Determine if this is a test email
        $is_test_email = false;
        if ( ! empty( $_POST['pedestal-breaking-news-send-test-email'] ) ) {
            $is_test_email = true;
        }

        // If not a test email and the send breaking news button wasn't clicked then bail
        if (
            ! $is_test_email
            && empty( $_POST['pedestal-breaking-news-notify-subscribers'] )
        ) {
            return;
        }

        // If not a test email and the send breaking news confirmation isn't valid then bail
        if (
            ! $is_test_email
            && (
                empty( $_POST['confirm-send-email'] )
                || 'SEND BREAKING NEWS' !== strtoupper( $_POST['confirm-send-email'] )
            )
        ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $post = Post::get( (int) $post_id );
        $args = [];
        if ( $is_test_email ) {
            $args['test_email_addresses'] = Email::sanitize_test_email_addresses( $_POST['test-email-addresses'] );
        }
        $campaign_id = $this->send_email( $post, $args );
        if ( $campaign_id && ! $is_test_email ) {
            // Set the last sent email date
            $post->set_sent_flag( 'breaking-news' );
            $post->set_sent_date( time() );
            $post->set_meta( 'mailchimp_campaign_id', $campaign_id );
        }
    }

    /**
     * Send an email campaign to breaking news subscribers
     *
     * @param  Post $post   The entity we are notifing subscribers about
     * @param  array $args  Options
     * @return string       MailChimp campaign id if sent successfully
     */
    public function send_email( Post $post, $args ) {
        $html         = Email::get_email_template( 'breaking-news', 'mc', [
            'item'       => $post,
            'email_type' => 'Breaking News',
            'shareable'  => true,
        ] );
        $subject      = sprintf( 'BREAKING NEWS: %s', $post->get_title() );
        $sending_args = [
            'messages'       => [
                [
                    'html'    => $html,
                    'subject' => $subject,
                ],
            ],
            'groups'         => 'Breaking News',
            'group_category' => 'Newsletters',
            'email_type'     => 'Breaking News',
            'folder_name'    => 'Breaking/alerts',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        return Email::send_mailchimp_email( $sending_args );
    }

    /**
     * Respond to an email tester request
     */
    public function action_pedestal_email_tester() {
        $breaking_news = new \WP_Query( [
            'post_type'      => Types::get_post_types_by_supported_feature( 'breaking' ),
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
            die();
        }
        $post = Post::get( $breaking_news->posts[0]->ID );
        echo Email::get_email_template( 'breaking-news', 'mc', [
            'item'       => $post,
            'email_type' => 'Breaking News',
            'shareable'  => true,
        ] );
        die();
    }
}
