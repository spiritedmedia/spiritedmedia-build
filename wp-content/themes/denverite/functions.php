<?php
/**
 * Denverite Functions
 */

namespace Pedestal;

use \Pedestal\Utils\Utils;

class Denverite extends Pedestal {

    /**
     * Site config options
     *
     * @var array
     */
    protected $site_config = [];

    protected static $instance;

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new Denverite;
            self::$instance->load();
            self::$instance->load_pedestal();
        }
        return self::$instance;
    }

    /**
     * Load the child theme
     */
    protected function load() {
        $this->setup_actions();
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
     * Setup actions
     */
    protected function setup_actions() {
        // Perform some 301 redirects for old URLs to new URLs
        // Old URL format is <post-name>-<post_id> i.e.
        // denverite.com/abc-123/ --> denverite.com/2018/01/12/abc/
        add_action( 'template_redirect', function() {
            global $wp;
            if ( ! is_404() ) {
                return;
            }
            $current_url = home_url( $wp->request );
            $parts = explode( '-', $current_url );
            $maybe_post_id = end( $parts );
            if ( ! is_numeric( $maybe_post_id ) ) {
                return;
            }
            $maybe_post = get_post( $maybe_post_id );
            if ( empty( $maybe_post ) ) {
                return;
            }
            $redirect_url = get_permalink( $maybe_post );
            // Add any query parameters that might have been included in the request
            $redirect_url = add_query_arg( $_GET, $redirect_url );
            wp_safe_redirect( $redirect_url, 301 );
            die();
        });

        // Redirect to side-by-side compare tool if ?compare is present
        add_action( 'template_redirect', function() {
            if ( ! isset( $_GET['compare'] ) ) {
                return;
            }
            $current_url = home_url( $wp->request );
            if ( is_single() ) {
                $post = get_post();
                $path = '/' . $post->post_name . '-' . $post->ID . '/';
                $current_url = home_url( $path );
            }
            $compare_url = home_url( '/qa/side-by-side/' );
            $compare_url = add_query_arg( [
                'url' => $current_url,
            ], $compare_url );
            wp_safe_redirect( $compare_url );
            die();
        });
    }

    /**
     * Setup filters
     */
    protected function setup_filters() {
        add_filter( 'pedestal_constants', function() {
            return [
                // Site Details
                'PEDESTAL_BLOG_NAME'                    => 'Denverite',
                'PEDESTAL_BLOG_DESCRIPTION'             => 'Useful and delightful news for people who care about Denver. What’s happening and why it matters. Plus: Fun stuff.',
                'PEDESTAL_BLOG_TAGLINE'                 => 'Denverite, the Denver site!',
                'PEDESTAL_HOMEPAGE_TITLE'               => 'Denverite, the Denver site!',
                'PEDESTAL_CITY_NAME'                    => 'Denver',
                'PEDESTAL_CITY_NICKNAME'                => 'Mile High City',
                'PEDESTAL_STATE_NAME'                   => 'Colorado',
                'PEDESTAL_STATE'                        => 'CO',
                'PEDESTAL_ZIPCODE'                      => '80204',
                'PEDESTAL_BUILDING_NAME'                => '',
                'PEDESTAL_STREET_ADDRESS'               => '1062 Delaware St',
                'PEDESTAL_BLOG_URL'                     => 'https://medium.com/billy-penn',
                'PEDESTAL_SITE_TIMEZONE'                => 'America/Denver',

                // Account Identifiers
                'PEDESTAL_GOOGLE_ANALYTICS_ID'          => 'UA-77340868-1',
                'PEDESTAL_GOOGLE_ANALYTICS_WEB_VIEW_ID' => '121553308',

                // DFP
                'PEDESTAL_DFP_PREFIX' => 'DEN',

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'    => 'denverite',
                'PEDESTAL_INSTAGRAM_USERNAME'  => 'dnvrite',
                'PEDESTAL_FACEBOOK_PAGE'       => 'https://www.facebook.com/dnvrite/',
                'PEDESTAL_FACEBOOK_PAGE_ID'    => '241487632889156',
                'PEDESTAL_YOUTUBE_CHANNEL_ID'  => 'UCKjA7SEgXtzIKUFQ4KyOdRw',

                // Email
                'PEDESTAL_EMAIL_CONTACT'          => 'contact@denverite.com',
                'PEDESTAL_EMAIL_NEWS'             => 'news@denverite.com',
                'PEDESTAL_EMAIL_TIPS'             => 'tips@denverite.com',

                // Slack
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '#den-botcountry',
                'PEDESTAL_SLACK_CHANNEL_NEWSLETTER'     => '#den-newsletter',
                'PEDESTAL_SLACK_CHANNEL_CITY'           => '#den',
                'PEDESTAL_SLACK_BOT_NAME'               => 'DenveriteBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => ':denverite:',

                // Site Features
                'PEDESTAL_ENABLE_INSTAGRAM_OF_THE_DAY'  => false,
                'PEDESTAL_ENABLE_FOOTER_EMAIL_ICON'     => true,
                'PEDESTAL_ENABLE_STREAM_ITEM_AVATAR'    => true,

                // Membership
                'PEDESTAL_NRH_PROPERTY' => 'denverite',
            ];
        } );

        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );

        add_filter( 'pedestal_footer_menu', function( $menu ) {
            return [
                'Advertising'    => '/advertising/',
                'Terms of Use'   => '/terms-of-use/',
                'Privacy Policy' => '/privacy-policy/',
                'About'          => '/about-denverite-staff/',
                'Search'         => '/?s=',
            ];
        } );

        // Send the Weekly Traffic Report to the editorial bot channel
        add_filter( 'pedestal_weekly_traffic_slack_args', function( $slack_args ) {
            $slack_args['channel'] = '#den-voice';
            return $slack_args;
        });

        // Add Merriweather and Montserrat fonts
        add_filter( 'pedestal_google_fonts_string', function( $string ) {
            return 'Merriweather:400,400i,700,700i|Montserrat:400,400i,600,600i';
        } );
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
                    'body'     => esc_html__( 'Knowledge is the treasure of a wise man.', 'pedestal' ),
                    'speaker'  => 'William Penn',
                    'is_quote' => true,
                ],
            ],
        ];
        $context['member_bar_text'] = 'Become a member and be a better Denverite every day.';
        $context['site']->emails['daily_newsletter_name'] = PEDESTAL_BLOG_NAME;
        $context['site']->emails['daily_newsletter_send_time'] = '7:20 a.m.';
        return $context;
    }

    /**
     * Set site config options
     */
    protected function set_site_config() {
        if ( empty( $this->site_config ) ) {
            $this->site_config = [
                'site_name'           => get_bloginfo( 'name' ),
                'site_live_url'       => 'https://denverite.com/',
                'site_branding_color' => '#210c42',
            ];
        }
    }
}
