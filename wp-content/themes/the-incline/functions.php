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
                'PEDESTAL_BLOG_NAME'                    => 'The Incline',
                'PEDESTAL_BLOG_DESCRIPTION'             => 'The Incline is a mobile platform for a better Pittsburgh -- the easiest way to find and follow local and breaking news in Pittsburgh.',
                'PEDESTAL_CITY_NAME'                    => 'Pittsburgh',
                'PEDESTAL_CITY_NICKNAME'                => 'Steel City',
                'PEDESTAL_STATE_NAME'                   => 'Pennsylvania',
                'PEDESTAL_STATE'                        => 'PA',
                'PEDESTAL_ZIPCODE'                      => '15212',
                'PEDESTAL_BUILDING_NAME'                => 'Alloy 26',
                'PEDESTAL_STREET_ADDRESS'               => '100 South Commons, Suite 102',
                // 'PEDESTAL_BLOG_URL'                  => 'https://medium.com/billy-penn',
                'PEDESTAL_GOOGLE_ANALYTICS_ID'          => 'UA-77560864-1',
                'PEDESTAL_GOOGLE_ANALYTICS_WEB_VIEW_ID' => '121833937',
                'PEDESTAL_GOOGLE_OPTIMIZE_ID'           => 'GTM-WFRTH8G',

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'    => 'theinclinepgh',
                'PEDESTAL_INSTAGRAM_USERNAME'  => 'theinclinepgh',
                'PEDESTAL_FACEBOOK_PAGE'       => 'https://www.facebook.com/theinclinepgh/',
                'PEDESTAL_FACEBOOK_PAGE_ID'    => '1558758474422919',
                'PEDESTAL_YOUTUBE_CHANNEL_ID'  => 'UC_5rdSt3WedEe2dp9nx9cIw',

                // Email
                'PEDESTAL_EMAIL_CONTACT'          => 'contact@theincline.com',
                'PEDESTAL_EMAIL_NEWS'             => 'news@theincline.com',
                'PEDESTAL_EMAIL_INTERNAL_MAILBOX' => 'billypennnews',
                'PEDESTAL_EMAIL_INTERNAL_DOMAIN'  => 'gmail.com',
                'PEDESTAL_EMAIL_PLACEHOLDER'      => 'the.incline@example.org',

                // Slack
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '#pgh-botcountry',
                'PEDESTAL_SLACK_CHANNEL_NEWSLETTER'     => '#pgh-newsletter',
                'PEDESTAL_SLACK_CHANNEL_CITY'           => '#pgh',
                'PEDESTAL_SLACK_BOT_NAME'               => 'TheInclineBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => ':theincline:',
            ];
        } );

        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
    }

    /**
     * Filter Timber context
     *
     * @param  array $context Timber context
     * @return array          Filtered Timber context
     */
    public function filter_timber_context( $context ) {
        $context = parent::handle_filter_timber_context( $context );
        $context['pages'] = [
            'about' => [
                'statement' => [
                    'body'     => esc_html__( 'Elevating news in Pittsburgh', 'pedestal' ),
                ],
            ],
        ];
        return $context;
    }

    /**
     * Set site config options
     */
    protected function set_site_config() {
        if ( empty( $this->site_config ) ) {
            $this->site_config = [
                'site_name'           => get_bloginfo( 'name' ),
                'site_live_url'       => 'http://theincline.com/',
                'site_branding_color' => '#F05329',
            ];
        }
    }
}
