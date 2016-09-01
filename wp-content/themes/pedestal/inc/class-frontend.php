<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use Timber\Timber;

use \Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

use \Pedestal\Objects\User;

use \Pedestal\Objects\Stream;

class Frontend {

    /**
     * Array of versions before which fallback stylesheets are necessary
     *
     * @var array
     */
    private static $fallback_stylesheet_vers = [ '3.4.4' ];

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Frontend;
            self::$instance->check_maintenance_mode();
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Set up actions used on the frontend
     */
    private function setup_actions() {

        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts' ] );
        add_action( 'wp_head', [ $this, 'action_wp_head_meta_tags' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'action_wp_enqueue_scripts' ] );

        // RelayMedia's AMP version requries these
        add_action( 'wp_head', [ $this, 'action_wp_head_amp_link' ] );
        add_action( 'wp_footer', [ $this, 'action_wp_footer_amp_beacon_pixel' ] );
    }

    /**
     * Set up filters used on the frontend
     */
    private function setup_filters() {

        add_filter( 'wp_title', [ $this, 'filter_wp_title' ] );

        add_filter( 'get_twig', [ $this, 'filter_get_twig' ] );

        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );

        add_filter( 'template_include', [ $this, 'filter_template_include' ] );

        add_filter( 'get_search_form', function( $output ) {
            $output = str_replace(
                [ 'class="search-submit"', '</form>' ],
                [ 'class="search-submit button--rounded button right"', '<div class="clear-right"></div></form>' ],
                $output
            );
            return $output;
        });

        add_filter( 'body_class', function( $body_classes ) {

            global $wp, $wp_query;
            $request = rtrim( $wp->request, '/' );
            if ( 'promotional-content' == $request ) {
                $body_classes[] = 'nativo';
            }

            if ( is_page() || is_author() ) {
                $body_classes[] = 'full-width';
            }

            return $body_classes;
        });

        add_filter( 'the_content_feed', function( $content ) {
            $obj = \Pedestal\Posts\Post::get_by_post_id( get_the_ID() );
            if ( $obj ) {
                if ( 'link' == $obj->get_type() && $source = $obj->get_source() ) {
                    $content = esc_html__( 'See it at: ', 'pedestal' ) . '<a href="' . esc_url( $obj->get_permalink() ) . '">' . esc_html( $obj->get_source()->get_name() ) . '</a>';
                } elseif ( 'embed' == $obj->get_type() && $source = $obj->get_source() ) {
                    $content = esc_html__( 'See it at: ', 'pedestal' ) . '<a href="' . esc_url( $obj->get_embed_url() ) . '">' . esc_html( $obj->get_source() ) . '</a>';
                }
            }
            return $content;
        });

    }

    /**
     * Display maintenance mode template for non-Editors if option enabled
     */
    private function check_maintenance_mode() {
        $options = get_option( 'pedestal_maintenance_mode' );
        if ( empty( $options['enabled'] ) ) {
            return;
        }

        // Display a message to visitors on login page when active
        add_filter( 'login_message', function() {
            return '<div id="login_error"><p>The site is currently in maintenance mode.</p></div>';
        } );

        if ( ! current_user_can( 'edit_posts' ) && ! in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ] ) ) {
            $protocol = 'HTTP/1.0';
            if ( 'HTTP/1.1' == $_SERVER['SERVER_PROTOCOL'] ) {
                $protocol = 'HTTP/1.1';
            }
            header( "$protocol 503 Service Unavailable", true, 503 );
            header( 'Retry-After: 3600' );
            include get_template_directory() . '/templates/maintenance-mode.php';
            exit();
        }
    }

    /**
     * Modify the main query
     */
    public function action_pre_get_posts( $query ) {

        if ( ! $query->is_main_query() ) {
            return;
        }

        if ( $query->is_home() ) {
            $meta_query = [
                'relation' => 'OR',
                [
                    'key'     => 'exclude_from_home_stream',
                    'value'   => 1,
                    'compare' => '!=',
                ],
                [
                    'key'     => 'exclude_from_home_stream',
                    'compare' => 'NOT EXISTS',
                ]
            ];
            $query->set( 'meta_query', $meta_query );
            $query->set( 'post_type', Types::get_entity_post_types() );
            $query->set( 'posts_per_page', 20 );
            if ( Pedestal()->get_pinned_data()['enabled'] ) {
                $query->set( 'post__not_in', [ Pedestal()->get_pinned_post()->get_id() ] );
            }
        }

        if ( $query->is_feed() ) {
            if ( $query->is_post_type_archive() ) {
                $post_type_name = Post::get_post_type_name( $query->get( 'post_type' ) );
                add_filter( 'wp_title_rss', function( $title, $sep ) use ( $post_type_name ) {
                    echo ' ' . $sep . ' ';
                    echo $post_type_name;
                }, 10, 2);
            } else {
                $query->set( 'post_type', Types::get_entity_post_types() );
                add_filter( 'wp_title_rss', function( $title, $sep ) {
                    echo '';
                }, 10, 2);
            }
        } elseif ( $query->is_archive() && ! $query->is_author() ) {
            if ( $query->is_post_type_archive( Types::get_cluster_post_types() ) ) {
                $query->set( 'posts_per_page', -1 );
                $query->set( 'orderby', 'title' );
                $query->set( 'order', 'ASC' );
            }
        }

        if ( $query->is_author() ) {
            $query->set( 'post_type', 'pedestal_article' );
            $query->set( 'posts_per_page', 5 );
        }

        if ( $query->is_search() ) {
            // @todo stories could appear in search
            $query->set( 'post_type', Types::get_entity_post_types() );
        }

    }

    /**
     * Add meta tags to the head of our site
     */
    public function action_wp_head_meta_tags() {

        $meta_description = $this->get_current_meta_description();
        $facebook_tags = $this->get_facebook_open_graph_meta_tags();
        $twitter_tags = $this->get_twitter_card_meta_tags();

        $tags = array_merge( [ 'description' => $meta_description ], $facebook_tags, $twitter_tags );
        foreach ( $tags as $name => $value ) {

            switch ( $name ) {
                // Include meta tag for original content authors if they've provided their Facebook profile URL
                case 'article:author':
                    if ( is_array( $value ) ) :
                        foreach ( $value as $author_data ) {
                            if ( ! empty( $profile = $author_data['profile'] ) ) {
                                echo sprintf( '<meta name="%s" property="%s" content="%s" />' . PHP_EOL,
                                    esc_attr( $name ),
                                    esc_attr( $name ),
                                    esc_attr( $profile )
                                );
                            }
                        }
                    endif;
                    break;

                case 'description':
                    echo sprintf( '<meta name="%s" property="%s" content="%s" />' . PHP_EOL,
                        esc_attr( $name ),
                        esc_attr( $name ),
                        esc_attr( $value )
                    );
                    break;

                default:
                    echo sprintf( '<meta property="%s" content="%s" />' . PHP_EOL,
                        esc_attr( $name ),
                        esc_attr( $value )
                    );
                    break;

            }
        }

    }

    /**
     * Add <link> to <head> specifying URL to AMP version of article
     *
     * @link https://github.com/spiritedmedia/spiritedmedia/issues/1443
     */
    public function action_wp_head_amp_link() {
        if ( ! is_singular( Types::get_editorial_post_types() ) ) {
            return;
        }

        $post = Post::get_by_post_id( get_the_ID() );
        $parts = parse_url( $post->get_permalink() );
        $amp_url = 'https://cdn.relaymedia.com/amp/';
        $amp_url .= $parts['host'];
        $amp_url .= $parts['path'];

        echo '<link rel="amphtml" href="' . esc_url( $amp_url ) . '">' . PHP_EOL;
    }

    /**
     * Add Relay Media AMP "beacon pixel" to the footer
     *
     * Ping Relay Media so they can cache the AMP version of an article.
     *
     * @link https://github.com/spiritedmedia/spiritedmedia/issues/1443
     */
    public function action_wp_footer_amp_beacon_pixel() {
        if ( ! is_singular( Types::get_editorial_post_types() ) ) {
            return;
        }

        $post = Post::get_by_post_id( get_the_ID() );
        $permalink = $post->get_permalink();
        $beacon_url = add_query_arg( [ 'url' => urlencode( $permalink ) ], 'https://cdn.relaymedia.com/ping' );

        echo '<img src="' . esc_url( $beacon_url ) . '" width="1" height="1">' . PHP_EOL;
    }

    /**
     * Get meta description for current page
     *
     * @return string
     */
    public function get_current_meta_description() {

        $meta_description = get_bloginfo( 'description' );
        if ( is_single() || is_page() || is_singular() ) {
            $post = Post::get_by_post_id( get_queried_object_id() );
            $meta_description = $post->get_seo_description();
        } elseif ( ( is_tax() || is_author() ) && get_queried_object()->description ) {
            $meta_description = get_queried_object()->description;
        }
        return $meta_description;
    }


    /**
     * Enqueue scripts and styles
     */
    public function action_wp_enqueue_scripts() {
        global $post;

        wp_enqueue_script( 'modernizr', get_template_directory_uri() . '/assets/dist/js/modernizr.js', [], '2.8.3', false );
        wp_enqueue_script( 'fastclick', get_template_directory_uri() . '/assets/dist/js/fastclick.js', [], '1.0', true );

        wp_enqueue_style( 'google-fonts', '//fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900,200italic,300italic,400italic,600italic,700italic,900italic|PT+Serif', [] );

        wp_enqueue_script( 'nativo', '//a.postrelease.com/serve/load.js?async=true' );
        wp_enqueue_script( 'boxter-funnl', 'https://boxter.co/f23.js', [], null );

        wp_register_script( 'soundcite', '//cdn.knightlab.com/libs/soundcite/latest/js/soundcite.min.js' );
        wp_register_style( 'soundcite', '//cdn.knightlab.com/libs/soundcite/latest/css/player.css' );

        wp_enqueue_script( 'pedestal-scripts', get_template_directory_uri() . '/assets/dist/js/pedestal.js', [ 'jquery', 'modernizr', 'fastclick' ], PEDESTAL_VERSION, true );

        $theme_name = wp_get_theme()->get_stylesheet();
        wp_enqueue_style( $theme_name . '-styles', get_stylesheet_directory_uri() . '/assets/dist/css/theme.css', [ 'google-fonts', 'soundcite' ], PEDESTAL_VERSION );

        if ( is_single() && is_a( $post, 'WP_Post' ) ) {
            $post_obj = Post::get_by_post_id( $post->ID );
            $post_published_ver = $post_obj->get_published_pedestal_ver();

            // Load SoundCite assets only if shortcode is present in the current post content
            if ( has_shortcode( $post->post_content, 'soundcite' ) ) {
                wp_enqueue_script( 'soundcite' );
                wp_enqueue_style( 'soundcite' );
            }

            // Load legacy stylesheet if current post was created before a specified Pedestal version
            foreach ( self::$fallback_stylesheet_vers as $ver ) {
                $ver_filename = str_replace( '.', '-', $ver );
                if ( empty( $post_published_ver ) || version_compare( $post_published_ver, $ver, '<=' ) ) {
                    wp_enqueue_style( 'pedestal-legacy-' . $ver_filename,
                        get_template_directory_uri() . '/assets/dist/css/theme-legacy-' . $ver_filename . '.css',
                        [ 'pedestal-styles' ],
                        PEDESTAL_VERSION
                    );
                }
            }
        }
    }

    /**
     * Set up the Twig environment
     */
    public function filter_get_twig( $twig ) {
        $twig->addFilter( 'addslashes', new \Twig_SimpleFilter( 'addslashes', 'addslashes' ) );
        return $twig;
    }

    /**
     * Filter Timber's default context value
     */
    public function filter_timber_context( $context ) {
        $context['menu'] = [
            'About'          => '/about',
            'Blog'           => PEDESTAL_BLOG_URL,
            'Jobs'           => '/jobs',
            'Press'          => '/press',
            'Advertising'    => '/advertising',
            'Terms of Use'   => '/terms-of-use',
            'Privacy Policy' => '/privacy-policy',
        ];

        $context['menu_off_canvas'] = [
            'Newsletter' => '/newsletter-signup',
            'Follow'     => '#',
        ];

        $context['copyright_text'] = 'Copyright &copy; ' . date( 'Y' ) . ' Spirited Media. All rights reserved.';

        if ( is_main_query() ) {
            global $wp_query;
            $context['pagination'] = Stream::get_pagination( $wp_query );
        }

        $spotlight = Pedestal()->get_spotlight_data();
        $context['spotlight'] = [
            'enabled'         => $spotlight['enabled'],
            'label'           => $spotlight['label'],
            'content'         => Pedestal()->get_spotlight_post(),
        ];

        $pinned = Pedestal()->get_pinned_data();
        $context['pinned'] = [
            'enabled'         => $pinned['enabled'],
            'content'         => Pedestal()->get_pinned_post(),
        ];

        $parsely = new \Pedestal\Objects\Parsely;
        $context['parsely'] = [];
        $context['parsely']['site'] = parse_url( home_url(), PHP_URL_HOST );
        $context['parsely']['data'] = $parsely->get_data();

        if ( is_singular( 'pedestal_story' ) ) {
            if ( is_active_sidebar( 'sidebar-story' ) ) {
                $context['sidebar'] = Timber::get_widgets( 'sidebar-story' );
            }
        } elseif ( is_singular( 'pedestal_hood' ) || is_post_type_archive( 'pedestal_hood' ) ) {
            if ( is_active_sidebar( 'sidebar-hood' ) ) {
                $context['sidebar'] = Timber::get_widgets( 'sidebar-hood' );
            }
        } elseif ( is_singular() && Types::is_entity( Post::get_post_type( get_queried_object_id() ) ) ) {
            if ( is_active_sidebar( 'sidebar-entity' ) ) {
                $context['sidebar'] = Timber::get_widgets( 'sidebar-entity' );
            }
        } else {
            if ( is_active_sidebar( 'sidebar-stream' ) ) {
                $context['sidebar'] = Timber::get_widgets( 'sidebar-stream' );
            }
        }

        if ( empty( $context['sidebar'] ) ) {
            ob_start();
            $context['sidebar'] = Timber::render( 'sidebar-default.twig', $context );
            ob_get_clean();
        }

        // Load some WP conditional functions as Timber context variables
        $conditionals = [
            'is_home',
            'is_single',
            'is_search',
            'is_feed',
        ];
        foreach ( $conditionals as $func ) {
            $context[ $func ] = function_exists( $func ) ? $func() : null;
        }

        return $context;
    }

    /**
     * Filter the title on single posts
     */
    public function filter_wp_title( $wp_title ) {

        if ( is_home() ) {
            return PEDESTAL_CITY_NAME . ' News, Local News, Breaking News - ' . PEDESTAL_BLOG_NAME;
        } elseif ( is_singular() ) {
            $obj = \Pedestal\Posts\Post::get_by_post_id( get_queried_object_id() );
            if ( ! is_object( $obj ) ) {
                return $wp_title;
            }
            return $obj->get_seo_title();
        } elseif ( is_search() ) {
            return 'Search - ' . PEDESTAL_BLOG_NAME;
        } elseif ( is_archive() ) {
            return self::get_archive_title() . ' — ' . PEDESTAL_BLOG_NAME;
        } else {
            return get_bloginfo( 'name' );
        }

    }

    /**
     * Filter template include to load our template
     */
    public function filter_template_include( $template ) {
        global $wp, $wp_query;

        $request = rtrim( $wp->request, '/' );
        switch ( $request ) {
            case 'promotional-content':
            case 'newsletter-signup':
            case 'unfollow-confirmation':
            case 'unsubscribe-confirmation':
                $template = get_template_directory() . '/page-' . $request . '.php';
                break;
        }

        if ( is_singular( 'pedestal_event' ) && isset( $wp_query->query_vars['ics'] ) ) {
            $template = get_template_directory() . '/single-pedestal_event-ics.php';
        }

        return $template;

    }

    /**
     * Get the Facebook Open Graph meta tags for this page
     */
    public function get_facebook_open_graph_meta_tags() {

        // Defaults
        $tags = [
            'og:site_name'        => get_bloginfo( 'name' ),
            'og:type'             => 'website',
            'og:title'            => get_bloginfo( 'name' ),
            'og:description'      => $this->get_current_meta_description(),
            'og:url'              => esc_url( home_url( Utils::get_request_uri() ) ),
        ];

        // Single posts
        if ( is_singular() ) {
            $obj = Post::get_by_post_id( get_queried_object_id() );
            $tags['og:title']          = $obj->get_facebook_open_graph_tag( 'title' );
            $tags['og:type']           = 'article';
            $tags['og:description']    = $obj->get_facebook_open_graph_tag( 'description' );
            $tags['og:url']            = $obj->get_facebook_open_graph_tag( 'url' );
            $tags['article:publisher'] = PEDESTAL_FACEBOOK_PAGE;

            if ( in_array( get_post_type( $obj->get_id() ), Types::get_editorial_post_types() ) ) {
                $tags['article:author'] = $obj->get_facebook_open_graph_tag( 'author' );
            }

            if ( $image = $obj->get_facebook_open_graph_tag( 'image' ) ) {
                $tags['og:image'] = $image;
            }
        }

        return $tags;

    }

    /**
     * Get the Twitter card meta tags for this page
     */
    public function get_twitter_card_meta_tags() {

        // Defaults
        $tags = [
            'twitter:card'        => 'summary',
            'twitter:site'        => '@' . PEDESTAL_TWITTER_USERNAME,
            'twitter:title'       => get_bloginfo( 'name' ),
            'twitter:description' => $this->get_current_meta_description(),
            'twitter:url'         => esc_url( home_url( Utils::get_request_uri() ) ),
        ];

        // Single posts
        if ( is_singular() ) {
            $obj = Post::get_by_post_id( get_queried_object_id() );
            $tags['twitter:title'] = $obj->get_twitter_card_tag( 'title' );
            $tags['twitter:description'] = $obj->get_twitter_card_tag( 'description' );
            $tags['twitter:url'] = $obj->get_twitter_card_tag( 'url' );

            if ( $image = $obj->get_twitter_card_tag( 'image' ) ) {
                $tags['twitter:image'] = $image;
            }
        }

        return $tags;

    }

    /**
     * Retrieve the archive title based on the queried object.
     *
     * Based on `get_the_archive_title()` in WordPress 4.1.0
     *
     * @return string Archive title.
     */
    public static function get_archive_title() {
        if ( is_category() ) {
            $title = single_cat_title( '', false );
        } elseif ( is_tag() ) {
            $title = sprintf( __( 'Tag: %s' ), single_tag_title( '', false ) );
        } elseif ( is_author() ) {
            $title = sprintf( __( '%s' ), get_the_author() );
        } elseif ( is_year() ) {
            $title = sprintf( __( 'Year: %s' ), get_the_date( _x( 'Y', 'yearly archives date format' ) ) );
        } elseif ( is_month() ) {
            $title = sprintf( __( 'Month: %s' ), get_the_date( _x( 'F Y', 'monthly archives date format' ) ) );
        } elseif ( is_day() ) {
            $title = sprintf( __( 'Day: %s' ), get_the_date( _x( 'F j, Y', 'daily archives date format' ) ) );
        } elseif ( is_post_type_archive() ) {
            $title = post_type_archive_title( '', false );
        } elseif ( is_tax() ) {
            $tax = get_taxonomy( get_queried_object()->taxonomy );
            /* translators: 1: Taxonomy singular name, 2: Current taxonomy term */
            $title = sprintf( __( '%1$s: %2$s' ), $tax->labels->singular_name, single_term_title( '', false ) );
        } else {
            $title = __( 'Archives' );
        }

        return $title;
    }
}
