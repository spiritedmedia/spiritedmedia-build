<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use \Pedestal\Posts\Post;

class Scripts_Styles {
    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            // Late static binding (PHP 5.3+)
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
        }

        // 3rd party hosted JavaScript should have async set so they don't block
        // our JavaScript from functioning if 3rd party scripts fail to load.
        add_filter( 'script_loader_tag', function( $script_tag = '', $handle = '' ) {
            $whitelisted_handles = [ 'boxter-funnl', 'nativo', 'soundcite' ];
            if ( ! in_array( $handle, $whitelisted_handles ) ) {
                return $script_tag;
            }
            return str_replace( ' src', ' async="async" src', $script_tag );
        }, 10, 2 );
    }

    /**
     * Enqueue scripts and styles
     */
    public function action_wp_enqueue_scripts() {
        $post = get_post();

        wp_register_style( 'google-fonts', 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900,200italic,300italic,400italic,600italic,700italic,900italic|PT+Serif', [], null );

        // Functionality-specific assets
        wp_register_script( 'soundcite', 'https://cdn.knightlab.com/libs/soundcite/latest/js/soundcite.min.js', [], null, $in_footer = true );
        wp_register_style( 'soundcite', 'https://cdn.knightlab.com/libs/soundcite/latest/css/player.css', [], null );

        if ( is_single() && is_a( $post, 'WP_Post' ) ) {
            $post_obj = Post::get_by_post_id( $post->ID );
            $post_published_ver = $post_obj->get_published_pedestal_ver();

            // Load SoundCite assets only if shortcode is present in the current post content
            if ( has_shortcode( $post->post_content, 'soundcite' ) ) {
                // Needs to be enqueued before the theme CSS file
                wp_enqueue_script( 'soundcite' );
                wp_enqueue_style( 'soundcite' );
            }
        }

        // Core site assets
        $theme_name = wp_get_theme()->get_stylesheet();
        wp_enqueue_style( $theme_name . '-styles', get_stylesheet_directory_uri() . '/assets/dist/css/theme.css', [ 'google-fonts' ], PEDESTAL_VERSION );
        wp_enqueue_script( 'pedestal-scripts', get_template_directory_uri() . '/assets/dist/js/pedestal.js', [ 'jquery' ], PEDESTAL_VERSION, true );

        // Advertising
        wp_enqueue_script( 'dfp-load', get_template_directory_uri() . '/assets/dist/js/dfp-load.js', [ 'jquery' ], PEDESTAL_VERSION );
        wp_enqueue_script( 'nativo', 'https://s.ntv.io/serve/load.js', [], null, $in_footer = true );
        if ( isset( $_GET['show-ad-units'] ) ) {
            wp_enqueue_script( 'dfp-placeholders', get_template_directory_uri() . '/assets/dist/js/dfp-placeholders.js', [ 'jquery' ], PEDESTAL_VERSION );
        }

        wp_register_script( 'pedestal-footnotes', get_template_directory_uri() . '/assets/dist/js/pedestal-footnotes.js', [ 'jquery' ],  PEDESTAL_VERSION, $in_footer = true );
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
     * @param  string $src URL to a file
     * @return string      Modified URL to a file
     */
    public function filter_cache_busting_file_src( $src = '' ) {
        global $wp_scripts;
        // If $wp_scripts hasn't been initialized
        if ( ! $wp_scripts instanceof \WP_Scripts ) {
            $wp_scripts = new \WP_Scripts();
        }
        $base_url = apply_filters( 'cache_busting_path_base_url', $wp_scripts->base_url, $src );
        // Check if script lives on this domain. Can't rewrite external scripts, they won't work.
        if ( ! strstr( $src, $base_url ) ) {
            return $src;
        }

        // Remove the 'ver' query var: ?ver=0.1
        $src = remove_query_arg( 'ver', $src );
        $regex = '/' . preg_quote( $base_url, '/' ) . '/';
        $path = preg_replace( $regex, '', $src );
        // If the folder starts with wp- then we can figure out where it lives on the filesystem.
        if ( strstr( $path, '/wp-' ) ) {
            $file = untrailingslashit( ABSPATH ) . $path;
        }
        if ( ! file_exists( $file ) ) {
            return $src;
        }

        $time_format = apply_filters( 'cache_busting_path_time_format', 'Y-m-d_G-i' );
        $modified_time = filemtime( $file );
        $timezone_string = get_option( 'timezone_string' );
        $dt = new \DateTime( '@' . $modified_time );
        $dt->setTimeZone( new \DateTimeZone( $timezone_string ) );
        $time = $dt->format( $time_format );
        $src = add_query_arg( 'ver', $time, $src );
        return $src;
    }

    /**
     * Conditional for determining if a post contains the TablePress shortcode [table]
     * @param  integer $post_id  ID of Post to check for shortcode
     */
    public function has_tablepress_shortcode( $post_id = 0 ) {
        if ( is_single() ) {
            $post = get_post( $post_id );
            if ( has_shortcode( $post->post_content, 'table' ) ) {
                return true;
            }
        }
        return false;
    }
}
