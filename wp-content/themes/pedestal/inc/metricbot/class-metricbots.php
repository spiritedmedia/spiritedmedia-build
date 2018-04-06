<?php
namespace Pedestal\MetricBot;

use Timber\Timber;

use Pedestal\Objects\Notifications;

class MetricBots {

    private $menu_slug = 'test-metricbots';

    /**
     * Get an instance of this class
     */
    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook in to WordPress via actions
     */
    public function setup_actions() {
        add_action( 'admin_menu', [ $this, 'action_admin_menu' ] );
    }

    /**
     * Hook in to WordPress via filters
     */
    public function setup_filters() {

    }

    public function action_admin_menu() {
        $page_title = 'MetricBots Tester Page';
        $menu_title = 'Test MetricBots';
        $capability = 'update_plugins';
        add_submenu_page( 'tools.php', $page_title, $menu_title, $capability, $this->menu_slug, [ $this, 'render_metricbot_test_page' ] );
    }

    public function render_metricbot_test_page() {
        $selected_bot = '';
        if ( ! empty( $_REQUEST['bot'] ) ) {
            if ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], $this->menu_slug ) ) {
                $selected_bot = $_REQUEST['bot'];
            }
        }

        $slack_channel = '';
        if ( ! empty( $_REQUEST['slack-channel'] ) ) {
            $slack_channel = $_REQUEST['slack-channel'];
        }

        $message = '';
        $data = (object) [];
        switch ( $selected_bot ) {
            case 'yesterdays-email':
                $message = Yesterdays_Email_Metric::get_instance()->get_message();
                $data = Yesterdays_Email_Metric::get_instance()->get_data();
                break;
            case 'weekly-traffic':
                $message = Weekly_Traffic_Metric::get_instance()->get_message();
                $data = Weekly_Traffic_Metric::get_instance()->get_data();
                break;
            case 'newsletter-signups-by-page':
                $message = Newsletter_Signups_By_Page_Metric::get_instance()->get_message();
                $data = Newsletter_Signups_By_Page_Metric::get_instance()->get_data();
                break;
        }

        $context = [];
        $context['bots'] = [
            'yesterdays-email'           => 'Yesterday\'s Email',
            'weekly-traffic'             => 'Weekly Traffic',
            'newsletter-signups-by-page' => 'Newsletter Signups by Page',
        ];
        $context['selected_bot'] = $selected_bot;
        $context['slack_channel'] = $slack_channel;
        $context['message'] = $message;
        $context['data'] = $data;
        $context['page'] = $this->menu_slug;
        $context['nonce_field'] = wp_nonce_field( $this->menu_slug, '_wpnonce', true, false );
        $context['primary_button'] = get_submit_button( 'Test Bot', 'primary', 'sync-and-save', false );
        Timber::render( 'partials/admin/test-metricbots.twig', $context );

        if ( $slack_channel ) {
            if ( '#' != $slack_channel[0] ) {
                $slack_channel = '#' . $slack_channel;
            }
            $notifications = new Notifications;
            $slack_args = [
                'username'    => 'MetricBot Test',
                'icon_emoji'  => ':ghost:',
                'channel'     => $slack_channel,
                'force_send'  => true,
            ];
            return $notifications->send( $message, $slack_args );
        }
    }
}
