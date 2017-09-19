<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;

use Timber\Timber;

use Pedestal\Posts\{
    Post,
    Newsletter
};

use Pedestal\Email\{
    Email,
    Email_Lists
};

class Newsletter_Emails {

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
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ], 10, 2 );
        add_action( 'save_post', [ $this, 'action_save_post_send_email' ], 100 );
        add_action( 'pedestal_email_tester_newsletter', [ $this, 'action_pedestal_email_tester' ] );
        add_action( 'admin_footer', function() {
            $post = get_post();
            if ( ! $post instanceof \WP_Post ) {
                return;
            }
            if ( 'pedestal_newsletter' !== $post->post_type || 'publish' === $post->post_status ) {
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
     * Hook in to various filters
     */
    public function setup_filters() {
        add_filter( 'gettext', [ $this, 'filter_gettext_publish_button' ], 10, 2 );
    }

    /**
     * Setup the Newsletter metabox
     *
     * @param string $post_type  The post type of the post being edited
     * @param WP_Post $post      A WordPress post object
     */
    public function action_add_meta_boxes( $post_type = '', $post ) {
        if ( 'pedestal_newsletter' !== $post_type || ! current_user_can( 'send_emails' ) ) {
            return;
        }

        add_meta_box( 'pedestal-newsletter-notify-subscribers',
            'Notify Newsletter Subscribers',
            [ $this, 'handle_meta_box' ],
            $post_type,
            'side',
            'default'
        );
    }

    /**
     * Handle the meta box to trigger a newsletter email send to subscribers
     *
     * @param  object $post WP_Post
     */
    public function handle_meta_box( $post ) {
        $post = Post::get_by_post_id( (int) $post->ID );
        $sent_date = $post->get_sent_date();

        $context = [
            'item'            => $post,
            'template'        => 'newsletter',
            'disabled'        => false,
            'message'         => '',
            'confirm_message' => '',
            'btn_send_test'   => get_submit_button(
                'Send Test Email',
                'secondary',
                'pedestal-newsletter-send-test-email',
                $wrap = false
            ),
        ];

        if ( $sent_date ) {
            $sent_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $sent_date ), PEDESTAL_DATETIME_FORMAT );
            $sent_confirm = sprintf( 'The newsletter email was sent on %s.',
                $sent_date
            );
            $context['message'] = esc_html__( $sent_confirm, 'pedestal' );
        }

        Timber::render( 'partials/admin/metabox-send-email-primary.twig', $context );
    }

    /**
     * Action to check if we should send a newsletter email
     *
     * @param  integer $post_id ID of the post being edited
     */
    public function action_save_post_send_email( $post_id = 0 ) {
        $post_type = get_post_type( $post_id );
        if ( 'pedestal_newsletter' !== $post_type ) {
            return;
        }
        $newsletter = Newsletter::get_by_post_id( (int) $post_id );
        $is_test_email = false;
        $args = [];
        if ( ! empty( $_POST['pedestal-newsletter-send-test-email'] ) ) {
            $is_test_email = true;
            $args['test_email_addresses'] = Email::sanitize_test_email_addresses( $_POST['test-email-addresses'] );
        }

        // If the newsletter isn't being published, then bail...
        if ( 'publish' !== $newsletter->get_status() && ! $is_test_email ) {
            return;
        }

        // If the newsletter was already sent then bail...
        if ( $newsletter->get_sent_date() && ! $is_test_email ) {
            return;
        }

        // If this newsletter is being sent as part of a test
        // then set it's status to draft for ActiveCampaign
        if ( $newsletter->get_meta( 'newsletter_is_test' ) ) {
            $args['status'] = 0;
        }

        $result = $this->send_email_to_list( $newsletter, $args );
        if ( $result && ! $is_test_email ) {
            // Set the last sent email date
            $newsletter->set_sent_flag( 'newsletter' );
            $newsletter->set_sent_date( time() );
            if ( isset( $result->id ) ) {
                $newsletter->set_meta( 'activecampaign_id', $result->id );
            }
            if ( isset( $result->message_ids ) ) {
                $newsletter->set_meta( 'activecampaign_message_ids', $result->message_ids );
            }
        }
    }

    /**
     * Send an email campaign to newsletter subscribers
     *
     * @param  Newsletter $newsletter  The newsletter we are notifing subscribers about
     * @param  Array $args             Options
     * @return Boolean                 Did the camapign send successfully?
     */
    public function send_email_to_list( $newsletter, $args ) {
        $newsletter_post = get_post( $newsletter->get_id() );
        $parent_newsletter = $newsletter_post;
        while ( 0 != $parent_newsletter->post_parent ) {
            $parent_newsletter = get_post( $parent_newsletter->post_parent );
        }
        $query_args = [
            'post_type' => 'pedestal_newsletter',
            'post_parent' => $parent_newsletter->ID,
            'posts_per_page' => 5,
            'post_status' => [ 'draft', 'publish', 'future' ],
        ];
        $message_posts = new \WP_Query( $query_args );
        $message_posts = array_merge( [ $parent_newsletter ], $message_posts->posts );
        $messages = [];
        foreach ( $message_posts as $message_post ) {
            $newsletter = new Newsletter( $message_post );
            $body = Email::get_email_template( 'newsletter', 'ac', [
                'item'       => $newsletter,
                'email_type' => 'Daily',
                'shareable'  => true,
            ] );
            $subject = $newsletter->get_title();
            $messages[] = [
                'html'    => $body,
                'subject' => $subject,
            ];
        }
        $email_lists = Email_Lists::get_instance();
        $daily_newsletter_id = $email_lists->get_newsletter_list_id( 'Daily Newsletter' );
        $sending_args = [
            'messages'   => $messages,
            'list'       => $daily_newsletter_id,
            'email_type' => 'Daily Newsletter',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        return Email::send_email( $sending_args );
    }

    /**
     * Handle request to /test-email/newsletter/
     */
    public function action_pedestal_email_tester() {
        $newsletters = new \WP_Query( [
            'post_type'      => 'pedestal_newsletter',
            'posts_per_page' => 1,
        ] );
        if ( empty( $newsletters->posts ) ) {
            echo 'No published newsletters to test with.';
            die();
        }
        $newsletter = new Newsletter( $newsletters->posts[0] );
        echo Email::get_email_template( 'newsletter', 'ac', [
            'item' => $newsletter,
            'email_type' => 'Daily',
            'shareable' => true,
        ] );
        die();
    }

    /**
     * Get the number of users subscribed to the Daily Newsletter list
     *
     * @return int
     */
    public function get_daily_newsletter_subscriber_count() {
        $email_lists = Email_Lists::get_instance();
        $list_id = $email_lists->get_newsletter_list_id( 'Daily Newsletter' );
        return Email::get_subscriber_count( $list_id );
    }

    /**
     * Filter the text of the post Publish button
     * @param  string $translation Text that may be translated
     * @param  string $text        Original text
     * @return string              Filtered translated text
     */
    public function filter_gettext_publish_button( $translation = '', $text = '' ) {
        if ( 'Publish' !== $text ) {
            return $translation;
        }

        // We need to account for the var postL10n JavaScript variable translation
        // as well and `get_post_type()` returns null during that context.
        $post_type = get_post_type();
        if ( ! $post_type && isset( $_GET['post'] ) ) {
            $post_type = get_post_type( $_GET['post'] );
        }
        if ( ! $post_type && isset( $_GET['post_type'] ) ) {
            $post_type = $_GET['post_type'];
        }
        if ( 'pedestal_newsletter' !== $post_type ) {
            return $translation;
        }

        $new_text = 'Send Newsletter';
        // If the trash is disabled we need to use a shorter label
        // 'Move to Trash' changes to 'Delete Permanently' and there is less space
        if ( ! EMPTY_TRASH_DAYS ) {
            $new_text = 'Send';
        }

        return $new_text;
    }
}
