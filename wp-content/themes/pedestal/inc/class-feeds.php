<?php

namespace Pedestal;

use Timber\Timber;

use \Pedestal\Utils\Utils;

use Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

class Feeds {

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Feeds;
            self::$instance->setup_actions();
            self::$instance->setup_filters();

        }
        return self::$instance;
    }

    /**
     * Set up actions used on the frontend
     */
    private function setup_actions() {

        remove_all_actions( 'do_feed_rss2' );
        add_action( 'do_feed_rss2', [ $this, 'action_do_feed_rss2' ] );
        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts' ] );

    }

    /**
     * Set up filters used on the frontend
     */
    private function setup_filters() {
        add_filter( 'timber_context', [ $this, 'filter_timber_context' ] );
    }

    /**
     * Load default feed templates
     */
    public function action_do_feed_rss2( $for_comments ) {
        $templates = [];
        $post_type = get_query_var( 'post_type' );

        if ( ! empty( $post_type ) && ! is_array( $post_type ) ) {
            $templates[] = "templates/feeds/feed-{$post_type}.php";
        }
        $templates[] = 'templates/feeds/feed.php';

        locate_template( $templates, true );
    }

    /**
     * Modify the main query
     */
    public function action_pre_get_posts( $query ) {

        if ( ! $query->is_main_query() ) {
            return;
        }

        $post_type = get_query_var( 'post_type' );

        if ( $query->is_feed() ) {
            $title = get_bloginfo_rss( 'name' );
            if ( ! $query->is_post_type_archive() ) {
                $query->set( 'post_type', Types::get_entity_post_types() );
            } else {
                $title .= ' » ' . Types::get_post_type_name( $post_type );
            }
            $this->set_feed_title( $query, $title );
        }

    }

    /**
     * Filter Timber's default context value
     */
    public function filter_timber_context( $context ) {

        $context['feed'] = [];
        $context['feed']['title']         = $this->get_feed_title();
        $context['feed']['self_link']     = $this->get_feed_self_link();
        $context['feed']['site_link']     = $this->get_feed_site_link();
        $context['feed']['description']   = $this->get_feed_description();
        $context['feed']['build_date']    = $this->get_feed_last_build_date();
        $context['feed']['language']      = $this->get_feed_language();
        $context['feed']['update_period'] = $this->get_feed_update_period();
        $context['feed']['update_freq']   = $this->get_feed_update_frequency();

        return $context;

    }

    private function get_feed_title() {
        return get_query_var( 'feed_title' );
    }

    private function set_feed_title( $query, $text ) {
        return $query->set( 'feed_title', $text );
    }

    /**
     * Display the link for the currently displayed feed in a XSS safe way.
     *
     * Generate a correct link for the atom:self element.
     *
     * A non-echoing version of the WP core `self_link()` function.
     *
     * @see https://developer.wordpress.org/reference/functions/self_link/
     */
    private function get_feed_self_link() {
        $host = parse_url( home_url() );
        return esc_url( set_url_scheme( 'http://' . $host['host'] . wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
    }

    /**
     * Get feed-safe site URL
     *
     * @return string URL
     */
    private function get_feed_site_link() {
        return get_bloginfo_rss( 'url' );
    }

    /**
     * Get feed-safe site description
     *
     * @return string Description
     */
    private function get_feed_description() {
        return get_bloginfo_rss( 'description' );
    }

    /**
     * Get the feed's last build date
     *
     * @return string RFC 822 datetime
     */
    private function get_feed_last_build_date() {
        return mysql2date( 'r', get_lastpostmodified( 'GMT' ), false );
    }

    /**
     * Get feed-safe site language
     *
     * @return string Language
     */
    private function get_feed_language() {
        return get_bloginfo_rss( 'language' );
    }

    /**
     * Get feed update period
     *
     * @return string
     */
    private function get_feed_update_period() {
        return 'hourly';
    }

    /**
     * Get feed update frequency
     *
     * @return string
     */
    private function get_feed_update_frequency() {
        return '1';
    }
}
