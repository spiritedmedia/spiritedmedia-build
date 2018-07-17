<?php
namespace Pedestal\Admin;

use Timber\Timber;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Posts\Post;

class Ad_Kill_Switch {

    private $disable_ads_meta_key = 'disable-ads';

    private $action = 'pedestal-disable-ads';

    private $nonce = 'pedestal-disable-ads-nonce';

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
     * Hook in to various actions
     */
    private function setup_actions() {
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ], 10, 2 );
        add_action( 'post_updated', [ $this, 'action_post_updated' ], 10 );
        add_action( 'wp_enqueue_scripts', [ $this, 'action_wp_enqueue_scripts_dequeue_dfp' ], 11 );
    }

    /**
     * Hook in to various filters
     */
    private function setup_filters() {
        add_filter( 'pedestal_show_dfp_unit', function( $show_ad_unit ) {
            if ( ! is_singular() || ! $show_ad_unit ) {
                return $show_ad_unit;
            }

            $post = get_post();
            if ( ! isset( $post->ID ) ) {
                return $show_ad_unit;
            }
            if ( $this->are_ads_hidden( $post->ID ) ) {
                return false;
            }
            return true;
        }, 10, 1 );
    }

    /**
     * Setup the metabox
     *
     * @param string $post_type  The post type of the post being edited
     */
    public function action_add_meta_boxes( $post_type = '' ) {
        if ( ! Types::is_original_content( $post_type ) ) {
            return;
        }
        add_meta_box( $this->action,
            'Ads',
            [ $this, 'handle_meta_box' ],
            $post_type,
            'side',
            'low'
        );
    }

    /**
     * Render the metabox
     *
     * @param  object $post WP_Post
     */
    public function handle_meta_box( $post ) {
        $ped_post = Post::get( (int) $post->ID );

        $name = $this->nonce;
        $referer = true;
        $echo = false;
        $nonce_field = wp_nonce_field( $this->action, $name, $referer, $echo );

        $context = [
            'content_type'    => $ped_post->get_type_name(),
            'nonce_field'     => $nonce_field,
            'are_ads_hidden' => $this->are_ads_hidden( $post->ID ),
        ];
        Timber::render( 'partials/admin/metabox-inline-ads.twig', $context );
    }

    /**
     * Save value of the metabox
     *
     * @param  integer $post_id ID of the post being edited
     */
    public function action_post_updated( $post_id = 0 ) {
        if (
            empty( $_POST[ $this->nonce ] )
            || ! check_admin_referer( $this->action, $this->nonce )
        ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! empty( $_POST['pedestal-disable-ads'] ) ) {
            update_post_meta( $post_id, $this->disable_ads_meta_key, true );
        } else {
            delete_post_meta( $post_id, $this->disable_ads_meta_key );
        }
    }

    /**
     * Dequeue DFP scripts if post is set to disable ads
     */
    public function action_wp_enqueue_scripts_dequeue_dfp() {
        if ( ! is_singular() ) {
            return;
        }
        $post = get_post();
        if ( ! $this->are_ads_hidden( $post->ID ) ) {
            return;
        }

        wp_dequeue_script( 'dfp-load' );
        wp_dequeue_script( 'dfp-placeholders' );
    }

    /**
     * Conditional check if ads should be hidden for a given post ID
     *
     * @param  integer $post_id ID of post to check
     * @return bool             Whether ads are hidden or not
     */
    public function are_ads_hidden( $post_id = 0 ) {
        return ( get_post_meta( $post_id, $this->disable_ads_meta_key, true ) );
    }
}
