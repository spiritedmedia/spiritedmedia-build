<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use \Pedestal\Posts\Post;
use \Pedestal\Registrations\Post_Types\Types;

class Scripts_Styles {


    /**
     * Name of the DFP script handler
     */
    private $dfp_load_script_handle = 'dfp-load';

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
            $instance->setup_filters();
        }
        return $instance;
    }

    /**
     * Hook in to WordPress via actions
     */
    public function setup_actions() {
        add_action( 'admin_init', [ $this, 'action_admin_init' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'action_wp_enqueue_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'action_wp_enqueue_scripts_dequeue_tablepress' ], 11 );
        add_action( 'wp', [ $this, 'action_wp_dequeue_tablepress' ] );
    }

    /**
     * Hook in to WordPress via filters
     */
    public function setup_filters() {

        // Modify ?ver query string for development environments to bust cache
        if ( defined( 'WP_ENV' ) && 'development' === strtolower( WP_ENV ) ) {
            add_filter( 'script_loader_src', [ $this, 'filter_cache_busting_file_src' ], 10 );
            add_filter( 'style_loader_src', [ $this, 'filter_cache_busting_file_src' ], 10 );
        } else {
            add_filter( 'script_loader_src', [ $this, 'filter_remove_ver_query_string' ], 10 );
            add_filter( 'style_loader_src', [ $this, 'filter_remove_ver_query_string' ], 10 );
        }

        // 3rd party hosted JavaScript should have async set so they don't block
        // our JavaScript from functioning if 3rd party scripts fail to load.
        add_filter( 'script_loader_tag', function( $script_tag = '', $handle = '' ) {
            $async_handles = [ 'soundcite', 'instagram-embed' ];
            if ( ! in_array( $handle, $async_handles ) ) {
                return $script_tag;
            }
            return str_replace( ' src', ' async="async" src', $script_tag );
        }, 10, 2 );

        // Load our own chosen version of jQuery
        add_filter( 'script_loader_tag', [ $this, 'filter_script_loader_tag_reload_jquery' ], 10, 3 );

        // Modify dfp-loader.js <script> element
        add_filter( 'script_loader_tag', [ $this, 'filter_script_loader_tag_modify_dfp_loader' ], 10, 3 );

        add_filter( 'timber_context', function( $context ) {
            $context['local_storage_cookie_script_url'] = PEDESTAL_DIST_DIRECTORY_URI . '/js/globalLocalStorageCookie.js';
            return $context;
        } );
    }

    public function action_admin_init() {
        add_editor_style( PEDESTAL_THEME_DIST_DIRECTORY_URI . '/css/editor-style.css' );
    }
    /**
     * Enqueue scripts and styles
     */
    public function action_wp_enqueue_scripts() {
        $post = get_post();

        $google_fonts_string = 'Overpass:300,300i,400,400i,600,700,700i';
        $google_fonts_string = apply_filters( 'pedestal_google_fonts_string', $google_fonts_string );
        $google_fonts_src    = 'https://fonts.googleapis.com/css?family=' . $google_fonts_string;
        wp_register_style( 'google-fonts', $google_fonts_src, [], null );

        // Functionality-specific assets
        wp_register_script( 'soundcite', 'https://cdn.knightlab.com/libs/soundcite/latest/js/soundcite.min.js', [], null, true );
        wp_register_style( 'soundcite', 'https://cdn.knightlab.com/libs/soundcite/latest/css/player.css', [], null );

        if ( is_single() && is_a( $post, 'WP_Post' ) ) {
            $post_obj           = Post::get( $post->ID );
            $post_published_ver = $post_obj->get_published_pedestal_ver();

            // Load SoundCite assets only if shortcode is present in the current post content
            if ( has_shortcode( $post->post_content, 'soundcite' ) ) {
                // Needs to be enqueued before the theme CSS file
                wp_enqueue_script( 'soundcite' );
                wp_enqueue_style( 'soundcite' );
            }
        }

        // Core site assets
        wp_enqueue_style( PEDESTAL_THEME_NAME . '-styles', PEDESTAL_THEME_DIST_DIRECTORY_URI . '/css/theme.css', [ 'google-fonts' ], PEDESTAL_VERSION );
        wp_enqueue_script( 'pedestal-scripts', PEDESTAL_DIST_DIRECTORY_URI . '/js/theme.js', [ 'jquery' ], PEDESTAL_VERSION, true );

        // Ensure Pedestal variables are defined on the frontend globally
        wp_localize_script( 'pedestal-scripts', 'PedVars', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ] );

        // Advertising
        wp_enqueue_script( $this->dfp_load_script_handle, PEDESTAL_DIST_DIRECTORY_URI . '/js/dfp-load.js', [ 'jquery' ], PEDESTAL_VERSION );
        if ( isset( $_GET['show-ad-units'] ) ) {
            wp_enqueue_script( 'dfp-placeholders', PEDESTAL_DIST_DIRECTORY_URI . '/js/dfp-placeholders.js', [ 'jquery' ], PEDESTAL_VERSION );
        }

        wp_register_script( 'pedestal-footnotes', PEDESTAL_DIST_DIRECTORY_URI . '/js/pedestal-footnotes.js', [ 'jquery' ], PEDESTAL_VERSION, true );
    }

    /**
     * Prevent default TablePress styles from loading on all requests
     */
    function action_wp_enqueue_scripts_dequeue_tablepress() {
        if ( $this->has_tablepress_shortcode() ) {
            return;
        }
        wp_dequeue_style( 'tablepress-default' );
    }

    /**
     * Prevent TablePress Responsive Table styles from loading on all requests
     */
    function action_wp_dequeue_tablepress() {
        if ( $this->has_tablepress_shortcode() ) {
            return;
        }
        remove_action( 'wp_enqueue_scripts', [ 'TablePress_Responsive_Tables', 'enqueue_css_files' ] );
        remove_action( 'wp_print_scripts', [ 'TablePress_Responsive_Tables', 'enqueue_css_files_flip' ] );
    }

    /**
     * Replace the `ver` query arg with the file's last modified date
     *
     * @param  string $src URL to a file
     * @return string      Modified URL to a file
     */
    public function filter_cache_busting_file_src( $src = '' ) {
        global $wp_scripts;
        // If $wp_scripts hasn't been initialized then bail
        if ( ! $wp_scripts instanceof \WP_Scripts ) {
            return;
        }
        $base_url = apply_filters( 'cache_busting_path_base_url', $wp_scripts->base_url, $src );
        // Check if script lives on this domain. Can't rewrite external scripts, they won't work.
        if ( ! strstr( $src, $base_url ) ) {
            return $src;
        }

        // Remove the 'ver' query var: ?ver=0.1
        $src   = remove_query_arg( 'ver', $src );
        $regex = '/' . preg_quote( $base_url, '/' ) . '/';
        $path  = preg_replace( $regex, '', $src );
        // If the folder starts with wp- then we can figure out where it lives on the filesystem.
        if ( strstr( $path, '/wp-' ) ) {
            $file = untrailingslashit( ABSPATH ) . $path;
        }
        if ( ! file_exists( $file ) ) {
            return $src;
        }

        $time_format     = apply_filters( 'cache_busting_path_time_format', 'Y-m-d_G-i' );
        $modified_time   = filemtime( $file );
        $timezone_string = get_option( 'timezone_string' );
        $dt              = new \DateTime( '@' . $modified_time );
        $dt->setTimeZone( new \DateTimeZone( $timezone_string ) );
        $time = $dt->format( $time_format );
        $src  = add_query_arg( 'ver', $time, $src );
        return $src;
    }

    /**
     * Remove ver query string from static asset URLs if they contain assets/dist/VERSION/
     *
     * @param  string $src URL to a file
     * @return string      Modified URL to a file
     */
    public function filter_remove_ver_query_string( $src = '' ) {
        if ( strstr( $src, 'assets/dist/' . PEDESTAL_VERSION . '/' ) ) {
            $src = remove_query_arg( 'ver', $src );
        }
        return $src;
    }

    /**
     * Conditional for determining if a post contains the TablePress shortcode [table]
     * @param  integer $post_id  ID of Post to check for shortcode
     */
    public function has_tablepress_shortcode( $post_id = 0 ) {
        if ( is_single() ) {
            $post = get_post( $post_id );
            if ( ! is_object( $post ) || ! property_exists( $post, 'post_content' ) ) {
                return false;
            }
            if ( has_shortcode( $post->post_content, 'table' ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Serve jQuery jQuery 2.x or 1.x depending on browser version
     *
     * @param string $script_element     <script> element to be rendered
     * @param string $handle             script handle that was registered
     * @param string $script_src         src sttribute of the <script>
     * @return string                    New <script> element
     */
    public function filter_script_loader_tag_reload_jquery( $script_element, $handle, $script_src ) {
        if ( 'jquery-migrate' == $handle ) {
            return '';
        }

        if ( 'jquery-core' == $handle || 'jquery' == $handle ) {
            $new_script_element = '';
            // jQuery 1.x gets served to IE8 and below...
            $new_script_element .= '<!--[if lt IE 9]>';
            $new_script_element .= $script_element;
            $new_script_element .= '<![endif]-->';
            // jQuery 2.x gets served to everyone else...
            $jquery2_path        = PEDESTAL_DIST_DIRECTORY_URI . '/js/vendor/jquery.min.js';
            $jquery2_src         = apply_filters( 'script_loader_src', $jquery2_path );
            $new_script_element .= '<!--[if (gte IE 9) | (!IE)]><!-->';
            $new_script_element .= "<script type='text/javascript' src='" . $jquery2_src . "'></script>";
            $new_script_element .= '<!--<![endif]-->';
            return $new_script_element;
        }

        return $script_element;
    }

    /**
     * Pass the DFP ID to dfp-load.js via a data attribute
     *
     * @param string $script_element     <script> element to be rendered
     * @param string $handle             script handle that was registered
     * @param string $script_src         src sttribute of the <script>
     * @return string                    New <script> element
     */
    public function filter_script_loader_tag_modify_dfp_loader( $script_element, $handle, $script_src ) {
        if ( $this->dfp_load_script_handle == $handle && defined( 'PEDESTAL_DFP_ID' ) ) {

            // Figure out the DFP Path for hierarchical ad targeting
            $dfp_path = PEDESTAL_DFP_ID . '/spirited.media/' . PEDESTAL_DFP_SITE;
            if ( is_home() ) {
                $dfp_path .= '/homepage';
            } elseif ( is_singular() ) {
                $terms = wp_get_object_terms( get_the_ID(), 'pedestal_category' );
                if ( ! empty( $terms[0] ) ) {
                    $ad_category = get_term_meta( $terms[0]->term_id, 'ad-category', true );
                    if ( $ad_category ) {
                        $dfp_path .= '/' . sanitize_title( $ad_category );
                    }
                }
            } elseif ( is_tax( 'pedestal_category' ) ) {
                $term = get_queried_object();
                if ( ! empty( $term->term_id ) ) {
                    $ad_category = get_term_meta( $term->term_id, 'ad-category', true );
                    if ( $ad_category ) {
                        $dfp_path .= '/' . sanitize_title( $ad_category );
                    }
                }
            }

            // Figure out the DFP Article ID for targeting ads by specific post
            $dfp_article_id = '';
            if ( is_singular() && ! Types::is_cluster( get_post_type() ) ) {
                $dfp_article_id = get_the_ID();
            }

            // Figure out topic targeting
            $tag_permalinks = [];
            if ( is_singular() ) {
                if ( Types::is_cluster( get_post_type() ) ) {
                    $tag_permalinks[] = get_permalink();
                } else {
                    $ped_post = Post::get( get_the_ID() );
                    if ( Types::is_entity( $ped_post ) ) {
                        $clusters = $ped_post->get_clusters([
                            'include_stories' => true,
                        ]);
                        if ( ! empty( $clusters ) ) {
                            foreach ( $clusters as $cluster ) {
                                $tag_permalinks[] = $cluster->get_permalink();
                            }
                        }
                    }
                }
            }

            // Transform cluster permalinks into the cluster slug
            // https://denverite.com/topics/foo/ ==> foo
            $tags       = array_map( function( $permalink ) {
                $path  = str_replace( get_site_url(), '', $permalink );
                $parts = explode( '/', $path );
                return $parts[2];
            }, $tag_permalinks);
            $dfp_topics = implode( ' ', $tags );

            $new_attrs      = sprintf(
                '
                    id="%s"
                    data-dfp-id="%s"
                    data-dfp-path="%s"
                    data-dfp-article-id="%s"
                    data-dfp-site="%s"
                    data-dfp-topics="%s"
                ',
                esc_attr( $this->dfp_load_script_handle ),
                esc_attr( PEDESTAL_DFP_ID ),
                esc_attr( $dfp_path ),
                esc_attr( $dfp_article_id ),
                esc_attr( PEDESTAL_DFP_SITE ),
                esc_attr( $dfp_topics )
            );
            $script_element = str_replace( 'src=', $new_attrs . ' src=', $script_element );
        }
        return $script_element;
    }
}
