<?php
/**
 * Billy Penn Functions
 */

namespace Pedestal;

use Pedestal\Utils\Utils;
use Pedestal\Registrations\Post_Types\Types;

class Billy_Penn extends Pedestal {

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
                'PEDESTAL_BLOG_NAME'                    => 'Billy Penn',
                'PEDESTAL_BLOG_DESCRIPTION'             => 'Original reporting plus a carefully curated mix of important and fun news about Philadelphia.',
                'PEDESTAL_BLOG_TAGLINE'                 => 'On top of Philly news',
                'PEDESTAL_HOMEPAGE_TITLE'               => 'Billy Penn: Philly\'s source for local news, info and things to do',
                'PEDESTAL_SITE_OWNER'                   => 'Billy Penn',
                'PEDESTAL_SITE_OWNER_URL'               => 'https://whyy.org/',
                'PEDESTAL_CITY_NAME'                    => 'Philadelphia',
                'PEDESTAL_CITY_NICKNAME'                => 'Philly',
                'PEDESTAL_STATE_NAME'                   => 'Pennsylvania',
                'PEDESTAL_STATE'                        => 'PA',
                'PEDESTAL_ZIPCODE'                      => '19102',
                'PEDESTAL_BUILDING_NAME'                => '',
                'PEDESTAL_STREET_ADDRESS'               => '30 S. 15th St., The Graham Building, 15th Fl.',

                // Account Identifiers
                'PEDESTAL_GOOGLE_ANALYTICS_ID'          => 'UA-54099407-1',
                'PEDESTAL_GOOGLE_ANALYTICS_WEB_VIEW_ID' => '90219011',
                // 'PEDESTAL_GOOGLE_OPTIMIZE_ID'           => 'GTM-P8PWVHM',
                // 'PEDESTAL_COMSCORE_ID'                  => '23083389',

                // DFP
                'PEDESTAL_DFP_PREFIX'                   => 'PHL',

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'             => 'billy_penn',
                'PEDESTAL_INSTAGRAM_USERNAME'           => 'billy_penn',
                'PEDESTAL_FACEBOOK_PAGE'                => 'https://www.facebook.com/billypennnews',
                'PEDESTAL_FACEBOOK_PAGE_ID'             => '666155016815882',
                'PEDESTAL_YOUTUBE_CHANNEL_ID'           => 'UC-wbUUytMNII9M-hF8U5IDA',

                // Branding
                'PEDESTAL_BRAND_COLOR'                  => '#268a8c',

                // Email
                'PEDESTAL_EMAIL_CONTACT'                => 'contact@billypenn.com',
                'PEDESTAL_EMAIL_NEWS'                   => 'news@billypenn.com',
                'PEDESTAL_EMAIL_TIPS'                   => 'tips@billypenn.com',
                'PEDESTAL_EMAIL_NEWSLETTER_FROM'        => 'contact@billypenn.com',

                // Slack
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '#phl-botcountry',
                'PEDESTAL_SLACK_CHANNEL_NEWSLETTER'     => '#phl-newsletter',
                'PEDESTAL_SLACK_CHANNEL_CITY'           => '#phl',
                'PEDESTAL_SLACK_BOT_NAME'               => 'BillyPennBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => ':billypenn:',

                // Membership
                'PEDESTAL_NRH_PROPERTY'                 => 'billypenn',

                // Site Features
                'PEDESTAL_ENABLE_STREAM_ITEM_AVATAR'    => true,
            ];
        } );

        // Configure donate form
        add_filter( 'pedestal_donate_form_context', function( $context ) {
            $context['submit_text'] = 'Take my money!';
            return $context;
        } );

        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
    }
}
