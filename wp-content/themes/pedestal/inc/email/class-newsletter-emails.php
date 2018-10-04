<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;

use Timber\Timber;

use Pedestal\Icons;
use Pedestal\Posts\{
    Post,
    Newsletter
};

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Objects\MailChimp;

use Pedestal\Email\{
    Email,
    Email_Groups
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
        add_action( 'save_post', [ $this, 'action_save_post_maybe_send_email' ], 100 );
        add_action( 'pedestal_email_tester_newsletter', [ $this, 'action_pedestal_email_tester' ] );

        // Render the newsletter signup form after the 3rd item in a stream
        add_action( 'pedestal_after_stream_item_3', function() {
            $ignore_post_types = Types::get_original_post_types();
            $ignore_post_types[] = 'pedestal_story';
            if ( is_singular( $ignore_post_types ) ) {
                return;
            }
            $signup_form = self::get_signup_form();
            echo '<div class="stream-item stream-item--signup-email signup-email--daily">';
            echo $signup_form;
            echo '</div>';
        } );

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
        $post = Post::get( (int) $post->ID );
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
    public function action_save_post_maybe_send_email( $post_id = 0 ) {
        $post_type = get_post_type( $post_id );
        if ( 'pedestal_newsletter' !== $post_type ) {
            return;
        }
        $newsletter = Newsletter::get( (int) $post_id );
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

        $campaign_id = $this->send_email( $newsletter, $args );
        if ( $campaign_id && ! $is_test_email ) {
            // Set the last sent email date
            $newsletter->set_sent_flag( 'newsletter' );
            $newsletter->set_sent_date( time() );
            $newsletter->set_meta( 'mailchimp_campaign_id', $campaign_id );
        }
    }

    /**
     * Send an email campaign to newsletter subscribers
     *
     * @param  Newsletter $newsletter  The newsletter we are notifing subscribers about
     * @param  Array $args             Options
     * @return Boolean                 Did the camapign send successfully?
     */
    public function send_email( $newsletter, $args ) {
        $newsletter_post = get_post( $newsletter->get_id() );
        $parent_newsletter = $newsletter_post;
        while ( 0 != $parent_newsletter->post_parent ) {
            $parent_newsletter = get_post( $parent_newsletter->post_parent );
        }
        $query_args = [
            'post_type'      => 'pedestal_newsletter',
            'post_parent'    => $parent_newsletter->ID,
            'posts_per_page' => 5,
            'post_status'    => [ 'draft', 'publish', 'future' ],
        ];
        $message_posts = new \WP_Query( $query_args );
        $message_posts = array_merge( [ $parent_newsletter ], $message_posts->posts );
        $messages = [];
        foreach ( $message_posts as $message_post ) {
            $newsletter = Newsletter::get( $message_post );
            $body = Email::get_email_template( 'newsletter', 'mc', [
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
        $sending_args = [
            'messages'       => $messages,
            'groups'         => 'Daily Newsletter',
            'group_category' => 'Newsletters',
            'email_type'     => 'Daily Newsletter',
        ];
        $sending_args = wp_parse_args( $sending_args, $args );
        return Email::send_mailchimp_email( $sending_args );
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
        $newsletter = Newsletter::get( $newsletters->posts[0] );
        echo Email::get_email_template( 'newsletter', 'mc', [
            'item'       => $newsletter,
            'email_type' => 'Daily',
            'shareable'  => true,
        ] );
        die();
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

    /**
     * Get a signup form for newsletters
     *
     * @param  array   $args       Arguments to manipulate the signup form
     * @return string              HTML markup of the signup form or nothing
     *                             if the form can't be rendered
     */
    public static function get_signup_form( $args = [] ) {
        $email_groups = Email_Groups::get_instance();
        $defaults = [
            'action_url'           => get_site_url() . '/subscribe-to-email-group/',
            'ga_category'          => 'inline-prompt',
            'ga_action'            => 'subscribe',
            'icon'                 => Icons::get_icon( 'envelope-slant' ),
            'input_icon_name'      => 'envelope-o',
            'input_icon'           => '',
            'success_icon'         => Icons::get_icon( 'check' ),
            'name'                 => PEDESTAL_BLOG_NAME,
            'title'                => '',
            'body'                 => 'default',
            'name'                 => PEDESTAL_BLOG_NAME,
            'sender_email_address' => PEDESTAL_EMAIL_NEWS,
            'send_time'            => '7:00 a.m.',
            'group_ids'            => [
                $email_groups->get_newsletter_group_id( 'Daily Newsletter' ),
                $email_groups->get_newsletter_group_id( 'Breaking News' ),
            ],
            'group_category'       => 'Newsletters',
        ];
        $site_defaults = apply_filters( 'pedestal_newsletter_signup_form_args', [] );
        $defaults      = wp_parse_args( $site_defaults, $defaults );
        $context       = wp_parse_args( $args, $defaults );

        // Can't show a sign up form if it can't be associated with a group
        $context['group_ids'] = array_filter( $context['group_ids'], 'is_string' );
        if ( empty( $context['group_ids'] ) ) {
            return;
        }

        if ( 'default' == $context['body'] ) {
            $latest_newsletter_url = Newsletter::get_latest_newsletter_link();
            $default_body = '
                <ul class="signup-email__details">
                    <li>Top news highlights and can\'t-miss ' . PEDESTAL_CITY_NICKNAME . ' stories</li>
                    <li>Upcoming events and activities in your area for you and your family</li>
            ';
            if ( $latest_newsletter_url ) {
                $default_body .= '
                <li>
                    Check out a <a href="' . esc_url( $latest_newsletter_url ) . '">sample from today\'s newsletter</a>
                </li>
                ';
            }
            $default_body .= '</ul>';
            $context['body'] = $default_body;
        }

        if ( empty( $context['input_icon'] ) && ! empty( $context['input_icon_name'] ) ) {
            $context['input_icon'] = Icons::get_icon( $context['input_icon_name'], 'signup-email__input-icon input-group__addon' );
        }

        ob_start();
        Timber::render( 'views/forms/newsletter-signup-form.twig', $context );
        return ob_get_clean();
    }
}
