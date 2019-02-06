<?php
namespace Pedestal\Admin;

use Timber\Timber;

/**
 * A collection of tools for making taxonomy management easier
 *  - Term to Term migration
 */
class Taxonomy_Tools {

    /**
     * Title of the page
     * @var string
     */
    private $page_title = 'Taxonomy Tools';

    /**
     * The slug of the admin page used in the URL
     * @var string
     */
    private $admin_page_slug = 'pedestal_taxonomy_tools';

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
     * Hook in to WordPress Actions
     */
    public function setup_actions() {

        // Needs to happen after post types are registered
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );
        add_action( 'edit_tag_form_fields', [ $this, 'action_edit_tag_form_fields' ] );
        add_action( 'edited_term', [ $this, 'action_edited_term' ], 15, 3 );
        add_action( 'admin_notices', [ $this, 'action_admin_notices' ] );
        add_action( 'admin_menu', function() {
            add_management_page(
                $this->page_title,
                $this->page_title,
                'merge_clusters',
                $this->admin_page_slug,
                [ $this, 'render_taxonomy_tools_page' ]
            );
        } );
    }

    /**
     * Setup Fieldmanager fields
     */
    public function action_init_after_post_types_registered() {
        $taxonomy = $this->get_taxonomy();
        if ( ! $taxonomy ) {
            return;
        }
        $tax_slug                 = $taxonomy->name;
        $plural_label             = $taxonomy->label;
        $singular_label           = $taxonomy->labels->singular_name;
        $source_autocomplete_args = [
            'name'           => 'term_id',
            'description'    => esc_html__( 'Select a ' . $singular_label, 'pedestal' ),
            'show_edit_link' => true,
            'datasource'     => new \Fieldmanager_Datasource_Term( [
                // This is a hacky way to send the taxonomy slug to our AJAX event handler so callbacks work and stuff.
                'ajax_action' => 'fm_datasource_term_for_' . $tax_slug,
                'taxonomy'    => $tax_slug,
            ] ),
        ];
        $fm                       = new \Fieldmanager_Group( esc_html__( 'Merge ' . $plural_label, 'pedestal' ), [
            'name'               => 'pedestal-taxonomy-tools-merge-terms',
            'tabbed'             => 'vertical',
            'persist_active_tab' => false,
            'children'           => [
                'old' => new \Fieldmanager_Group( '1. Merge these terms&hellip;', [
                    'description' => esc_html( "Select the old $singular_label you wish to merge into the new $singular_label. OLD " . strtoupper( $plural_label ) . ' WILL BE DELETED!', 'pedestal' ),
                    'children'    => [
                        'terms' => new \Fieldmanager_Group( false, [
                            'minimum_count'  => 1,
                            'limit'          => 100,
                            'add_more_label' => esc_html__( 'Add Another Term', 'pedestal' ),
                            'children'       => [
                                'term_id' => new \Fieldmanager_Autocomplete( false, [
                                    'description' => esc_html__( 'Select a ' . $singular_label . ' to Merge', 'pedestal' ),
                                ] + $source_autocomplete_args ),
                            ],
                        ]),
                    ],
                ] ),
                'new' => new \Fieldmanager_Group( '2. Into this term&hellip;', [
                    'description' => esc_html( "Select the new $singular_label you want to merge the old $plural_label into. The $plural_label selected in the first tab will be DELETED and merged into this one.", 'pedestal' ),
                    'children'    => [
                        'term_id' => new \Fieldmanager_Autocomplete( false, $source_autocomplete_args ),
                    ],
                ] ),
            ],
        ] );
        $this->fields             = $fm;
        $this->form               = $fm->add_page_form( 'pedestal-term-merge-fields' );
    }

    /**
     * Build the form for selecting the terms to migrate
     *
     * @return string HTML of the form
     */
    public function get_page_form() {
        $current = apply_filters( 'fm_' . $this->form->uniqid . '_load', [], $this->fields );

        // Check if any validation is required
        $fm_validation = Fieldmanager_Util_Validation( $this->form->uniqid, 'page' );
        $fm_validation->add_field( $this->fields );

        $context = [
            'unique_id'     => sanitize_title( $this->form->uniqid ),
            'nonce_field'   => wp_nonce_field( 'fieldmanager-save-' . $this->fields->name, 'fieldmanager-' . $this->fields->name . '-nonce' ),
            'form_body'     => $this->fields->element_markup( $current ),
            'submit_button' => get_submit_button( 'Submit' ),
        ];
        ob_start();
            Timber::render( 'partials/admin/taxonomy-tools/taxonomy-tools-form.twig', $context );
        return ob_get_clean();
    }

    /**
     * Render the markup of the Taxonomy Tools page
     */
    public function render_taxonomy_tools_page() {
        $taxonomy = $this->get_taxonomy();

        if ( $taxonomy ) {
            // We have a taxonomy so we can render the form
            $context = [
                'form' => $this->get_page_form(),
            ];
            // If the form is being submitted we can merge the terms
            if ( ! empty( $_POST['pedestal-taxonomy-tools-merge-terms'] ) ) {
                $data = $_POST['pedestal-taxonomy-tools-merge-terms'];
                // This array index isn't used and messes up our loop later on
                unset( $data['old']['terms']['proto'] );

                $tax          = $taxonomy->name;
                $to_term_id   = $data['new']['term_id'];
                $to_term      = get_term( $to_term_id, $tax );
                $to_term_name = $to_term->name;

                $from = [];
                foreach ( $data['old']['terms'] as $term ) {
                    if ( ! empty( $term['term_id'] ) ) {
                        $from[] = $term['term_id'];
                    }
                }
                $from  = array_unique( $from );
                $count = 0;
                foreach ( $from as $from_term_id ) {
                    $this->migrate_term( $from_term_id, $to_term_id, $tax );
                    $count++;
                }
                $context['message'] = $count . ' terms were migrated to ' . $to_term_name;
            }
        } else {
            // No taxonomy was selected so show the screen to select one
            $taxonomy_options = [];
            $args             = [
                'public' => true,
            ];
            $taxonomies       = get_taxonomies( $args, 'objects' );
            foreach ( $taxonomies as $tax ) {
                $taxonomy_options[ $tax->name ] = $tax->label;
            }
            $context = [
                'taxonomy_options' => $taxonomy_options,
                'preselected_slug' => 'pedestal_source',
                'admin_page_slug'  => $this->admin_page_slug,
            ];

        }

        $context['page_title'] = $this->page_title;
        Timber::render( 'partials/admin/taxonomy-tools/taxonomy-tools-page.twig', $context );
    }

    /**
     * Add a field to migrate the term from the edit form
     *
     * @param  WP_Term $term The term being edited
     */
    public function action_edit_tag_form_fields( $term ) {
        $dropdown = wp_dropdown_categories( [
            'show_option_none'  => ' ',
            'option_none_value' => '-1',
            'taxonomy'          => $term->taxonomy,
            'hide_empty'        => false,
            'exclude'           => [ $term->term_id ],
            'orderby'           => 'name',
            'echo'              => false,
            'name'              => 'term-migration',
            'id'                => 'term-migration',
            'hide_if_empty'     => true,
        ] );
        if ( ! $dropdown ) {
            return;
        }
        $context = [
            'term_name' => $term->name,
            'dropdown'  => $dropdown,
        ];
        Timber::render( 'partials/admin/taxonomy-tools/taxonomy-tools-edit-term-row.twig', $context );
    }

    /**
     * Handle migrating the term when the term is being saved
     *
     * @param  integer $term_id  ID of the term being edited
     * @param  integer $tt_id    Term Taxonomy ID of the term being edited
     * @param  string  $taxonomy Taxonomy of the term being edited
     */
    public function action_edited_term( $term_id = 0, $tt_id = 0, $taxonomy = '' ) {
        if ( empty( $_POST['term-migration'] ) || '-1' == $_POST['term-migration'] ) {
            return;
        }
        $source_term_id = $term_id;
        $target_term_id = $_POST['term-migration'];
        $this->migrate_term( $source_term_id, $target_term_id, $taxonomy );
        // Since the term was deleted we need to redirect somewhere else
        $redirect_url = add_query_arg( [
            'taxonomy'            => $taxonomy,
            'migrate-term-notice' => 'success',
        ], admin_url( 'edit-tags.php' ) );
        wp_safe_redirect( $redirect_url );
        die();
    }

    /**
     * Display a success message after migrating a term on the edit-tags screen
     */
    public function action_admin_notices() {
        if ( empty( $_GET['migrate-term-notice'] ) ) {
            return;
        }
        ?>
        <div class="notice notice-success fade is-dismissible">
            <p>Term migrated successfully!</p>
        </div>
        <?php
    }

    /**
     * Get the taxonomy for the terms we're working with via the query string or AJAX magic
     * @return WP_Taxonomy|false A taxonomy object or false if the taxonomy is not found
     */
    public function get_taxonomy() {
        // We need to know which taxonomy we're dealing with
        // as part of the Fieldmanager Autocomplete AJAX request.
        // There is no easy way to do that but we can use a custom AJAX action.
        // By parsing the action like 'fm_datasource_term_for_<taxonomy-slug>'
        // we can figure out which taxonomy we need to return.
        if ( wp_doing_ajax() && ! empty( $_POST['action'] ) ) {
            $pieces = explode( 'fm_datasource_term_for_', $_POST['action'] );
            if ( ! empty( $pieces[1] ) && taxonomy_exists( $pieces[1] ) ) {
                return get_taxonomy( $pieces[1] );
            }
        }
        if ( ! empty( $_GET['taxonomy'] ) ) {
            return get_taxonomy( $_GET['taxonomy'] );
        }
        return false;
    }

    /**
     * Migrate all posts associated with one term to another term
     *
     * @param  integer $source_id Term ID we will migrate from
     * @param  integer $target_id Term ID we will migrate to
     * @param  string  $tax       Taxonomy of the terms we're migrating
     * @return bool               Whether the migration has succeeded or not
     */
    public function migrate_term( $source_id = 0, $target_id = 0, $tax = '' ) {
        $source_term = get_term( $source_id, $tax );
        $target_term = get_term( $target_id, $tax );
        $post_ids    = get_objects_in_term( $source_id, $tax, [
            'order' => 'ASC',
        ] );

        $args = [
            'orderby' => 'name',
            'order'   => 'ASC',
            'fields'  => 'all',
        ];
        foreach ( $post_ids as $id ) {
            $new_terms = [];
            $old_terms = wp_get_object_terms( $id, $tax, $args );
            foreach ( $old_terms as $old_term ) {
                if ( (int) $old_term->term_id != $source_id ) {
                    $new_terms[] = $old_term->term_id;
                }
            }
            $new_terms[] = $target_term->term_id;
            $append      = false;
            wp_set_post_terms( (int) $id, $new_terms, $tax, $append );
        }
        wp_delete_term( $source_id, $tax );

        return true;
    }
}
