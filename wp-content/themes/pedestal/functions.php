<?php

namespace Pedestal;

use Aptoma\Twig\Extension\MarkdownEngine;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\TokenParser\MarkdownTokenParser;

use Pedestal\Utils\Utils;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Registrations\Taxonomies\{
    Taxonomies,
    Single_Option_Taxonomies
};
use Pedestal\Posts\Entities\Embed;
use Pedestal\MetricBot\{
    MetricBots,
    Weekly_Traffic_Metric,
    Newsletter_Signups_By_Page_Metric,
    Yesterdays_Email_Metric
};
use Pedestal\Posts\{
    Post,
    Slots
};
use Pedestal\Objects\User;
use Pedestal\Email\{
    Email,
    Email_Groups,
    One_Off_Emails,
    Breaking_News_Emails,
    Newsletter_Emails,
    Newsletter_Testing,
    Follow_Updates,
    Schedule_Follow_Updates
};
use Pedestal\Menus\{
    Menus,
    Menu_Icons
};
use Pedestal\Audience\{
    Audience,
    Conversion_Prompts,
    Message_Banner,
    Message_Spot
};

if ( ! class_exists( '\\Pedestal\\Pedestal' ) ) :

    abstract class Pedestal {

        private $is_email = false;

        private $is_stream = false;

        /**
         * Map controller classes to the correct theme
         *
         * @var array
         */
        protected static $theme_class_map = [
            'billy-penn'  => 'Billy_Penn',
            'the-incline' => 'The_Incline',
            'denverite'   => 'Denverite',
        ];

        protected static $instance;

        /**
         * Load the theme
         */
        protected function load() {
            $this->set_environment();
            $this->define_constants();
            $this->require_files();
            $this->setup_cache();
            $this->setup_theme();
            $this->setup_actions();
            $this->setup_filters();

            do_action( 'pedestal_loaded' );
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
            if ( ! defined( 'PEDESTAL_ENV' ) ) {
                define( 'PEDESTAL_ENV', $pedestal_env );
            }
        }

        /**
         * Define our constants
         */
        private function define_constants() {

            // The following constants can not be modified so we set them first.
            if ( ! defined( 'PEDESTAL_VERSION' ) ) {
                $version = file_get_contents( ABSPATH . '/VERSION' );
                $version = str_replace( 'Version:', '', $version );
                define( 'PEDESTAL_VERSION', trim( $version ) );
            }

            define( 'PEDESTAL_DIST_DIRECTORY', get_template_directory() . '/assets/dist/' . PEDESTAL_VERSION );
            define( 'PEDESTAL_DIST_DIRECTORY_URI', get_template_directory_uri() . '/assets/dist/' . PEDESTAL_VERSION );
            define( 'PEDESTAL_THEME_DIST_DIRECTORY', get_stylesheet_directory() . '/assets/dist/' . PEDESTAL_VERSION );
            define( 'PEDESTAL_THEME_DIST_DIRECTORY_URI', get_stylesheet_directory_uri() . '/assets/dist/' . PEDESTAL_VERSION );
            $spirited_media_root = network_site_url() . 'wp-content/themes/spirited-media/assets/dist/' . PEDESTAL_VERSION;
            define( 'SPIRITED_MEDIA_THEME_DIST_DIRECTORY_URI', $spirited_media_root );

            // Define an abbreviated prefix for use in naming
            if ( ! defined( 'PEDESTAL_PREFIX' ) ) {
                define( 'PEDESTAL_PREFIX', 'ped_' );
            }

            // Web-root relative path to the themes directory without leading slash
            if ( ! defined( 'PEDESTAL_WP_THEMES_PATH' ) ) {
                $wp_themes_path = ltrim( parse_url( get_theme_root_uri() )['path'], '/' );
                define( 'PEDESTAL_WP_THEMES_PATH', $wp_themes_path );
            }

            $constants = apply_filters( 'pedestal_constants', [] );
            $defaults  = [
                'PEDESTAL_API_NAMESPACE'                => 'pedestal/v1',

                // Site Details
                'PEDESTAL_THEME_NAME'                   => wp_get_theme()->get_stylesheet(),
                'PEDESTAL_BLOG_NAME'                    => get_bloginfo( 'name' ),
                'PEDESTAL_BLOG_DESCRIPTION'             => get_bloginfo( 'description' ),
                'PEDESTAL_BLOG_TAGLINE'                 => '',
                'PEDESTAL_HOMEPAGE_TITLE'               => '',
                'PEDESTAL_SITE_OWNER'                   => 'Spirited Media',
                'PEDESTAL_SITE_OWNER_URL'               => 'https://spiritedmedia.com/',
                'PEDESTAL_CITY_NAME'                    => '',
                'PEDESTAL_CITY_NICKNAME'                => '',
                'PEDESTAL_STATE_NAME'                   => '',
                'PEDESTAL_STATE'                        => '',
                'PEDESTAL_ZIPCODE'                      => '',
                'PEDESTAL_BUILDING_NAME'                => '',
                'PEDESTAL_STREET_ADDRESS'               => '',
                'PEDESTAL_DATE_FORMAT'                  => 'M. d, Y',
                'PEDESTAL_TIME_FORMAT'                  => 'g:i a',
                'PEDESTAL_DATETIME_FORMAT'              => 'M. d, Y \a\t g:i a',
                'PEDESTAL_SITE_TIMEZONE'                => 'America/New_York',

                // Account Identifiers
                'PEDESTAL_GOOGLE_ANALYTICS_ID'          => '',
                'PEDESTAL_GOOGLE_ANALYTICS_WEB_VIEW_ID' => '',
                'PEDESTAL_GOOGLE_OPTIMIZE_ID'           => '',
                'PEDESTAL_COMSCORE_ID'                  => '',

                // DFP
                'PEDESTAL_DFP_ID'                       => '104495818',
                'PEDESTAL_DFP_PREFIX'                   => '',
                'PEDESTAL_DFP_SITE'                     => '',

                // Email
                'PEDESTAL_EMAIL_CONTACT'                => '',
                'PEDESTAL_EMAIL_NEWS'                   => '',
                'PEDESTAL_EMAIL_TIPS'                   => '',
                'PEDESTAL_EMAIL_NEWSLETTER_FROM'        => '',
                'PEDESTAL_EMAIL_FROM_NAME'              => get_bloginfo( 'name' ),

                // Social Media
                'PEDESTAL_TWITTER_USERNAME'             => '',
                'PEDESTAL_INSTAGRAM_USERNAME'           => '',
                'PEDESTAL_FACEBOOK_PAGE'                => '',
                'PEDESTAL_FACEBOOK_PAGE_ID'             => '',
                'PEDESTAL_YOUTUBE_CHANNEL_ID'           => '',

                // Branding
                'PEDESTAL_BRAND_COLOR'                  => '',

                // Slack
                'PEDESTAL_SLACK_WEBHOOK_ENDPOINT'       => 'https://hooks.slack.com/services/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',
                'PEDESTAL_SLACK_CHANNEL_BOTS_PRODUCT'   => '#botcountry',
                'PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL' => '',
                'PEDESTAL_SLACK_CHANNEL_NEWSLETTER'     => '',
                'PEDESTAL_SLACK_CHANNEL_CITY'           => '',
                'PEDESTAL_SLACK_BOT_NAME'               => 'PedestalBot',
                'PEDESTAL_SLACK_BOT_EMOJI'              => '',

                // Site Features
                'PEDESTAL_ENABLE_INSTAGRAM_OF_THE_DAY'  => true,
                'PEDESTAL_ENABLE_FOOTER_EMAIL_ICON'     => false,
                'PEDESTAL_ENABLE_STREAM_ITEM_AVATAR'    => false,

                // Membership
                'PEDESTAL_NRH_PROPERTY'                 => '',

                // Corporate
                'SPIRITED_MEDIA_THEME_NAME'             => 'spirited-media',
                'SPIRITED_MEDIA_SITE_URL'               => get_blog_details( 1, true )->siteurl,
                'SPIRITED_MEDIA_TAGLINE'                => 'Reimagining local news',
                'SPIRITED_MEDIA_EMAIL_CONTACT'          => 'contact@spiritedmedia.com',
            ];
            $constants = wp_parse_args( $constants, $defaults );
            foreach ( $constants as $constant => $value ) {
                $constant = strtoupper( $constant );
                if ( ! defined( $constant ) ) {
                    define( $constant, $value );
                }
            }

            // The following constants require other constants to be set first.

            if ( ! defined( 'PEDESTAL_BLOG_NAME_SANS_THE' ) && defined( 'PEDESTAL_BLOG_NAME' ) ) {
                define( 'PEDESTAL_BLOG_NAME_SANS_THE', str_replace( 'The ', '', PEDESTAL_BLOG_NAME ) );
            }

            if ( ! defined( 'PEDESTAL_TWITTER_SHARE_TEXT_MAX_LENGTH' ) ) {
                $twitter_share_text_max_length = 280 - strlen( ' via @' . PEDESTAL_TWITTER_USERNAME );
                define( 'PEDESTAL_TWITTER_SHARE_TEXT_MAX_LENGTH', $twitter_share_text_max_length );
            }

            if ( ! defined( 'PEDESTAL_DOMAIN_PRETTY' ) ) {
                $site_name   = str_replace( ' ', '', PEDESTAL_BLOG_NAME );
                $domain_name = parse_url( get_site_url(), PHP_URL_HOST );
                $domain_name = str_replace( mb_strtolower( $site_name ), $site_name, $domain_name );
                define( 'PEDESTAL_DOMAIN_PRETTY', $domain_name );
            }

            if ( ! defined( 'PEDESTAL_TWITTER_URL' ) ) {
                define( 'PEDESTAL_TWITTER_URL', 'https://twitter.com/' . PEDESTAL_TWITTER_USERNAME );
            }

            if ( ! defined( 'PEDESTAL_INSTAGRAM_URL' ) ) {
                define( 'PEDESTAL_INSTAGRAM_URL', 'https://www.instagram.com/' . PEDESTAL_INSTAGRAM_USERNAME . '/' );
            }
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

            if ( defined( 'WP_CLI' ) && WP_CLI ) {
                require_once dirname( __FILE__ ) . '/inc/cli/class-cli.php';
                require_once dirname( __FILE__ ) . '/inc/cli/class-cli-clusters.php';
            }

            if ( is_admin() ) {
                $this->admin             = Admin\Admin::get_instance();
                $this->homepage_settings = Admin\Homepage_Settings::get_instance();
                $this->cluster_tools     = Admin\Cluster_Tools::get_instance();
                $this->taxonomy_tools    = Admin\Taxonomy_Tools::get_instance();
                // Will be reimplemented after our move to MailChimp. See #2426
                // $this->newsletter_testing = Newsletter_Testing::get_instance();
            } else {
                $this->frontend = Frontend::get_instance();
            }

            $this->scripts_styles           = Scripts_Styles::get_instance();
            $this->utilities                = Utils::get_instance();
            $this->taxonomies               = Taxonomies::get_instance();
            $this->single_option_taxonomies = Single_Option_Taxonomies::get_instance();
            $this->post_types               = Types::get_instance();
            $this->user_management          = User_Management::get_instance();
            $this->shortcode_manager        = Shortcode_Manager::get_instance();
            $this->adverts                  = Adverts::get_instance();
            $this->ad_kill_switch           = Admin\Ad_Kill_Switch::get_instance();
            $this->slots                    = Posts\Slots\Slots::get_instance();
            $this->feeds                    = Feeds::get_instance();
            $this->featured_posts           = Featured_Posts::get_instance();
            $this->icons                    = Icons::get_instance();
            $this->cron_management          = Cron_Management::get_instance();
            $this->menus                    = Menus::get_instance();
            $this->menu_icons               = Menu_Icons::get_instance();
            $this->component_previews       = Component_Previews::get_instance();
            $this->page_cache               = Page_Cache::get_instance();

            // Target Audience Messaging
            $this->audience           = Audience::get_instance();
            $this->message_spot       = Message_Spot::get_instance();
            $this->conversion_prompts = Conversion_Prompts::get_instance();
            $this->message_banner     = Message_Banner::get_instance();

            // Metrics
            $this->metricbots                        = MetricBots::get_instance();
            $this->weekly_traffic_metric             = Weekly_Traffic_Metric::get_instance();
            $this->newsletter_signups_by_page_metric = Newsletter_Signups_By_Page_Metric::get_instance();
            $this->yesterdays_email_metric           = Yesterdays_Email_Metric::get_instance();

            // Emails
            $this->emails                  = Email::get_instance();
            $this->email_groups            = Email_Groups::get_instance();
            $this->breaking_news_emails    = Breaking_News_Emails::get_instance();
            $this->newsletter_emails       = Newsletter_Emails::get_instance();
            $this->follow_updates          = Follow_Updates::get_instance();
            $this->schedule_follow_updates = Schedule_Follow_Updates::get_instance();

        }

        /**
         * Set up theme actions
         */
        private function setup_actions() {

            add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
            add_action( 'widgets_init', [ $this, 'action_widgets_init' ], 11 );

            add_action( 'draft_to_publish', [ $this, 'handle_post_notifications' ] );
            add_action( 'draft_to_publish', [ $this, 'handle_set_post_pedestal_version' ] );
            add_action( 'future_to_publish', [ $this, 'handle_post_notifications' ] );
            add_action( 'future_to_publish', [ $this, 'handle_set_post_pedestal_version' ] );

            /**
             * Modify what appears in the admin bar menu
             */
            add_action( 'wp_before_admin_bar_render', function () {
                global $wp_admin_bar;

                // Remove comments because we don't have comments
                $wp_admin_bar->remove_menu( 'comments' );

                // Remove the New Post link from the My Sites admin bar menu becuase
                // we don't have Posts
                foreach ( $wp_admin_bar->get_nodes() as $node ) {
                    if ( $node->title == 'New Post' || $node->title == 'Manage Comments' ) {
                        $wp_admin_bar->remove_node( $node->id );
                    }
                }
            }, 999 );
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
                return PEDESTAL_SITE_TIMEZONE;
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
                    $ratio                  = new \Pedestal\Utils\Image_Ratio( 4, 3 );
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
                add_filter( 'compressed_emoji_url', [ $this, 'filter_rewrite_url_for_cdn' ], 10, 1 );
                add_filter( 'compressed_emoji_svg_url', [ $this, 'filter_rewrite_url_for_cdn' ], 10, 1 );
            }

            // Add some Twig functions and filters
            add_filter( 'timber/twig', [ $this, 'filter_timber_twig_add_basic_filters' ] );
            add_filter( 'timber/twig', [ $this, 'filter_timber_twig_add_markdown_support' ] );
            add_filter( 'timber/twig', [ $this, 'filter_timber_twig_add_functions' ], 99 );

            // Since we don't have comments, skip running the query to count all of the comments on every load
            add_filter( 'wp_count_comments', function( $count, $post_id ) {
                if ( 0 === $post_id ) {
                    $stats = [
                        'approved'       => 0,
                        'moderated'      => 0,
                        'spam'           => 0,
                        'trash'          => 0,
                        'post-trashed'   => 0,
                        'total_comments' => 0,
                        'all'            => 0,
                    ];
                    return (object) $stats;
                }
            }, 10, 2 );
        }

        /**
         * Add some basic PHP and WordPress functions as Twig filters
         */
        public function filter_timber_twig_add_basic_filters( $twig ) {
            $function_names = [
                // PHP Functions
                'addslashes',     // http://php.net/manual/en/function.addslashes.php
                'nl2br',          // http://php.net/manual/en/function.nl2br.php

                // WordPress functions
                'antispambot',    // https://developer.wordpress.org/reference/functions/antispambot/
                'esc_attr',       // https://developer.wordpress.org/reference/functions/esc_attr/
                'esc_html',       // https://developer.wordpress.org/reference/functions/esc_html/
                'esc_url',        // https://developer.wordpress.org/reference/functions/esc_url/
                'esc_js',         // https://developer.wordpress.org/reference/functions/esc_js/
                'esc_textarea',   // https://developer.wordpress.org/reference/functions/esc_textarea/
                'sanitize_email', // https://developer.wordpress.org/reference/functions/sanitize_email/
            ];
            foreach ( $function_names as $func ) {
                if ( function_exists( $func ) ) {
                    $twig->addFilter( new \Twig_SimpleFilter( $func, $func ) );
                }
            }
            return $twig;
        }

        /**
         * Add some functions to Twig
         */
        public function filter_timber_twig_add_functions( $twig ) {
            // Function to check if doing email and alternate strings
            $twig->addFunction( new \Twig_SimpleFunction( 'if_email', function( $email_str, $standard_str ) {
                if ( $this->is_email() ) {
                    return $email_str;
                }
                return $standard_str;
            } ) );

            // Get the current Unix epoch time in milliseconds
            $twig->addFunction( new \Twig_SimpleFunction( 'now', function() {
                return round( microtime( true ) * 1000 );
            } ) );

            // Add WordPress' checked() function to Twig
            $twig->addFunction( new \Twig_SimpleFunction( 'checked', function( $checked, $current = true ) {
                return checked( $checked, $current );
            } ) );

            // Add WordPress' selected() function to Twig
            $twig->addFunction( new \Twig_SimpleFunction( 'selected', function( $selected, $current = true ) {
                return selected( $selected, $current );
            } ) );

            // Add WordPress' disabled() function to Twig
            $twig->addFunction( new \Twig_SimpleFunction( 'disabled', function( $disabled, $current = true ) {
                return disabled( $disabled, $current );
            } ) );

            return $twig;
        }

        /**
         * Add Markdown support to Twig templates
         */
        public function filter_timber_twig_add_markdown_support( $twig ) {
            $engine = new MarkdownEngine\ParsedownEngine();
            $twig->addExtension( new MarkdownExtension( $engine ) );
            $twig->addTokenParser( new MarkdownTokenParser( $engine ) );
            return $twig;
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

            // Enable RSS feed links
            add_theme_support( 'automatic-feed-links' );

            // Disable comment RSS feeds
            add_filter( 'feed_links_show_comments_feed', '__return_false' );

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

            $sidebars = [
                'homepage' => esc_html__( 'Homepage', 'pedestal' ),
            ];
            foreach ( $sidebars as $id => $sidebar ) {
                $args = [
                    'name'          => $sidebar,
                    'id'            => "sidebar-$id",
                    'description'   => '',
                    'class'         => '',
                    'before_widget' => '<li id="%1$s" class="widget %2$s">',
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
            add_rewrite_endpoint( 'ics', EP_PERMALINK );
        }

        /**
         * Register and unregister widgets
         */
        public function action_widgets_init() {

            // Our widgets
            register_widget( '\Pedestal\Widgets\Recent_Content_Widget' );
            register_widget( '\Pedestal\Widgets\Recent_Video_Widget' );

            if ( PEDESTAL_ENABLE_INSTAGRAM_OF_THE_DAY ) {
                register_widget( '\Pedestal\Widgets\Daily_Insta_Widget' );
            }

            // Unregister core widgets we won't be using
            unregister_widget( 'WP_Widget_Archives' );
            unregister_widget( 'WP_Widget_Calendar' );
            unregister_widget( 'WP_Widget_Categories' );
            unregister_widget( 'WP_Widget_Custom_HTML' );
            unregister_widget( 'WP_Widget_Links' );
            unregister_widget( 'WP_Widget_Media_Audio' );
            unregister_widget( 'WP_Widget_Media_Gallery' );
            unregister_widget( 'WP_Widget_Media_Image' );
            unregister_widget( 'WP_Widget_Media_Video' );
            unregister_widget( 'WP_Widget_Meta' );
            unregister_widget( 'WP_Widget_Pages' );
            unregister_widget( 'WP_Widget_Recent_Comments' );
            unregister_widget( 'WP_Widget_Recent_Posts' );
            unregister_widget( 'WP_Widget_RSS' );
            unregister_widget( 'WP_Widget_Search' );
            unregister_widget( 'WP_Widget_Tag_Cloud' );
            unregister_widget( 'WP_Widget_Text' );

            // Unregister widgets added by plugins
            unregister_widget( 'P2P_Widget' );
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
            switch ( Utils::get_service_name_from_url( $url ) ) {
                case 'documentcloud':
                case 'instagram':
                    return $data;

                default:
                    return Embed::do_embed( [
                        'url' => $url,
                    ] );
            }
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
                return str_replace( get_site_url(), $this->get_cdn_url(), $url );
            }
            return $url;
        }

        /**
         * Setup filtering of Timber's default context variables
         *
         * If a child theme needs to change any of this data, then it should
         * define its own `filter_timber_context()` method and call
         * `handle_filter_timber_context()` from there.
         *
         * This must not be used as a filter directly. Most of this filtering
         * happens in \Pedestal\Frontend but some basic sitewide variables
         * should be available to Timber across the board.
         *
         * @return $context Timber context
         */
        protected function handle_filter_timber_context( $context ) {
            $context['PEDESTAL_VERSION'] = PEDESTAL_VERSION;

            $context['date_format'] = get_option( 'date_format' );
            $context['time_format'] = get_option( 'time_format' );

            $context['site']->owner = [
                'name' => PEDESTAL_SITE_OWNER,
                'url'  => PEDESTAL_SITE_OWNER_URL,
            ];

            $context['site']->social = [
                'facebook_page_id' => PEDESTAL_FACEBOOK_PAGE_ID,
            ];

            $context['site']->address = [
                'building_name'  => PEDESTAL_BUILDING_NAME,
                'street_address' => PEDESTAL_STREET_ADDRESS,
                'city'           => PEDESTAL_CITY_NAME,
                'state'          => PEDESTAL_STATE,
                'zipcode'        => PEDESTAL_ZIPCODE,
            ];

            $context['site']->city = [
                'name' => PEDESTAL_CITY_NAME,
            ];

            $context['site']->emails = [
                'contact' => PEDESTAL_EMAIL_CONTACT,
                'tips'    => PEDESTAL_EMAIL_TIPS,
            ];

            $context['site']->analytics = [
                'ga'      => [],
                'parsely' => [],
            ];
            // Enable analytics for everyone that can't edit posts
            if ( ! current_user_can( 'edit_posts' ) ) {
                $ga_data          = [
                    'id'         => PEDESTAL_GOOGLE_ANALYTICS_ID,
                    'optimizeID' => PEDESTAL_GOOGLE_OPTIMIZE_ID,
                ];
                $ga_inline_script = file_get_contents( PEDESTAL_DIST_DIRECTORY . '/js/ga.js' );

                $context['site']->analytics['ga'] = [
                    'optimize_id'   => $ga_data['optimizeID'],
                    'js_data'       => Utils::encode_for_js( $ga_data ),
                    'inline_script' => $ga_inline_script,
                ];

                $context['site']->analytics['parsely'] = [
                    'site' => parse_url( home_url(), PHP_URL_HOST ),
                ];

                if ( is_singular() ) {
                    $parsely_metadata                                  = [
                        'post_id' => get_queried_object_id(),
                    ];
                    $parsely_metadata                                  = htmlspecialchars( json_encode( $parsely_metadata ), ENT_NOQUOTES );
                    $context['site']->analytics['parsely']['metadata'] = $parsely_metadata;
                }
            }

            // `Schema_Metadata` needs to be loaded as late as possible so
            // conditional functions like `is_singular()` work
            $this->schema_metadata      = Schema_Metadata::get_instance();
            $context['schema_metadata'] = $this->schema_metadata->get_markup();

            $context['cdn_fallback_inline_script'] = file_get_contents( PEDESTAL_DIST_DIRECTORY . '/js/cdn-fallback.js' );

            return $context;
        }

        /**
         * Filter Timber context
         *
         * @param  array $context Timber context
         * @return array          Filtered Timber context
         */
        public function filter_timber_context( $context ) {
            return self::handle_filter_timber_context( $context );
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
                $post_obj = Post::get( $post_id );
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
                $post_obj = Post::get( $post_id );
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
            $addr = 'billypennnews';
            if ( $suffix ) {
                $addr .= '+' . $suffix;
            }
            $addr .= '@gmail.com';
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
            $post = Post::get( $spotlight['content'] );
            if ( empty( $post ) ) {
                $posts = new \WP_Query( [
                    'posts_per_page'         => 1,
                    'post_type'              => Types::get_original_post_types(),
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                ] );
                if ( empty( $posts->posts ) ) {
                    return false;
                }
                $post_id = $posts->posts[0]->ID;
                $post    = Post::get( $post_id );
            }
            // If the Spotlight post is the same as the currently requested post then bail
            if ( $post->get_id() == get_the_ID() ) {
                return false;
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
         * Returns the CDN URL if defined by S3_UPLOADS_BUCKET_URL constant in wp-config.php
         *
         * @return string|false The CDN URL without a trailing slash e.g. `https://a.spirited.media`
         */
        public function get_cdn_url() {
            if ( defined( 'S3_UPLOADS_BUCKET_URL' ) && ! empty( S3_UPLOADS_BUCKET_URL ) ) {
                return S3_UPLOADS_BUCKET_URL;
            }
            return false;
        }

        /**
         * Is the current request loaded through email.php?
         *
         * @return boolean
         */
        public function is_email() {
            return $this->is_email;
        }

        /**
         * Are we currently rendering a stream?
         *
         * @return boolean
         */
        public function is_stream() {
            return $this->is_stream;
        }
    }


    /**
     * Load the theme
     */
    function Pedestal() {
        $theme_name = wp_get_theme()->get_stylesheet();
        $class_map  = Pedestal::get_theme_class_map();
        $class      = '\\Pedestal\\' . $class_map[ $theme_name ];
        return $class::get_instance();
    }
    add_action( 'after_setup_theme', '\\Pedestal\\Pedestal', 10 );

endif;
