<?php

namespace Pedestal;

use Pedestal\Featured_Posts;
use Pedestal\Utils\Utils;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Registrations\Taxonomies\Taxonomies;
use Pedestal\Posts\Entities\Embed;
use Pedestal\Posts\{
    Post,
    Slots
};
use Pedestal\Objects\{
    Newsletter_Lists,
    Stream,
    User
};

if ( ! class_exists( '\\Pedestal\\Pedestal' ) ) :

    abstract class Pedestal {

        private $is_email = false;

        /**
         * The root URL for the CDN i.e. https://a.spirited.media
         *
         * @var string
         */
        protected $cdn_url;

        /**
         * Map controller classes to the correct theme
         *
         * @var array
         */
        protected static $theme_class_map = [
            'billy-penn'  => 'Billy_Penn',
            'the-incline' => 'The_Incline',
        ];

        protected static $instance;

        /**
         * Load the theme
         */
        protected function load() {

            $this->set_environment();
            $this->define_constants();
            $this->set_site_config();
            $this->require_files();
            $this->setup_cache();
            $this->setup_theme();
            $this->setup_actions();
            $this->setup_filters();
        }

        /**
         * Set Pedestal's environment constant
         *
         * By default, we tread carefully and treat this as live.
         */
        private function set_environment() {
            $pedestal_env = 'live';
            if ( defined( 'WP_ENV' ) && 'development' == WP_ENV ) {
                $pedestal_env = 'dev';
            } elseif ( isset( $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
                $pedestal_env = $_ENV['PANTHEON_ENVIRONMENT'];
            }
            define( 'PEDESTAL_ENV', $pedestal_env );
        }

        /**
         * Define our constants
         */
        private function define_constants() {

            // The following constants can not be modified so we set them first.
            $version = file_get_contents( ABSPATH . '/VERSION' );
            $version = str_replace( 'Version: ', '', $version );
            define( 'PEDESTAL_VERSION', $version );

            // Define an abbreviated prefix for use in naming
            define( 'PEDESTAL_PREFIX', 'ped_' );

            // Web-root relative path to the themes directory without leading slash
            $wp_themes_path = ltrim( parse_url( get_theme_root_uri() )['path'], '/' );
            define( 'PEDESTAL_WP_THEMES_PATH', $wp_themes_path );

            $constants = apply_filters( 'pedestal_constants', [] );
            $defaults = [
                // Network Details
                'SPIRITEDMEDIA_LIVE_SITE_URL'    => 'http://spiritedmedia.com',
                'SPIRITEDMEDIA_STAGING_SITE_URL' => 'http://staging.spiritedmedia.com',

                // Site Details
                'PEDESTAL_BLOG_URL'            => '',
                'PEDESTAL_BLOG_NAME'           => get_bloginfo( 'name' ),
                'PEDESTAL_BLOG_DESCRIPTION'    => get_bloginfo( 'description' ),
                'PEDESTAL_CITY_NAME'           => '',
                'PEDESTAL_CITY_NICKNAME'       => '',
                'PEDESTAL_STATE_NAME'          => '',
                'PEDESTAL_STATE'               => '',
                'PEDESTAL_ZIPCODE'             => '',
                'PEDESTAL_BUILDING_NAME'       => '',
                'PEDESTAL_STREET_ADDRESS'      => '',
                'PEDESTAL_DATE_FORMAT'         => 'M d Y',
                'PEDESTAL_TIME_FORMAT'         => 'g:i a',
                'PEDESTAL_DATETIME_FORMAT'     => sprintf( esc_html__( '%s \a\t %s', 'pedestal' ), get_option( 'date_format' ), get_option( 'time_format' ) ),
                'PEDESTAL_GOOGLE_ANALYTICS_ID' => '',
                'PEDESTAL_GOOGLE_OPTIMIZE_ID'  => '',
                'PEDESTAL_BOXTERCO_SCRIPT'     => '',
                'PEDESTAL_COMSCORE_ID'         => '',

                // Email
                'PEDESTAL_EMAIL_CONTACT'          => '',
                'PEDESTAL_EMAIL_NEWS'             => '',
                'PEDESTAL_EMAIL_INTERNAL_MAILBOX' => '',
                'PEDESTAL_EMAIL_INTERNAL_DOMAIN'  => '',
                'PEDESTAL_EMAIL_FROM_NAME'        => get_bloginfo( 'name' ),
                'PEDESTAL_EMAIL_PLACEHOLDER'      => '',

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'        => '',
                'PEDESTAL_TWITTER_CONSUMER_KEY'    => 'v28fnuzyBOGCFxIksqC6kOixd',
                'PEDESTAL_TWITTER_CONSUMER_SECRET' => 'IcWL0dryn9VB2SRW0U6D447GmGorvig30jWLHlXabWzIWGe0oC',
                'PEDESTAL_INSTAGRAM_USERNAME'      => '',
                'PEDESTAL_FACEBOOK_PAGE'           => '',
                'PEDESTAL_FACEBOOK_PAGE_ID'        => '',
                'PEDESTAL_YOUTUBE_CHANNEL_ID'      => '',

                // Users
                'PEDESTAL_USER_TITLE_MAX_LENGTH' => 72,
                'PEDESTAL_USER_HASH_PHRASE'      => 'billy penn is so cool ',

                // Branding
                'PEDESTAL_BRAND_COLOR' => '',

                // API Keys
                'MANDRILL_API_KEY'   => '',
                'EVERYBLOCK_API_KEY' => '31f70243ea980f63a6545a6bc4bfabd3a284dfa7',

                // Slack
                'PEDESTAL_SLACK_WEBHOOK_ENDPOINT'       => 'https://hooks.slack.com/services/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',
                'PEDESTAL_SLACK_CHANNEL_BOTS_PRODUCT'   => '#botcountry',
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '',
                'PEDESTAL_SLACK_CHANNEL_NEWSLETTER'     => '',
                'PEDESTAL_SLACK_BOT_NAME'               => 'PedestalBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => '',
            ];
            $constants = wp_parse_args( $constants, $defaults );
            foreach ( $constants as $constant => $value ) {
                $constant = strtoupper( $constant );
                if ( ! defined( $constant ) ) {
                    define( $constant, $value );
                }
            }

            // The following constants require other constants to be set first.
            define( 'SPIRITEDMEDIA_PEDESTAL_LIVE_DIR', SPIRITEDMEDIA_LIVE_SITE_URL . '/wp-content/themes/pedestal' );
            define( 'SPIRITEDMEDIA_PEDESTAL_STAGING_DIR', SPIRITEDMEDIA_STAGING_SITE_URL . '/wp-content/themes/pedestal' );
            $twitter_share_text_max_length = 140 - strlen( ' via @' . PEDESTAL_TWITTER_USERNAME );
            define( 'PEDESTAL_TWITTER_SHARE_TEXT_MAX_LENGTH', $twitter_share_text_max_length );

            $dev_auth_user = 'spirited';
            $dev_auth_pass = 'media';
            define( 'PEDESTAL_DEV_AUTH', $dev_auth_user . ':' . $dev_auth_pass );
        }

        /**
         * Set a class property
         *
         * @param string $name  Property name
         * @param mixed $value  Property value
         */
        public function set_property( string $name, $value ) {
            $this->$name = $value;
        }

        /**
         * Get site config options
         *
         * @return array
         */
        public function get_site_config() {
            if ( ! empty( $this->site_config ) ) {
                return $this->site_config;
            }
        }

        /**
         * Set site config options
         *
         * Each site must define its own specfic config options.
         */
        abstract protected function set_site_config();

        /**
         * Require the components we're using
         */
        private function require_files() {

            /**
             * Fixes plugins_url() for plugins located in /lib
             */
            add_filter( 'plugins_url', function( $plugins_url, $path, $plugin ) {
                if ( false !== stripos( $plugin, get_template_directory() ) ) {
                    $plugins_url = get_template_directory_uri() . str_replace( WP_PLUGIN_URL . get_template_directory(), '', $plugins_url );
                }
                return $plugins_url;
            }, 10, 3 );

            // Include WP_oEmbed class
            require_once ABSPATH . WPINC . '/class-oembed.php';

            // Components
            require_once dirname( __FILE__ ) . '/lib/mandrill-wp-mail.php';
            // require_once dirname( __FILE__ ) . '/lib/codebird/codebird.php';
            // require_once dirname( __FILE__ ) . '/lib/codebird/class-wp-codebird.php';

            if ( defined( 'WP_CLI' ) && WP_CLI ) {
                require_once dirname( __FILE__ ) . '/inc/cli/class-cli.php';
                require_once dirname( __FILE__ ) . '/inc/cli/class-cli-clusters.php';
            }

            if ( is_admin() ) {
                $this->admin = Admin\Admin::get_instance();
                $this->cluster_tools = Admin\Cluster_Tools::get_instance();
            } else {
                $this->frontend = Frontend::get_instance();
            }

            $this->utilities         = Utils::get_instance();
            $this->taxonomies        = Taxonomies::get_instance();
            $this->post_types        = Types::get_instance();
            $this->user_management   = User_Management::get_instance();
            $this->subscriptions     = Subscriptions::get_instance();
            $this->newsletter_lists  = Newsletter_Lists::get_instance();
            $this->shortcode_manager = Shortcode_Manager::get_instance();
            $this->adverts           = Adverts::get_instance();
            $this->slots             = Posts\Slots\Slots::get_instance();
            $this->feeds             = Feeds::get_instance();
            $this->featured_posts    = Featured_Posts::get_instance();

            // Some functionality should only ever run on a live environment
            if ( 'live' === PEDESTAL_ENV ) {
                $this->cron_management = Cron_Management::get_instance();
            }

        }

        /**
         * Set up theme actions
         */
        private function setup_actions() {

            add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
            add_action( 'widgets_init', [ $this, 'action_widgets_init' ], 11 );

            /*
             * Remove "Comments" from admin bar because we don't have comments
             */
            add_action( 'admin_bar_menu', function( $wp_admin_bar ) {
                $wp_admin_bar->remove_menu( 'comments' );
            }, 999 );

            add_action( 'draft_to_publish', [ $this, 'handle_post_notifications' ] );
            add_action( 'draft_to_publish', [ $this, 'handle_set_post_pedestal_version' ] );
            add_action( 'future_to_publish', [ $this, 'handle_post_notifications' ] );
            add_action( 'future_to_publish', [ $this, 'handle_set_post_pedestal_version' ] );

        }

        /**
         * Set up theme filters
         */
        protected function setup_filters() {

            /*
             * Co-Authors Plus tweaks
             */
            add_filter( 'coauthors_guest_authors_enabled', '__return_false' );
            add_filter( 'coauthors_plus_should_query_post_author', '__return_false' );
            add_filter( 'coauthors_guest_author_avatar_sizes', '__return_empty_array' );
            add_filter( 'coauthors_guest_author_manage_cap', function() {
                // Allow editors and above
                return 'edit_others_posts';
            });

            /*
             * Convert Mandrill emails to inline styles
             */
            add_filter( 'mandrill_wp_mail_pre_message_args', function( $args ) {
                $args['inline_css'] = true;
                $args['preserve_recipients'] = false; // don't ever expose multiple 'to' addresses
                return $args;
            });
            add_filter( 'wp_mail_from', function( $original_var ) {
                if ( PEDESTAL_EMAIL_NEWS ) {
                    return PEDESTAL_EMAIL_NEWS;
                }
                return $original_var;
            });
            add_filter( 'wp_mail_from_name', function( $original_var ) {
                if ( PEDESTAL_EMAIL_FROM_NAME ) {
                    return PEDESTAL_EMAIL_FROM_NAME;
                }
                return $original_var;
            });

            add_filter( 'pre_option_blogdescription', function( $original_var ) {
                if ( PEDESTAL_BLOG_DESCRIPTION ) {
                    return PEDESTAL_BLOG_DESCRIPTION;
                }
                return $original_var;
            });

            add_filter( 'pre_option_date_format', function( $date_format ) {
                if ( PEDESTAL_DATE_FORMAT ) {
                    $date_format = PEDESTAL_DATE_FORMAT;
                }
                return $date_format;
            } );

            add_filter( 'pre_option_time_format', function( $time_format ) {
                if ( PEDESTAL_TIME_FORMAT ) {
                    $time_format = PEDESTAL_TIME_FORMAT;
                }
                return $time_format;
            } );

            add_filter( 'pre_option_show_avatars', '__return_true' );
            add_filter( 'pre_option_blog_public', '__return_true' );
            add_filter( 'pre_option_timezone_string', function() {
                return 'America/New_York';
            });
            add_filter( 'pre_option_default_role', function() {
                return 'subscriber';
            });
            add_filter( 'pre_option_rss_use_excerpt', '__return_zero' );

            /**
             * Limit post revisions
             */
            add_filter( 'wp_revisions_to_keep', function( $num, $post ) {
                return 5;
            }, 10, 2 );

            // Override oEmbed with our embed shortcodes
            add_filter( 'oembed_result', [ $this, 'filter_oembed_result' ], 10, 3 );

            /**
             * Add image size where largest possible proportional size is generated
             *
             * @link http://wordpress.stackexchange.com/questions/212768/add-image-size-where-largest-possible-proportional-size-is-generated
             */
            add_filter( 'intermediate_image_sizes_advanced', function( $sizes, $metadata ) {
                if ( ! empty( $metadata['width'] ) && ! empty( $metadata['height'] ) ) {

                    // Calculate the max width and height for the 4:3 ratio
                    $ratio = new \Pedestal\Utils\Image_Ratio( 4, 3 );
                    list( $width, $height ) = $ratio->get_largest_size(
                        $metadata['width'],
                        $metadata['height']
                    );

                    // Add the new custom size
                    $sizes['max-4-3'] = [
                        'width'  => $width,
                        'height' => $height,
                        'crop'   => true,
                    ];
                }

                return $sizes;
            }, 10, 2 );

            // Serve static assets through a CDN, if available
            if ( $this->get_cdn_url() ) {
                add_filter( 'wp_resource_hints', [ $this, 'filter_resource_hints_for_cdn' ], 10, 2 );
                add_filter( 'style_loader_src', [ $this, 'filter_rewrite_url_for_cdn' ], 10, 1 );
                add_filter( 'script_loader_src', [ $this, 'filter_rewrite_url_for_cdn' ], 10, 1 );
                add_filter( 'template_directory_uri', [ $this, 'filter_rewrite_url_for_cdn' ], 10, 1 );
                add_filter( 'stylesheet_directory_uri', [ $this, 'filter_rewrite_url_for_cdn' ], 10, 1 );
            }

            // Filter Twig as Timber loads it
            add_filter( 'timber/loader/twig', [ $this, 'filter_timber_loader' ] );

            // Add some generic Twig funtions and filters
            add_filter( 'timber/twig', function( $twig ) {
                // Function to check if doing email and alternate strings
                $twig->addFunction( new \Twig_SimpleFunction( 'if_email', function( $email_str, $standard_str ) {
                    if ( $this->is_email() ) {
                        return $email_str;
                    }
                    return $standard_str;
                } ) );
                return $twig;
            }, 99 );
        }

        /**
         * Get the theme class mapping
         *
         * @return array
         */
        public static function get_theme_class_map() {
            return self::$theme_class_map;
        }

        /**
         * Set up the object cache
         */
        private function setup_cache() {
            // Set up non-persistent object caching groups
            wp_cache_add_non_persistent_groups( [ '_np_pedestal' ] );
        }

        /**
         * Set up theme configuration
         */
        private function setup_theme() {

            // Use post thumbnails as featured images
            add_theme_support( 'post-thumbnails' );

            // Post formats aren't used in this theme.
            remove_theme_support( 'post-formats' );

            // Enable support for HTML5 markup.
            add_theme_support( 'html5', [
                'comment-list',
                'search-form',
                'comment-form',
                'gallery',
                'caption',
            ] );

            // Images
            add_image_size( 'medium-square', 300, 300, true );

            // Image sizes for responsive content images
            add_image_size( '2048-wide', 2048 );
            add_image_size( '1024-wide', 1024 );
            add_image_size( '800-wide', 800 );
            add_image_size( '640-wide', 640 );
            add_image_size( '480-wide', 480 );
            add_image_size( '400-wide', 400 );
            add_image_size( '320-wide', 320 );

            // 16x9 for the lead image
            add_image_size( '2048-16x9', 2048, 1152, true );
            add_image_size( '1024-16x9', 1024, 576, true ); // The size of the lead image
            add_image_size( '800-16x9', 800, 450, true );
            add_image_size( '640-16x9', 640, 360, true );
            add_image_size( '480-16x9', 480, 270, true );
            add_image_size( '400-16x9', 400, 225, true );
            add_image_size( '320-16x9', 320, 180, true );

            add_image_size( 'twitter-card', 120, 120, true );
            add_image_size( 'facebook-open-graph', 1200, 630, true );

            $editor_stylesheets = [ '/assets/dist/css/editor-style.css' ];
            array_walk( $editor_stylesheets, function( &$value ) {
                $value = get_stylesheet_directory_uri() . $value;
            } );
            add_editor_style( $editor_stylesheets );

            $sidebars = [
                'stream'      => esc_html__( 'Stream', 'pedestal' ),
                'story'       => esc_html__( 'Story', 'pedestal' ),
                'entity'      => esc_html__( 'Entity', 'pedestal' ),
            ];
            foreach ( $sidebars as $id => $sidebar ) {
                $args = [
                    'name'          => $sidebar,
                    'id'            => "sidebar-$id",
                    'description'   => '',
                    'class'         => '',
                    'before_widget' => '<li id="%1$s" class="widget has-border--bottom %2$s">',
                    'after_widget'  => "</li>\n",
                    'before_title'  => '<h3 class="widget-title">',
                    'after_title'   => "</h3>\n",
                ];
                register_sidebar( $args );
            }

        }

        /**
         * Register custom rewrite rules
         */
        public function action_init_register_rewrites() {

            add_rewrite_rule( 'promotional-content/?$', 'index.php?promotional-content=1', 'top' );
            add_rewrite_rule( 'newsletter-signup/?$', 'index.php?newsletter-signup=1', 'top' );
            add_rewrite_rule( 'unfollow-confirmation/?$', 'index.php?unfollow-confirmation=1', 'top' );
            add_rewrite_rule( 'unsubscribe-confirmation/?$', 'index.php?unsubscribe-confirmation=1', 'top' );
            add_rewrite_endpoint( 'ics', EP_PERMALINK );

        }

        /**
         * Register and unregister widgets
         */
        public function action_widgets_init() {
            global $wp_widget_factory;

            // Our widgets
            register_widget( '\Pedestal\Widgets\Signup_Newsletter_Widget' );
            register_widget( '\Pedestal\Widgets\In_This_Story_Widget' );
            register_widget( '\Pedestal\Widgets\Recent_Content_Widget' );
            register_widget( '\Pedestal\Widgets\Recent_Video_Widget' );
            register_widget( '\Pedestal\Widgets\Daily_Insta_Widget' );

            // Our DFP widgets
            register_widget( '\Pedestal\Widgets\DFP\Rail_Right_Widget' );

            // Unregister core widgets we won't be using
            unregister_widget( 'WP_Widget_Calendar' );
            unregister_widget( 'WP_Widget_Pages' );
            unregister_widget( 'WP_Widget_Archives' );
            unregister_widget( 'WP_Widget_Links' );
            unregister_widget( 'WP_Widget_Meta' );
            unregister_widget( 'WP_Widget_Categories' );
            unregister_widget( 'WP_Widget_Recent_Comments' );
            unregister_widget( 'WP_Widget_Recent_Posts' );
            unregister_widget( 'WP_Widget_RSS' );
            unregister_widget( 'WP_Widget_Tag_Cloud' );
            unregister_widget( 'WP_Widget_Search' );

        }

        /**
         * [filter_oembed_result description]
         *
         * @param  [type] $data [description]
         * @param  [type] $url  [description]
         * @param  [type] $args [description]
         * @return [type]       [description]
         */
        public function filter_oembed_result( $data, $url, $args ) {
            return Embed::do_embed( [ 'url' => $url ] );
        }

        /**
         * Adds the CDN URL as a preconnect resource hint.
         *
         * @link https://make.wordpress.org/core/2016/07/06/resource-hints-in-4-6/
         *
         * @param  array $hints           Array of URLs
         * @param  string $relation_type  Type of hint used to determine if we should modify $hints
         * @return array                  Modified $hints
         */
        public function filter_resource_hints_for_cdn( $hints = [], $relation_type = '' ) {
            // Let's preconnect to the CDN URL that we're about to make requests to
            if ( $this->get_cdn_url() && 'preconnect' == $relation_type ) {
                $hints[] = $this->get_cdn_url();
            }

            // We don't need to do a dns-prefetch if we're already going to preconnect ot the CDN URL
            if ( $this->get_cdn_url() && 'dns-prefetch' == $relation_type ) {
                $needle = $this->get_cdn_url();
                $needle = str_replace( 'https://', '', $needle );
                $needle = str_replace( 'http://', '', $needle );
                foreach ( $hints as $index => $hint ) {
                    if ( $hint == $needle ) {
                        unset( $hints[ $index ] );
                    }
                }
            }
            return $hints;
        }

        /**
         * Rewrite URL so request goes through the CDN
         *
         * @param  string $url  URL to be rewritten
         * @return string       Modified $url
         */
        public function filter_rewrite_url_for_cdn( $url = '' ) {
            if ( $this->get_cdn_url() ) {
                return str_replace( get_site_url(), $this->cdn_url, $url );
            }
            return $url;
        }

        /**
         * Filter Timber's Twig loader
         */
        public function filter_timber_loader( $loader ) {
            $loader->addGlobal( 'macros', $loader->loadTemplate( 'macros/macros.twig' ) );
            return $loader;
        }

        /**
         * Filter Timber's default context variables
         *
         * Most of this filtering happens in \Pedestal\Frontend but some basic
         * sitewide variables should be available to Timber across the board.
         *
         * This must be called by child themes and not used as a filter directly.
         *
         * @return $context Timber context
         */
        protected function handle_filter_timber_context( $context ) {
            $site_config = Pedestal()->get_site_config();
            $theme_path = PEDESTAL_WP_THEMES_PATH . '/' . wp_get_theme()->get_stylesheet();

            $context['is_email'] = Pedestal()->is_email();

            $context['date_format'] = get_option( 'date_format' );
            $context['time_format'] = get_option( 'time_format' );
            $context['datetime_format'] = PEDESTAL_DATETIME_FORMAT;

            $context['site']->social = [
                'twitter_url'      => 'https://twitter.com/' . PEDESTAL_TWITTER_USERNAME,
                'facebook_url'     => PEDESTAL_FACEBOOK_PAGE,
                'facebook_page_id' => PEDESTAL_FACEBOOK_PAGE_ID,
                'instagram_url'    => 'https://www.instagram.com/' . PEDESTAL_INSTAGRAM_USERNAME . '/',
            ];

            $context['site']->address = [
                'building_name' => PEDESTAL_BUILDING_NAME,
                'street_address' => PEDESTAL_STREET_ADDRESS,
                'city' => PEDESTAL_CITY_NAME,
                'state' => PEDESTAL_STATE,
                'zipcode' => PEDESTAL_ZIPCODE,
            ];

            $context['site']->emails = [
                'contact'                => PEDESTAL_EMAIL_CONTACT,
                'news'                   => PEDESTAL_EMAIL_NEWS,
                'placeholder'            => PEDESTAL_EMAIL_PLACEHOLDER,
                'daily_newsletter_id'    => $this->newsletter_lists->get_newsletter_list_id( 'Daily Newsletter' ),
                'breaking_newsletter_id' => $this->newsletter_lists->get_newsletter_list_id( 'Breaking News' ),
            ];

            $context['site']->live_urls = [
                'corporate'    => SPIRITEDMEDIA_LIVE_SITE_URL,
                'current'      => $site_config['site_live_url'],
                'theme'        => $site_config['site_live_url'] . $theme_path,
                'theme_parent' => SPIRITEDMEDIA_PEDESTAL_LIVE_DIR,
            ];

            $context['site']->branding = [
                'color' => PEDESTAL_BRAND_COLOR,
            ];

            $parsely = new \Pedestal\Objects\Parsely;
            $context['site']->analytics = [
                'ga_id' => PEDESTAL_GOOGLE_ANALYTICS_ID,
                'ga_optimize_id' => PEDESTAL_GOOGLE_OPTIMIZE_ID,
                'parsely' => [
                    'site' => parse_url( home_url(), PHP_URL_HOST ),
                    'data' => $parsely->get_data(),
                ],
            ];

            if ( ! empty( $context['pages'] ) && is_array( $context['pages'] ) ) {
                $pages_defaults = [
                    'about' => [
                        'statement' => [
                            'body'     => '',
                            'is_quote' => false,
                            'speaker'  => '',
                        ],
                    ],
                    '404' => [],
                ];
                $context['pages'] = Utils::array_merge_recursive( $pages_defaults, $context['pages'] );
            }

            return $context;
        }

        /**
         * Handle Slack notifications
         */
        public function handle_post_notifications( $post ) {
            if ( ! is_object( $post ) || ! property_exists( $post, 'ID' ) ) {
                return;
            }
            $post_id = $post->ID;
            if ( in_array( Types::get_post_type( $post_id ), Types::get_pedestal_post_types() ) ) {
                $post_obj = Post::get_by_post_id( $post_id );
                $post_obj->notify_on_publish();
            }
        }

        /**
         * Save the current Pedestal version to post meta on publish
         */
        public function handle_set_post_pedestal_version( $post ) {
            if ( ! is_object( $post ) || ! property_exists( $post, 'ID' ) ) {
                return;
            }
            $post_id = $post->ID;
            if ( in_array( Types::get_post_type( $post_id ), Types::get_pedestal_post_types() ) ) {
                $post_obj = Post::get_by_post_id( $post_id );
                $post_obj->set_published_pedestal_ver();
            }
        }

        /**
         * Get the site's internal email address
         *
         * @param  string $suffix Mailbox suffix. Optional.
         *
         * @return string         Email address
         */
        public function get_internal_email( $suffix = '' ) {
            $addr = PEDESTAL_EMAIL_INTERNAL_MAILBOX;
            if ( $suffix ) {
                $addr .= '+' . $suffix;
            }
            $addr .= '@' . PEDESTAL_EMAIL_INTERNAL_DOMAIN;
            return $addr;
        }

        /**
         * Get the Spotlight post object
         *
         * Gets the featured post if set -- if not set, then get the most recent
         * editorial post.
         *
         * @return array
         */
        public function get_spotlight_post() {
            $spotlight = $this->get_spotlight_data();
            if ( ! $spotlight['enabled'] ) {
                return false;
            }
            $post = Post::get_by_post_id( $spotlight['content'] );
            if ( empty( $post ) ) {
                $stream = new Stream( [
                    'posts_per_page' => 1,
                    'post_type'      => Types::get_original_post_types(),
                ] );
                if ( empty( $stream->get_stream() ) ) {
                    return;
                }
                $post = $stream->get_stream()[0];
            }
            return $post;
        }

        /**
         * Get the data for Spotlight
         *
         * @return array
         */
        public function get_spotlight_data() {
            return get_option( 'pedestal_spotlight', [
                'enabled' => 0,
                'label'   => '',
                'content' => 0,
            ] );
        }

        /**
         * Returns the CDN URL
         *
         * If the CDN URL is not set, then set it.
         *
         * @return string|false The CDN URL
         */
        public function get_cdn_url() {
            if ( empty( $this->cdn_url ) ) {
                $this->set_cdn_url();
            }
            return $this->cdn_url;
        }

        /**
         * Set the CDN URL
         */
        protected function set_cdn_url() {
            $s3_options = get_site_option( 'tantan_wordpress_s3' );
            if (
                ! is_array( $s3_options )
                || empty( $s3_options['cloudfront'] )
                || ! isset( $s3_options['cloudfront'] )
                || '0' === $s3_options['serve-from-s3']
            ) {
                $this->cdn_url = false;
                return;
            }

            $proto = 'http://';
            if ( isset( $s3_options['force-https'] ) && '1' === $s3_options['force-https'] ) {
                $proto = 'https://';
            }
            $this->cdn_url = $proto . $s3_options['cloudfront'];
        }

        /**
         * Is the current request loaded through email.php?
         *
         * @return boolean
         */
        public function is_email() {
            return $this->is_email;
        }
    }


    /**
     * Load the theme
     */
    function Pedestal() {
        $theme_name = wp_get_theme()->get_stylesheet();
        $class_map = Pedestal::get_theme_class_map();
        $class = '\\Pedestal\\' . $class_map[ $theme_name ];
        return $class::get_instance();
    }
    add_action( 'after_setup_theme', '\\Pedestal\\Pedestal', 10 );

endif;
