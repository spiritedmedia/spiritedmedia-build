<?php

namespace Pedestal\Admin;

use function Pedestal\Pedestal;

use Timber\Timber;

use \Pedestal\Utils\Utils;

use \Pedestal\Objects\User;

/**
 * Encapsulates customizations for the WordPress admin
 */
class Bulk_Subscribe {

    private static $bulk_subscribe_page_title = 'Bulk Subscribe Email Addresses to Newsletter and Breaking News emails';

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Bulk_Subscribe;
            self::$instance->load();
        }
        return self::$instance;

    }

    /**
     * Load code for the admin
     */
    private function load() {
        $this->setup_actions();
    }

    /**
     * Set up admin filters
     */
    private function setup_actions() {

        add_action( 'admin_menu', function() {
            add_submenu_page( 'users.php',
                esc_html__( self::$bulk_subscribe_page_title, 'pedestal' ),
                esc_html__( 'Bulk Subscribe', 'pedestal' ),
                'manage_options',
                'pedestal_bulk_subscribe',
                [ $this, 'handle_bulk_subscribe_page' ]
            );
        } );

    }

    /**
     * Handle the rendering of the bulk subscribe options page
     */
    public function handle_bulk_subscribe_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page. 23 skidoo!' ) );
        }

        $context = [
            'page_title'         => esc_html__( self::$bulk_subscribe_page_title, 'pedestal' ),
            'form_nonce'         => wp_nonce_field( 'pedestal-users-bulk-subscribe' ),
            'submit_button_text' => esc_html__( 'Subscribe Addresses', 'pedestal' ),
        ];

        $messages = [];
        if ( ! empty( $_POST['bulk_subscribe_submit'] ) ) {
            $emails = [];
            $email = $_POST['email'];

            if ( ! empty( $email['bulk_emails'] ) ) {
                $emails = explode( PHP_EOL, $email['bulk_emails'] );
                $emails = array_filter( array_map( 'trim', $emails ) );
                $emails = array_map( 'strtolower', $emails );
            }

            foreach ( $emails as $email ) {
                $newsletter_subscribe = Pedestal()->subscriptions->subscribe_daily_newsletter( $email );
                if ( is_wp_error( $newsletter_subscribe ) ) {
                    $messages[] = $this->handle_bulk_subscribe_messages( [
                        'email'   => $email,
                        'error'   => $newsletter_subscribe,
                        'updated' => false,
                    ] );
                } elseif ( true === $newsletter_subscribe ) {
                    $messages[] = $this->handle_bulk_subscribe_messages( [
                        'email'   => $email,
                        'updated' => true,
                        'action' => 'subscribe',
                    ] );
                } else {
                    $messages[] = $this->handle_bulk_subscribe_messages( [
                        'email'   => $email,
                        'updated' => false,
                        'action' => 'subscribe',
                    ] );
                }
            }

            $context['messages'] = implode( '', $messages );
        }

        Timber::render( 'partials/admin/users-bulk-subscribe.twig', $context );
    }

    /**
     * Handle feedback messages for the bulk subscribe form
     */
    private function handle_bulk_subscribe_messages( $options ) {
        $send_msg = function( $classes, $msg ) {
            return sprintf( '<div id="message" class="%s">%s</div>', $classes, $msg );
        };

        $msg = '';
        $classes = 'notice fade ';

        if ( empty( $options['email'] ) ) {
            $classes .= 'error';
            $msg = esc_html__( 'Error!', 'pedestal' );
            return $send_msg( $classes, $msg );
        }

        $options['email'] = '<b>' . $options['email'] . '</b>';

        if ( $options['updated'] ) {
            $classes .= 'updated';
            switch ( $options['action'] ) {
                case 'subscribe':
                    $msg = sprintf( esc_html__( '%s subscribed to Daily Newsletter and Breaking News emails' ), $options['email'] );
                    break;
                default:
                    $msg = sprintf( esc_html__( 'Options saved' ) );
                    break;
            }
        } else {
            $classes .= 'error';
            if ( ! empty( $options['error'] ) && is_wp_error( $options['error'] ) ) {
                foreach ( $options['error']->get_error_messages() as $error ) {
                    $msg .= $error . '<br />';
                }
            } else {
                switch ( $options['action'] ) {
                    case 'subscribe':
                        $msg = sprintf( esc_html__( 'Error subscribing %s to Daily Newsletter and Breaking News emails.' ), $options['email'] );
                        break;
                    default:
                        $msg = sprintf( esc_html__( 'Error!' ) );
                        break;
                }
            }
        }

        return $send_msg( $classes, $msg );
    }
}
