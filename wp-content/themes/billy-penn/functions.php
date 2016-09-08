<?php
/**
 * Billy Penn Functions
 */

namespace Pedestal;

class Billy_Penn extends Pedestal {

    /**
     * Site config options
     *
     * @var array
     */
    protected $site_config = [];

    protected static $instance;

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new Billy_Penn;
            self::$instance->load();
            self::$instance->load_pedestal();
        }
        return self::$instance;
    }

    /**
     * Load the child theme
     */
    protected function load() {
        $this->setup_filters();
    }

    /**
     * Load Pedestal
     *
     * All of Pedestal's methods overridden in this class MUST be called again!
     *
     * This must be called after `load()` because child themes must be loaded
     * before parent themes.
     *
     */
    protected function load_pedestal() {
        parent::load();
        parent::setup_filters();
    }

    /**
     * Setup filters
     */
    protected function setup_filters() {
        add_filter( 'pedestal_constants', function() {
            return [
                // Site Details
                'PEDESTAL_BLOG_NAME'              => 'Billy Penn',
                'PEDESTAL_BLOG_DESCRIPTION'       => 'Billy Penn is a mobile platform for a better Philly -- the easiest way to find and follow local and breaking news in Philadelphia.',
                'PEDESTAL_CITY_NAME'              => 'Philadelphia',
                'PEDESTAL_CITY_NICKNAME'          => 'Philly',
                'PEDESTAL_BLOG_URL'               => 'https://medium.com/billy-penn',
                'PEDESTAL_GOOGLE_ANALYTICS_ID'    => 'UA-54099407-1',

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'       => 'billy_penn',
                'PEDESTAL_FACEBOOK_PAGE'          => 'https://www.facebook.com/billypennnews',
                'PEDESTAL_INSTAGRAM_USERNAME'     => 'billy_penn',

                // Email
                'PEDESTAL_EMAIL_CONTACT'          => 'contact@billypenn.com',
                'PEDESTAL_EMAIL_NEWS'             => 'news@billypenn.com',
                'PEDESTAL_EMAIL_INTERNAL_MAILBOX' => 'billypennnews',
                'PEDESTAL_EMAIL_INTERNAL_DOMAIN'  => 'gmail.com',

                // Slack
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '#phl-botcountry',
                'PEDESTAL_SLACK_BOT_NAME'               => 'BillyPennBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => ':billypenn:',
            ];
        } );
    }

    /**
     * Set site config options
     */
    protected function set_site_config() {
        if ( empty( $this->site_config ) ) {
            $this->site_config = [
                'site_name'           => get_bloginfo( 'name' ),
                'site_live_url'       => 'http://billypenn.com/',
                'site_branding_color' => '#268a8c',
            ];
        }
    }
}
