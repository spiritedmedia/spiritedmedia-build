<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;
use Timber\Timber;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\{
    Post,
    Newsletter
};
use Pedestal\Posts\Clusters\Cluster;
use Pedestal\Email\Follow_Updates;
use Pedestal\Objects\MailChimp;
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
        add_action( 'pedestal_email_tester_all', [ $this, 'action_pedestal_email_tester_all' ] );
    }

    /**
     * Hook in to various filters
     */
    public function setup_filters() {
        add_filter( 'query_vars', function( $query_vars ) {
            $query_vars[] = 'pedestal-test-email';
            $query_vars[] = 'pedestal-subscribe-to-email-group';
            return $query_vars;
        });
    }

    /**
     * Register rewrite rules
     */
    public function action_init_register_rewrites() {
        add_rewrite_rule( 'subscribe-to-email-group/?$', 'index.php?pedestal-subscribe-to-email-group=1', 'top' );
        add_rewrite_rule( 'test-email/?$', 'index.php?pedestal-test-email=all', 'top' );
        add_rewrite_rule( 'test-email/([^/]+)/?$', 'index.php?pedestal-test-email=$matches[1]', 'top' );
    }

    /**
     * Handle various requests from rewrite rules
     */
    public function action_template_redirect() {
        if ( '1' == get_query_var( 'pedestal-subscribe-to-email-group' ) ) {
            $this->handle_subscribe_to_email_group();
            if ( isset( $_REQUEST['ajax-request'] ) && 1 == $_REQUEST['ajax-request'] ) {
                // It's an AJAX request so returning a blank page is more performant here.
                exit;
            }

            $context = Timber::get_context();
            Timber::render( 'emails/pages/confirm-subscription.twig', $context );
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
        ];
        echo $this->get_email_template( 'tests-index', 'ac', [
            'items' => $templates,
            'shareable' => false,
        ] );

        die();
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
     * Handles subscribing an email address to a MailChimp group
     */
    private function handle_subscribe_to_email_group() {
        // Perform honeypot check to weed out bots
        $this->handle_honeypot_check();

        // Do we have all the bits of info we need?
        if ( empty( $_REQUEST['email_address'] )
            || empty( $_POST['group-ids'] )
            || empty( $_POST['group-category'] )
            || empty( $_POST['signup-source'] )
        ) {
            status_header( 400 );
            echo 'The form is missing information. Please reload the page and try again.';
            exit;
        }

        if ( empty( $_POST['signup-form-nonce'] ) || ! wp_verify_nonce( $_POST['signup-form-nonce'], PEDESTAL_THEME_NAME ) ) {
            // The request could not be completed due to a conflict with the current state of the target resource
            status_header( 409 );
            echo 'The form has expired. Please reload the page and try again.';
            exit;
        }

        $mc = MailChimp::get_instance();
        if ( ! empty( $_POST['group-ids'] ) ) {
            $group_ids = $_POST['group-ids'];
        }
        if ( ! is_array( $group_ids ) ) {
            $group_ids = [ $group_ids ];
        }
        $group_category = sanitize_text_field( $_POST['group-category'] );

        $signup_source = '';
        if ( ! empty( $_POST['signup-source'] ) ) {
            $signup_source = sanitize_text_field( $_POST['signup-source'] );
        }

        $email = sanitize_email( $_REQUEST['email_address'] );
        // Subscribe the email subscriber to the groups
        $args = [
            'groups'         => $group_ids,
            'group_category' => $group_category,
            'signup_source'  => $signup_source,
        ];
        return $mc->add_contact_to_groups( $email, $args );
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
     * Handle sending the email campaign via MailChimp
     *
     * @param  array $args  Details about the campaign
     * @return boolean      Did the camapign send successfully?
     */
    public static function send_mailchimp_email( $args = [] ) {
        $default_args = [
            'messages'              => false,
            'groups'                => [],
            'group_category'        => false,
            'test_email_addresses'  => [],
            'email_type'            => 'Unknown', // What type of email campaign is being sent? Newsletter, Breaking News, Folow Update etc.
        ];
        $args = wp_parse_args( $args, $default_args );

        if ( ! is_array( $args['groups'] ) ) {
            $args['groups'] = [ $args['groups'] ];
        }

        // Sanity check
        if ( empty( $args['groups'] ) || empty( $args['group_category'] ) ) {
            // If we don't have the group info to send the email to then we can't proceed
            return false;
        }
        if ( ! $args['messages'] || ! is_array( $args['messages'] ) ) {
            // Missing one or more key component for sending out an email, aren't we now?
            return false;
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

        $mc = MailChimp::get_instance();
        $the_message = $args['messages'][0];
        $mc_args = [
            'groups'         => $args['groups'],
            'group_category' => $args['group_category'],
            'message'        => $the_message['html'],
            'subject_line'   => $the_message['subject'],

        ];
        if ( ! empty( $args['folder_name'] ) ) {
            $mc_args['folder_name'] = $args['folder_name'];
        }
        $campaign_id = $mc->send_campaign( $mc_args );
        do_action( 'pedestal_sent_email_campaign', $args, $mc_args );
        return $campaign_id;
    }

    /**
     * Handle sanitizing email addresses beforing sending a test email
     *
     * @param  string|array $addresses  Comma separated list of email addresses
     * @return array                    Sanitized email addresses
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
