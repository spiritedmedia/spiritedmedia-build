<?php
namespace Pedestal\Admin;

/**
 * Tools for setting up split-test emails in WordPress
 */
class Newsletter_Testing {

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
     * Hook in to various actions
     */
    public function setup_actions() {
        add_action( 'add_meta_boxes_pedestal_newsletter', [ $this, 'action_add_meta_boxes_pedestal_newsletter' ] );
        add_action( 'save_post', [ $this, 'action_save_post' ], 10, 2 );
        add_action( 'pre_get_posts', [ $this, 'action_pre_get_posts' ] );
    }

    /**
     * Hook in to various filters
     */
    public function setup_filters() {
        add_filter( 'the_permalink', [ $this, 'filter_the_permalink' ], 10, 2 );
    }

    /**
     * Set-up a split testing metabox
     * @param WP Post $post The post object being edited
     */
    public function action_add_meta_boxes_pedestal_newsletter( $post ) {
        add_meta_box( 'pedestal-newsletter-testing',
            'Split Testing',
            [ $this, 'newsletter_testing_metabox' ],
            null,
            'advanced',
            'high'
        );
    }

    /**
     * Rendering of the split testing meta box
     * @param  WP Post $post Post object being edited
     */
    public function newsletter_testing_metabox( $post ) {
        // If it is a child post then link to the parent post
        if ( 0 != $post->post_parent ) {
            $parent_post = get_post( $post->post_parent );
            $edit_link = get_edit_post_link( $parent_post->ID );
            echo '<p>This is a copy of <a href="' . esc_url( $edit_link ) . '">' . $parent_post->post_title . '</a>.</p>';
            // Hide the publish button for child posts
            ?>
            <script>document.getElementById('publishing-action').style = 'display: none;';</script>
            <?php
            return;
        }

        // List cloned versions
        $args = [
            'post_type' => 'pedestal_newsletter',
            'post_parent' => $post->ID,
            'posts_per_page' => 5,
            'post_status' => [ 'draft', 'publish', 'future' ],
        ];
        $cloned_posts = new \WP_Query( $args );
        $cloned_posts = $cloned_posts->posts;
        foreach ( $cloned_posts as $cloned_post ) :
            $edit_link = get_edit_post_link( $cloned_post->ID );
            $date = get_the_date( 'M d Y g:i a', $cloned_post );
            $title = get_the_title( $cloned_post );
            echo '<p><a href="' . esc_url( $edit_link ) . '">' . $title . '</a> - ' . $date . '</p>';
        endforeach;
        ?>
        <p>
            <input type="submit" name="save" value="Make a Copy" class="button button-primary button-large">
        </p>
        <?php
    }

    /**
     * Handle saving cloned posts
     * @param  integer $post_id ID of the post being saved
     * @param  WP Post  $post   Post object being saved
     */
    public function action_save_post( $post_id = 0, $post ) {
        if ( empty( $_POST['save'] ) || 'Make a Copy' != $_POST['save'] ) {
            return;
        }

        // If we don't do this then we're sent on an infinite loop
        $_POST['save'] = 'Made a Copy';
        $all_meta = get_post_meta( $post_id );
        // Unset meta keys
        unset( $all_meta['_edit_lock'] );
        unset( $all_meta['_edit_last'] );

        // Clone post meta values
        $new_meta = [];
        foreach ( $all_meta as $key => $val ) {
            if ( is_array( $val ) ) {
                $val = $val[0];
            }
            $val = maybe_unserialize( $val );
            $new_meta[ $key ] = $val;
        }

        // Convert the post object to an array
        $new_post = (array) $post;

        // Clear out some values
        unset( $new_post['ID'] );
        unset( $new_post['post_modified'] );
        unset( $new_post['post_modified_gmt'] );

        // Update some values
        $new_post['post_parent'] = $post_id;
        $new_post['meta_input'] = $new_meta;
        $new_post['post_status'] = 'draft';
        // Ensure a unique slug
        $new_slug = $new_post['post_title'] . ' ' . current_time( 'mysql' );
        $new_post['post_name'] = '-' . sanitize_title( $new_slug );

        wp_insert_post( $new_post );
    }

    /**
     * Prevent child Newsletter posts from being displayed in the post listing
     * screen of the admin
     * @param  WP_Query $query Object of the current main query
     */
    public function action_pre_get_posts( $query ) {
        $current_screen = get_current_screen();
        if ( ! isset( $current_screen->id ) ) {
            return;
        }

        if ( is_admin() && 'edit-pedestal_newsletter' == $current_screen->id ) {
            // Only show posts that have a post_parent value of 0
            $query->set( 'post_parent', 0 );
        }
    }

    /**
     * Modify the permalink of child posts to use the parent post permalink
     * @param  string  $permalink Current URL
     * @param  integer $post_id   ID of the post of the permalink
     * @return string             New permalink
     */
    public function filter_the_permalink( $permalink = '', $post_id = 0 ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return $permalink;
        }

        // Newsletter posts that are children should use
        // the permalink of their parent Newsletter post
        if ( 'pedestal_newsletter' === $post->post_type && 0 < $post->post_parent ) {
            $permalink = get_permalink( $post->post_parent );
        }
        return $permalink;
    }
}
