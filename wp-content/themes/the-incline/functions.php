<?php
/**
 * The Incline Functions
 */

namespace Pedestal;

class The_Incline extends Pedestal {

    /**
     * Site config options
     *
     * @var array
     */
    protected $site_config = [];

    protected static $instance;

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new The_Incline;
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
                'PEDESTAL_BLOG_NAME'              => 'The Incline',
                'PEDESTAL_BLOG_DESCRIPTION'       => 'The Incline is a mobile platform for a better Pittsburgh -- the easiest way to find and follow local and breaking news in Pittsburgh.',
                'PEDESTAL_CITY_NAME'              => 'Pittsburgh',
                'PEDESTAL_CITY_NICKNAME'          => 'Steel City',
                // 'PEDESTAL_BLOG_URL'            => 'https://medium.com/billy-penn',
                'PEDESTAL_GOOGLE_ANALYTICS_ID'    => 'UA-77560864-1',

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'       => 'theinclinepgh',
                'PEDESTAL_INSTAGRAM_USERNAME'     => 'theinclinepgh',
                'PEDESTAL_FACEBOOK_PAGE'          => 'https://www.facebook.com/theinclinepgh/',
                'PEDESTAL_FACEBOOK_PAGE_ID'       => '1558758474422919',

                // Email
                'PEDESTAL_EMAIL_CONTACT'          => 'contact@billypenn.com',
                'PEDESTAL_EMAIL_NEWS'             => 'news@billypenn.com',
                'PEDESTAL_EMAIL_INTERNAL_MAILBOX' => 'billypennnews',
                'PEDESTAL_EMAIL_INTERNAL_DOMAIN'  => 'gmail.com',

                // Slack
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '#pgh-botcountry',
                'PEDESTAL_SLACK_BOT_NAME'               => 'TheInclineBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => ':theincline:',

                // API Keys
                'MANDRILL_API_KEY' => 'uUnTV4kMlDRY6Mokh-deqw',
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
                'site_live_url'       => 'http://theincline.com/',
                'site_branding_color' => '#ef5029',
            ];
        }
    }
}
