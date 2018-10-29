<?php

namespace Pedestal;


use Timber\Timber;

use function Pedestal\Pedestal;
use Pedestal\Objects\User;
use Pedestal\Posts\{
    Newsletter,
    Post
};
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Utils\Utils;

class Frontend {

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
     * Set up actions used on the frontend
     */
    private function setup_actions() {
        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts' ] );
        add_action( 'wp_head', [ $this, 'action_wp_head_meta_tags' ] );
        add_action( 'wp_head', [ $this, 'action_add_comscore_tracking_pixel' ] );
    }

    /**
     * Set up filters used on the frontend
     */
    private function setup_filters() {

        add_filter( 'wp_title', [ $this, 'filter_wp_title' ] );

        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );

        add_filter( 'template_include', [ $this, 'filter_template_include' ] );

        add_filter( 'body_class', function( $body_classes ) {

            global $wp_query;

            if ( is_page() || is_author() ) {
                $body_classes[] = 'full-width';
            }

            if ( is_user_logged_in() && isset( $_GET['debug-ga'] ) ) {
                $body_classes[] = 'js-debug-ga';
            }

            if ( is_single() ) {
                $post_type = get_post_type();
                $body_classes[] = 'single-' . Utils::remove_name_prefix( $post_type );
                $is_entity = Types::is_entity( $post_type );
                $is_cluster = Types::is_cluster( $post_type );

                if ( $is_cluster ) {
                    $body_classes[] = 'single-cluster';
                } elseif ( $is_entity ) {
                    $body_classes[] = 'single-entity';
                }
            }

            return $body_classes;
        });

        add_filter( 'the_content', [ $this, 'filter_the_content_prepare_footnotes' ] );
        add_filter( 'the_footnotes', [ $this, 'filter_the_footnotes_render' ], 10, 2 );
        add_filter( 'nav_menu_link_attributes', function( $attrs = [], $item, $args = [], $depth ) {
            $attrs['data-ga-category'] = 'sidebar';
            $attrs['data-ga-label'] = 'link';
            return $attrs;
        }, 10, 4 );

        add_filter( 'robots_txt', [ $this, 'filter_robots_txt' ] );

        // Let post password cookies expire in 15 minutes to minimize the amount
        // of time the user will browse the site while bypassing the cache
        add_filter( 'post_password_expires', function( $expires ) {
            return time() + 15 * MINUTE_IN_SECONDS;
        } );
    }


    //
    // Actions
    // =========================================================================


    /**
     * Modify the main query
     */
    public function action_pre_get_posts( $query ) {

        // Exclude all password protected posts from queries
        if ( ! $query->is_singular() && ! $query->is_admin() ) {
            add_filter( 'posts_where', function( $where ) {
                global $wpdb;
                return $where .= " AND {$wpdb->posts}.post_password = '' ";
            } );
        }

        if ( ! $query->is_main_query() ) {
            return;
        }

        // Exclude posts that are set to be hidden in streams
        if (
            $query->is_home() ||
            ! empty( $query->query['pedestal_category'] ) &&
            // If post__in is set then we don't need post__not_in
            empty( $query->get( 'post__in' ) )
        ) {

            $post_not_in  = $query->get( 'post__not_in' );
            $excluded_ids = self::get_post_ids_excluded_from_home_stream();
            $post_not_in  = array_merge( $post_not_in, $excluded_ids );

            $query->set( 'post__not_in', $post_not_in );
        }

        if ( $query->is_home() ) {
            $query->set( 'post_type', Types::get_entity_post_types() );
            $query->set( 'posts_per_page', 20 );
        }

        if ( $query->is_feed() ) {
            if ( $query->is_post_type_archive() ) {
                $post_type_name = Types::get_post_type_name( $query->get( 'post_type' ) );
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
            if (
                $query->is_post_type_archive( Types::get_cluster_post_types() )
            ) {
                $query->set( 'posts_per_page', 50 );
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

        $tags = array_merge( [
            'description' => $meta_description,
        ], $facebook_tags, $twitter_tags );

        foreach ( $tags as $name => $value ) :
            switch ( $name ) :
                // Include meta tag for original content authors if they've provided their Facebook profile URL
                case 'article:author':
                    if ( is_array( $value ) ) :
                        foreach ( $value as $author_data ) {
                            $profile = $author_data['profile'];
                            if ( ! empty( $profile ) ) {
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
            endswitch;
        endforeach;

        // Add a meta refresh to the homepage
        if ( is_home() && 'live' === PEDESTAL_ENV ) {
            $refresh = 0;
            if ( ! empty( $_GET['refresh'] ) ) {
                $refresh = intval( $_GET['refresh'] );
            }
            $refresh++;
            echo sprintf( '<meta http-equiv="refresh" content="600;url=?refresh=%d" />',
                intval( $refresh )
            );
        }

    }

    public function action_add_comscore_tracking_pixel() {
        if ( ! defined( 'PEDESTAL_COMSCORE_ID' ) || ! PEDESTAL_COMSCORE_ID ) {
            return;
        }
        Timber::render( 'partials/analytics/comscore.twig', [
            'comscore_id' => PEDESTAL_COMSCORE_ID,
        ] );
    }


    //
    // Filters
    // =========================================================================


    public function filter_robots_txt( $txt ) {
        ob_start();
        Timber::render( 'partials/robots-txt.twig', [] );
        $txt .= ob_get_clean();
        return $txt;
    }

    /**
     * Filter Timber's default context value
     */
    public function filter_timber_context( $context ) {
        $footer_social_icons = [
            [
                'url'      => $context['site']->social['instagram_url'],
                'icon'     => 'instagram',
                'sr_label' => '@' . PEDESTAL_INSTAGRAM_USERNAME . ' on Instagram',
                'ga_label' => 'Instagram',
            ],
            [
                'url'      => $context['site']->social['facebook_url'],
                'icon'     => 'facebook',
                'sr_label' => PEDESTAL_BLOG_NAME . ' on Facebook',
                'ga_label' => 'Facebook',
            ],
            [
                'url'      => $context['site']->social['twitter_url'],
                'icon'     => 'twitter',
                'sr_label' => '@' . PEDESTAL_TWITTER_USERNAME . ' on Twitter',
                'ga_label' => 'Twitter',
            ],
        ];
        if ( PEDESTAL_ENABLE_FOOTER_EMAIL_ICON ) {
            $email_icon = [
                'url'      => 'mailto:' . PEDESTAL_EMAIL_TIPS,
                'icon'     => 'envelope',
                'sr_label' => 'Get in touch!',
                'ga_label' => 'Email',
            ];
            array_unshift( $footer_social_icons, $email_icon );
        }
        $context['footer_social_icons'] = $footer_social_icons;

        $context['footer_menu'] = apply_filters( 'pedestal_footer_menu', [
            'About Us'       => '/about/',
            'Blog'           => PEDESTAL_BLOG_URL,
            'Jobs'           => '/jobs/',
            'Press'          => '/press/',
            'Advertising'    => '/advertising/',
            'Terms of Use'   => '/terms-of-use/',
            'Privacy Policy' => '/privacy-policy/',
            'Search'         => '/?s=',
        ] );

        $context['latest_newsletter'] = Newsletter::get_latest_newsletter_link();
        $context['copyright_text'] = 'Copyright &copy; ' . date( 'Y' ) . ' Spirited Media';

        if ( is_search() ) {
            $context['search_query'] = get_search_query();
        }

        if ( wp_get_current_user() ) {
            $context['current_user'] = new User( wp_get_current_user() );
        } else {
            $context['current_user'] = false;
        }

        if ( is_singular() ) :
            $post = Post::get( get_queried_object_id() );
            if ( is_a( $post, '\\Pedestal\\Posts\\Post' ) ) :
                $post_type = $post->get_post_type();

                if ( Types::is_cluster( $post_type ) ) {
                    $context['is_cluster'] = true;
                }

                if ( 'pedestal_story' == $post_type ) {
                    $context['is_story'] = true;
                }
            endif;
        endif;

        if ( is_404() ) {
            $context['e404_links'] = [
                'Search' => get_site_url() . '/?s=',
                'Home'   => get_site_url(),
            ];
        }

        $context['is_page_donate'] = is_page( 'support-our-work' ) ?: false;

        $context['sidebar_ad'] = '<li class="widget widget_pedestal_dfp_rail_right">' . Adverts::render_sidebar_ad_unit() . '</li>';

        // Load some WP conditional functions as Timber context variables
        $conditionals = [
            'is_home',
            'is_single',
            'is_page',
            'is_search',
            'is_feed',
            'is_archive',
            'is_tax',
            'is_post_type_archive',
        ];
        foreach ( $conditionals as $func ) {
            $context[ $func ] = function_exists( $func ) ? $func() : null;
        }

        return $context;
    }

    /**
     * Filter the page title
     */
    public function filter_wp_title( $wp_title ) {

        $title = '';
        if ( is_home() ) {
            return PEDESTAL_HOMEPAGE_TITLE;
        } elseif ( is_singular() ) {
            $title = $wp_title;
            $ped_post = Post::get( get_queried_object_id() );
            if ( Types::is_post( $ped_post ) ) {
                $title = $ped_post->get_seo_title();
            }
        } elseif ( is_search() ) {
            $title = 'Search';
        } elseif ( is_archive() ) {
            $title = self::get_archive_title();
        }

        if ( ! $title ) {
            return PEDESTAL_BLOG_NAME;
        }

        return $title . ' - ' . PEDESTAL_BLOG_TAGLINE;
    }

    /**
     * Filter template include to load our template
     *
     * @param  string $template Path to a PHP template
     * @return string          Possibly modified template path
     */
    public function filter_template_include( $template = '' ) {
        global $wp, $wp_query;

        if ( is_singular( 'pedestal_event' ) && isset( $wp_query->query_vars['ics'] ) ) {
            $template = get_template_directory() . '/single-pedestal_event-ics.php';
        }

        return $template;
    }

    /**
     * Process the post content for any generated footnotes
     */
    public function filter_the_content_prepare_footnotes( $content ) {
        $post_id = get_the_ID();

        // Need to correct wpautop() which smart-quoteify's the " in the numoffset argument.
        $content = preg_replace( '/numoffset=&#8221;(\d+)&#8243;/i', 'numoffset="$1"', $content );

        // Microsoft has some weird space characters that Mac/Unix systems don't
        // have. What the next line does is replace the weird space characters
        // with a real space character which makes the regex work...
        $content = str_replace( '[ref ', '[ref ', $content );

        $start = 1;
        $notes = [];

        // Given `[0. numoffset="5" This is a footnote]`
        //
        // `$matches[0]` = The whole match including the square brackets: `[0. numoffset="5" This is a footnote]`
        // `$matches[4]` = numoffset value: 5
        // `$matches[5]` = The footnote text: This is a footnote
        if ( preg_match_all( '/\[(\d+\.((\s+)?numoffset="(\d+)+")? (.*?))\]/s', $content, $matches ) ) :
            foreach ( $matches[0] as $index => $target ) {
                $offset_value = (int) $matches[4][ $index ];
                $text = trim( $matches[5][ $index ] );

                // Footnotes that have [ or ] in the text break. Use double
                // curly quotes as an escape to workaround this.
                $text = str_replace( '{{', '[', $text );
                $text = str_replace( '}}', ']', $text );

                if ( $offset_value > 0 ) {
                    $start = $offset_value;
                }

                $notes[] = $text;
            }

            $n = $start;
            foreach ( $matches[0] as $index => $target ) {
                $context = [
                    'token' => $post_id . '-' . $n,
                    'num'   => $n,
                ];
                $replacement  = '';
                ob_start();
                $replacement = Timber::render( 'partials/footnotes/footnote-link.twig', $context );
                ob_get_clean();
                $content = str_replace( $target, $replacement, $content );
                $n++;
            }

            $post_obj = Post::get( $post_id );
            if ( is_a( $post_obj, '\\Pedestal\\Posts\\Post' ) && method_exists( $post_obj, 'set_footnotes_generated' ) ) {
                $post_obj->set_footnotes_generated( $notes, $start );
            }

            // Workaround for wpautop() bug. Otherwise it sometimes inserts an
            // opening <p> but not the closing </p>. There are a bunch of open
            // wpautop tickets. See 4298 and 7988 in particular.
            $content .= "\n\n";
        endif;

        return $content ;
    }

    /**
     * Filter the footnotes field below post content to include generated notes
     */
    public function filter_the_footnotes_render( $footnotes, $post_id ) {
        $post = Post::get( $post_id );
        if (
            is_a( $post, '\\Pedestal\\Posts\\Post' )
            && method_exists( $post, 'get_footnotes_generated_notes' )
            && preg_match_all( '/<sup class=\"footnote/s', $post->get_the_content() )
        ) {
            $context = [
                'post_id' => $post_id,
                'num'     => $post->get_footnotes_generated_start(),
                'items'   => $post->get_footnotes_generated_notes(),
            ];
            ob_start();
            $footnotes .= Timber::render( 'partials/footnotes/footnotes-list.twig', $context );
            ob_get_clean();
        }
        wp_enqueue_script( 'pedestal-footnotes' );
        return $footnotes;
    }


    //
    // General Methods
    // =========================================================================


    /**
     * Get meta description for current page
     *
     * @return string
     */
    public function get_current_meta_description() {
        if ( is_single() ) {
            $post = Post::get( get_queried_object_id() );
            if ( Types::is_post( $post ) ) {
                return $post->get_seo_description();
            }
        } elseif ( ( is_tax() || is_author() ) && get_queried_object()->description ) {
            return get_queried_object()->description;
        }
        return get_bloginfo( 'description' );
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
            'og:image'            => get_stylesheet_directory_uri() . '/assets/images/logos/logo-icon-placeholder.png',
        ];

        // Single posts
        if ( is_singular() ) {
            $obj = Post::get( get_queried_object_id() );
            $tags['og:title']          = $obj->get_facebook_open_graph_tag( 'title' );
            $tags['og:type']           = 'article';
            $tags['og:description']    = $obj->get_facebook_open_graph_tag( 'description' );
            $tags['og:url']            = $obj->get_facebook_open_graph_tag( 'url' );
            $tags['article:publisher'] = PEDESTAL_FACEBOOK_PAGE;

            if ( in_array( get_post_type( $obj->get_id() ), Types::get_original_post_types() ) ) {
                $tags['article:author'] = $obj->get_facebook_open_graph_tag( 'author' );
            }

            $image = $obj->get_facebook_open_graph_tag( 'image' );
            if ( $image ) {
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
            'twitter:image'       => get_stylesheet_directory_uri() . '/assets/images/logos/logo-icon-placeholder.png',
        ];

        // Single posts
        if ( is_singular() ) {
            $post_obj = Post::get( get_queried_object_id() );
            if ( Types::is_post( $post_obj ) ) {
                $tags['twitter:title'] = $post_obj->get_twitter_card_tag( 'title' );
                $tags['twitter:url'] = $post_obj->get_twitter_card_tag( 'url' );

                $post_description = $post_obj->get_twitter_card_tag( 'description' );
                if ( ! empty( $post_description ) ) {
                    $tags['twitter:description'] = $post_description;
                }

                $image = $post_obj->get_twitter_card_tag( 'image' );
                if ( $image ) {
                    $tags['twitter:image'] = $image;
                }
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
        $paged = get_query_var( 'paged' ) >= 2;
        $title = $paged ? 'More ' : '';

        if ( is_author() ) {
            return sprintf( __( '%s' ), get_the_author() );
        } elseif ( is_post_type_archive() ) {
            $type = Utils::remove_name_prefix( get_query_var( 'post_type' ) );
            switch ( $type ) {
                case 'factcheck':
                case 'event':
                case 'newsletter':
                    // Only some post types deserve a "More" prefix on paged archives
                    $title .= post_type_archive_title( '', false );
                    break;
                default:
                    $title = post_type_archive_title( '', false );
                    break;
            }
        } elseif ( is_tax() ) {
            $taxonomy_singular_name = '';
            $term_title = single_term_title( '', false );
            if ( isset( get_queried_object()->taxonomy ) ) {
                $tax = get_taxonomy( get_queried_object()->taxonomy );
                if ( 'pedestal_category' === $tax->name ) {
                    $title .= $term_title;
                } elseif ( isset( $tax->labels->singular_name ) ) {
                    $taxonomy_singular_name = $tax->labels->singular_name;
                    $title .= sprintf(
                        __( '%1$s: %2$s' ),
                        $taxonomy_singular_name,
                        $term_title
                    );
                }
            }
        } elseif ( is_archive() && 'originals' == get_query_var( 'pedestal_originals' ) ) {
            $title .= PEDESTAL_BLOG_NAME_SANS_THE . ' Originals';
        } else {
            $title .= 'Archives';
        }

        return $title;
    }

    /**
     * Get list of IDs that should be excluded from the home stream
     *
     * @return array List of post IDs that should be excluded from a query
     */
    public static function get_post_ids_excluded_from_home_stream( $force_refresh = false ) {
        $option_name = 'exclude_from_home_stream';
        $ids = get_option( $option_name );
        if ( ! empty( $ids ) && ! $force_refresh ) {
            return $ids;
        }

        $args = [
            'meta_query' => [
                [
                    'key'     => $option_name,
                    'value'   => 'hide',
                    'compare' => '==',
                ],
            ],
            'post_type'      => Types::get_pedestal_post_types(),
            'post_status'    => 'publish',
            'posts_per_page' => 500,
            'fields'         => 'ids',
        ];
        $query = new \WP_Query( $args );
        update_option( $option_name, $query->posts );
        return $query->posts;
    }
}
