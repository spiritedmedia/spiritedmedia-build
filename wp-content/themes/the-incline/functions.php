<?php
/**
 * The Incline Functions
 */

namespace Pedestal;

use Pedestal\Registrations\Post_Types\Types;

class The_Incline extends Pedestal {

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
                'PEDESTAL_BLOG_NAME'        => 'The Incline',
                'PEDESTAL_BLOG_DESCRIPTION' => 'Relevant, original and actionable news for Pittsburgh.',
                'PEDESTAL_BLOG_TAGLINE'     => 'Elevating the news in Pittsburgh',
                'PEDESTAL_HOMEPAGE_TITLE'   => 'The Incline: Elevating the news in Pittsburgh',
                'PEDESTAL_CITY_NAME'        => 'Pittsburgh',
                'PEDESTAL_CITY_NICKNAME'    => 'Steel City',
                'PEDESTAL_STATE_NAME'       => 'Pennsylvania',
                'PEDESTAL_STATE'            => 'PA',
                'PEDESTAL_ZIPCODE'          => '15212',
                'PEDESTAL_BUILDING_NAME'    => 'Alloy 26',
                'PEDESTAL_STREET_ADDRESS'   => '100 South Commons, Suite 102',

                // Account Identifiers
                'PEDESTAL_GOOGLE_ANALYTICS_ID'          => 'UA-77560864-1',
                'PEDESTAL_GOOGLE_ANALYTICS_WEB_VIEW_ID' => '121833937',
                'PEDESTAL_GOOGLE_OPTIMIZE_ID'           => 'GTM-WFRTH8G',

                // DFP
                'PEDESTAL_DFP_PREFIX' => 'PGH',

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'   => 'theinclinepgh',
                'PEDESTAL_INSTAGRAM_USERNAME' => 'theinclinepgh',
                'PEDESTAL_FACEBOOK_PAGE'      => 'https://www.facebook.com/theinclinepgh/',
                'PEDESTAL_FACEBOOK_PAGE_ID'   => '1558758474422919',
                'PEDESTAL_YOUTUBE_CHANNEL_ID' => 'UC_5rdSt3WedEe2dp9nx9cIw',

                // Branding
                'PEDESTAL_BRAND_COLOR' => '#f05329',

                // Email
                'PEDESTAL_EMAIL_CONTACT'          => 'contact@theincline.com',
                'PEDESTAL_EMAIL_NEWS'             => 'news@theincline.com',
                'PEDESTAL_EMAIL_TIPS'             => 'tips@theincline.com',
                'PEDESTAL_EMAIL_NEWSLETTER_FROM'  => 'news@theincline.com',

                // Slack
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '#pgh-botcountry',
                'PEDESTAL_SLACK_CHANNEL_NEWSLETTER'     => '#pgh-newsletter',
                'PEDESTAL_SLACK_CHANNEL_CITY'           => '#pgh',
                'PEDESTAL_SLACK_BOT_NAME'               => 'TheInclineBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => ':theincline:',

                // Membership
                'PEDESTAL_NRH_PROPERTY' => 'theincline',
            ];
        } );

        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );

        // Configure newsletter signup form
        add_filter( 'pedestal_newsletter_signup_form_args', function( $args = [] ) {
            $args['send_time'] = '6:00 a.m.';
            return $args;
        } );
    }
}
