<?php
namespace Pedestal\Registrations\Taxonomies;

use Timber\Timber;

/**
 * For given taxonomies display their metabox as a set of radio buttons so only one option can be selected
 */
class Single_Option_Taxonomies {

    /**
     * Hold all of the taxonomies
     *
     * @var array
     */
    public $taxonomies = [];

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Hook into actions
     */
    public function setup_actions() {
        add_action( 'init', [ $this, 'action_init_set_taxonomies' ] );
        add_action( 'init', [ $this, 'action_init_sanitize_tax_input' ], 11 );
        add_action( 'admin_menu', [ $this, 'action_admin_menu_remove_meta_box' ] );
        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_box' ] );
        add_action( 'transition_post_status', [ $this, 'action_transition_post_status' ], 10, 3 );
        add_action( 'admin_notices', [ $this, 'action_admin_notices' ] );
    }

    /**
     * Get the taxonomies that should be displayed
     */
    public function action_init_set_taxonomies() {
        $output = 'objects';
        $this->taxonomies = get_taxonomies( [
            'single_option_taxonomy' => true,
        ], $output );
        // For taxonomies that you can't modify the args for
        $this->taxonomies = apply_filters( 'pedestal_single_option_taxonomies', $this->taxonomies );
    }

    /**
     * Make sure the terms being submitted actually exist. Prevents someone from
     * changing the `value` attribute on the frontend and creating a new term.
     */
    public function action_init_sanitize_tax_input() {
        if ( empty( $_POST['tax_input'] ) ) {
            return;
        }
        foreach ( $_POST['tax_input'] as $tax => $term_name ) {
            if ( empty( $this->taxonomies[ $tax ] ) ) {
                continue;
            }
            if ( ! term_exists( $term_name, $tax ) ) {
                unset( $_POST['tax_input'][ $tax ] );
            }
        }
    }

    /**
     * Remove default WordPress taxonomy metaboxes for our taxonomies
     */
    public function action_admin_menu_remove_meta_box() {
        if ( empty( $this->taxonomies ) ) {
            return;
        }
        foreach ( $this->taxonomies as $tax_key => $tax ) {
            $id = $tax_key . 'div';
            if ( ! is_taxonomy_hierarchical( $tax_key ) ) {
                $id = 'tagsdiv-' . $tax_key;
            }
            $post_type = $tax->object_type;
            remove_meta_box( $id, $post_type, 'side' );
        }
    }

    /**
     * Add our own custom metabox for displaying terms as radio buttons
     */
    public function action_add_meta_box() {
        if ( empty( $this->taxonomies ) ) {
            return;
        }
        foreach ( $this->taxonomies as $tax_key => $tax ) {
            $id = 'radio-' . $tax_key . 'div';
            $title = $tax->labels->singular_name;
            $post_type = $tax->object_type;
            $callback_args = [
                'taxonomy' => $tax_key,
            ];
            $context = 'side';
            $priority = 'core';
            add_meta_box( $id, $title, [ $this, 'render_metabox' ], $post_type , $context, $priority, $callback_args );
        }
    }

    /**
     * Callback for rendering the metabox
     *
     * @param  WP_Post|object $post Post object for the post currently being edited
     * @param  array $box           Metabox arguments
     */
    public function render_metabox( $post, $box ) {
        if ( ! isset( $box['args']['taxonomy'] ) || ! is_array( $box['args'] ) ) {
            return;
        }
        $taxonomy = $box['args']['taxonomy'];
        $selected_term = [
            'term_id'          => 0,
            'name'             => '',
            'slug'             => '',
            'term_group'       => 0,
            'term_taxonomy_id' => 0,
            'taxonomy'         => $taxonomy,
            'description'      => '',
            'parent'           => 0,
            'count'            => 0,
            'filter'           => 'raw',
        ];
        $selected_terms = wp_get_object_terms( $post->ID, $taxonomy, [] );
        if ( ! empty( $selected_terms ) ) {
            $selected_term = $selected_terms[0];
        }
        $terms = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ] );
        $context = [
            'taxonomy'      => $taxonomy,
            'terms'         => $terms,
            'selected_term' => (object) $selected_term,
        ];
        ob_start();
            Timber::render( 'partials/admin/metabox-single-option-taxonomy.twig', $context );
        echo ob_get_clean();
    }

    /**
     * Check if a term is selected before being published. Otherwise we'll set
     * the post_status to draft until a term is selected from required taxonomies.
     *
     * @param  string $new_status The new status of the post
     * @param  string $old_status The old status of the post
     * @param  object $post       The post
     */
    public function action_transition_post_status( $new_status = '', $old_status = '', $post ) {
        if ( 'publish' != $new_status && 'future' != $new_status ) {
            return;
        }

        $taxonomies_to_check = [];
        foreach ( $this->taxonomies as $tax_key => $tax ) {
            $post_types = $tax->object_type;
            if ( ! is_array( $post_types ) ) {
                $post_types = [ $post_types ];
            }
            if ( in_array( $post->post_type, $post_types ) ) {
                $taxonomies_to_check[] = $tax_key;
            }
        }
        if ( empty( $taxonomies_to_check ) ) {
            return;
        }

        $empty_taxonomies = [];
        foreach ( $taxonomies_to_check as $taxonomy ) {
            $terms = wp_get_object_terms( $post->ID, $taxonomy, [] );
            if ( empty( $terms ) ) {
                $args = [
                    'ID'          => $post->ID,
                    'post_status' => 'draft',
                ];
                $updated = wp_update_post( $args );
                if ( $updated ) {
                    $empty_taxonomies[] = $taxonomy;
                }
            }
        }
        if ( ! empty( $empty_taxonomies ) ) {
            $edit_link = get_edit_post_link( $post->ID, '' );
            if ( $edit_link ) {
                $edit_link = add_query_arg( [
                    'required-taxonomies' => implode( ',', $empty_taxonomies ),
                ], $edit_link );
                wp_safe_redirect( $edit_link );
                die();
            }
        }
    }

    /**
     * Display an admin notice if a taxonomy term needs to be selected
     */
    public function action_admin_notices() {
        if ( ! isset( $_GET['required-taxonomies'] ) ) {
            return;
        }
        $required_taxes = explode( ',', $_GET['required-taxonomies'] );
        foreach ( $required_taxes as $tax_key ) {
            if ( ! isset( $this->taxonomies[ $tax_key ] ) ) {
                continue;
            }
            $tax = $this->taxonomies[ $tax_key ];
            $tax_label = $tax->labels->singular_name;
            $metabox_id = 'radio-' . $tax_key . 'div';
            $context = [
                'tax_label'  => $tax_label,
                'metabox_id' => $metabox_id,
            ];
            ob_start();
                Timber::render( 'partials/admin/notice-single-option-taxonomy.twig', $context );
            echo ob_get_clean();
        }
    }
}
