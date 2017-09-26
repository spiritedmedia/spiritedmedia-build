<?php

namespace Pedestal\Admin;

use function Pedestal\Pedestal;

use Timber\Timber;

use Pedestal\Utils\Utils;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Posts\Post;

/**
 * WP Admin tools for managing Clusters
 */
class Cluster_Tools {

    private $form;

    private $fields;

    private $notice = '';

    private $log = [];

    private static $page_title = 'Cluster Tools';

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Cluster_Tools;
            self::$instance->load();
        }
        return self::$instance;

    }

    /**
     * Load code for the admin
     */
    private function load() {
        $this->setup_actions();
    }

    /**
     * Set up admin filters
     */
    private function setup_actions() {

        // Needs to happen after post types are registered
        add_action( 'init', [ $this, 'action_init_after_post_types_registered' ], 11 );

        add_action( 'admin_menu', function() {
            add_management_page(
                esc_html__( self::$page_title, 'pedestal' ),
                esc_html__( self::$page_title, 'pedestal' ),
                'merge_clusters',
                'pedestal_cluster_tools',
                [ $this, 'render_cluster_tools_page' ]
            );
        } );
    }

    public function action_init_after_post_types_registered() {
        $this->setup_cluster_merge_field();
        $this->form = $this->fields->add_page_form( 'pedestal-cluster-merge-fields' );
    }

    private function setup_cluster_merge_field() {
        $cluster_autocomplete_args = [
            'name'                => 'post',
            'description'         => esc_html__( 'Select a Cluster', 'pedestal' ),
            'show_edit_link'      => true,
            'datasource'          => new \Fieldmanager_Datasource_Post( [
                'query_args' => [
                    'post_type'      => Types::get_cluster_post_types(),
                    'posts_per_page' => 30,
                ],
            ] ),
        ];
        $fm = new \Fieldmanager_Group( esc_html__( 'Merge Clusters', 'pedestal' ), [
            'name'               => 'pedestal_cluster_merge',
            'tabbed'             => 'vertical',
            'persist_active_tab' => false,
            'children'           => [
                'old' => new \Fieldmanager_Group( esc_html__( '1. Clusters to Merge', 'pedestal' ), [
                    'description' => esc_html( 'Select the old clusters you wish to merge into the new cluster. OLD CLUSTERS WILL BE DELETED!', 'pedestal' ),
                    'children'    => [
                        'post' => new \Fieldmanager_Group( false, [
                            'minimum_count'  => 1,
                            'limit'          => 5,
                            'add_more_label' => esc_html__( 'Merge Additional Cluster', 'pedestal' ),
                            'children' => [
                                'post' => new \Fieldmanager_Autocomplete( false, $cluster_autocomplete_args + [
                                    'description'         => esc_html__( 'Select a Cluster to Merge', 'pedestal' ),
                                ] ),
                            ],
                        ]),
                    ],
                ] ),
                'new' => new \Fieldmanager_Group( esc_html__( '2. Target Cluster', 'pedestal' ), [
                    'description' => esc_html( 'Select the new cluster you want to merge the old clusters into. The clusters selected in the first tab will be DELETED and merged into this one.', 'pedestal' ),
                    'children'    => [
                        'post' => new \Fieldmanager_Autocomplete( false, $cluster_autocomplete_args ),
                    ],
                ] ),
            ],
        ] );
        $this->fields = $fm;
    }

    public function render_page_form() {
        $current = apply_filters( 'fm_' . $this->form->uniqid . '_load', [], $this->fields );
        $html = '<form method="POST" id="' . esc_attr( $this->form->uniqid ) . '">';
        $html .= '<div class="fm-page-form-wrapper">';
        $html .= sprintf( '<input type="hidden" name="fm-page-action" value="%s" />', esc_attr( sanitize_title( $this->form->uniqid ) ) );
        $html .= wp_nonce_field( 'fieldmanager-save-' . $this->fields->name, 'fieldmanager-' . $this->fields->name . '-nonce' );
        $html .= $this->fields->element_markup( $current );
        $html .= '</div>';
        $html .= get_submit_button( esc_html( 'Submit' ) );
        $html .= '</form>';
        $html .= '</div>';

        // Check if any validation is required
        $fm_validation = Fieldmanager_Util_Validation( $this->form->uniqid, 'page' );
        $fm_validation->add_field( $this->fields );

        return $html;
    }

    /**
     * Handle the rendering of the cluster tools page
     */
    public function render_cluster_tools_page() {
        $context = [
            'page_title' => esc_html__( self::$page_title, 'pedestal' ),
            'form'       => $this->render_page_form(),
        ];

        if ( ! empty( $_POST['pedestal_cluster_merge'] ) ) {
            $data = $_POST['pedestal_cluster_merge'];
            unset( $data['old']['post']['proto'] );
            $this->handle_cluster_merge( $data );
            $context['notice'] = $this->notice;
            $context['messages'] = '<p>' . implode( '</p><p>', $this->log ) . '</p>';
        }

        Timber::render( 'partials/admin/cluster-tools.twig', $context );
    }

    /**
     * Handle page notice for the cluster tools form
     *
     * @param string $msg     Message to include in notice
     * @param string $type    Type of notice
     * @param string $classes String of additional classes for the notice
     */
    private function render_cluster_tools_notice( $msg, $type = '', $classes = '' ) {
        if ( $type ) {
            $classes .= 'notice-' . $type;
        }
        $classes .= ' notice fade is-dismissible ';
        $this->notice = sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $classes ), esc_html( $msg ) );
    }

    private function handle_cluster_merge( $data ) {
        if ( empty( $data ) ||
            empty( $data['old'] ) ||
            empty( $data['new'] ) ||
            empty( $data['old']['post'] ) ||
            empty( $data['new']['post'] )
        ) {
            $this->render_cluster_tools_notice( 'Missing required data!', 'error' );
            return;
        }

        $_post_types = [];
        $include_proto_data = true;
        $connection_types = Types::get_cluster_connection_types( $_post_types, $include_proto_data );
        $msg_report = 'What a strange occurrence! Best report to #product...';

        $merge_clusters = [];
        $target_cluster_id = $data['new']['post'];
        foreach ( $data['old']['post'] as $post ) {
            $merge_cluster_id = $post['post'];
            $cluster = Post::get( $merge_cluster_id );
            if ( Types::is_cluster( $cluster ) ) {
                $merge_clusters[] = $cluster;
            } else {
                $msg = "The selected post with ID %d for which you've selected
                for merging is not actually a cluster! %s";
                $msg = sprintf( $msg, $merge_cluster_id, $msg_report );
                $this->render_cluster_tools_notice( $msg, 'error' );
                return;
            }
        }
        unset( $merge_cluster_id );
        $target_cluster = Post::get( $target_cluster_id );

        if ( ! Types::is_cluster( $target_cluster ) ) {
            $msg = sprintf( 'The selected target post with ID %d is not actually a cluster! %s',
                $target_cluster_id,
                $msg_report
            );
            $this->render_cluster_tools_notice( $msg, 'error' );
            return;
        }

        $target_cluster_log_msg = $this->handle_post_log_identifier( $target_cluster );

        $new_connections = [];
        $total_new_connection_count = 0;
        foreach ( $merge_clusters as $i => $merge_cluster ) :
            $merge_cluster_log_msg = $this->handle_post_log_identifier( $merge_cluster );

            // Don't merge any clusters that are identical to the target
            if ( $merge_cluster->get_id() == $target_cluster_id ) {
                $msg = '<b>%s is identical to the target cluster, so it was not
                merged or deleted.</b>';
                $this->log[] = sprintf( $msg, ucfirst( $merge_cluster_log_msg ) );
                unset( $merge_clusters[ $i ] );
                continue;
            }

            $this->log[] = sprintf( '<i>Migrating connections from %s to %s...</i>',
                $merge_cluster_log_msg,
                $target_cluster_log_msg
            );

            $new_connection_count = 0;
            $old_connected = $merge_cluster->get_connected();
            foreach ( $old_connected as $connected ) :
                $connected_log_message = $this->handle_post_log_identifier( $connected );
                // If this is empty, something is very wrong, because we already
                // know the post is connected via P2P. So don't check for it
                // being empty and let the error occur...
                $p2p_data = $connected->get_p2p_data();
                $p2p_type = $p2p_data['type'];

                // Replace the sanitized name of the original cluster to be
                // merged with the sanitized name of the target cluster, with
                // the hopes that the results equal a valid connection type
                $post_type_name_plural = true;
                $post_type_name_sanitize = true;
                $merge_cluster_sanitized_name = $merge_cluster->get_post_type_name( $post_type_name_plural, $post_type_name_sanitize );
                $target_cluster_sanitized_name = $target_cluster->get_post_type_name( $post_type_name_plural, $post_type_name_sanitize );
                $new_connection_type = str_replace( $merge_cluster_sanitized_name, $target_cluster_sanitized_name, $p2p_type );

                $connect = p2p_type( $new_connection_type );
                if ( false === $connect ) {
                    $msg_unregistered_connection = '<code>%s</code> is not a
                    registered connection type! Tried connecting %s to %s...
                    You\'re probably best off making this connection
                    manually...';

                    $this->log[] = sprintf( $msg_unregistered_connection,
                        $new_connection_type,
                        $connected_log_message,
                        $target_cluster_log_msg
                    );
                    continue;
                }

                if ( is_wp_error( $connect ) ) {
                    $this->log[] = $connect->get_error_message();
                    continue;
                }

                $connect->connect( $connected->get_id(), $target_cluster_id );
                if ( is_wp_error( $connect ) ) {
                    $this->log[] = $connect->get_error_message();
                    continue;
                }

                $log_successful_connection = sprintf( 'Connected %s to %s with connection type <code>%s</code>.',
                    $connected_log_message,
                    $target_cluster_log_msg,
                    $new_connection_type
                );

                $geospace_connection_types = Types::get_cluster_connection_types( Types::get_geospace_post_types() );
                if ( in_array( $new_connection_type, $geospace_connection_types ) ) {
                    $log_successful_connection .= ' Note that the geospatial
                    relationship for this new connection will have to be
                    defined manually by editing one of the posts in the new
                    connection and setting this metadata in the appropriate
                    connection box.';

                    if ( in_array( $p2p_type, $geospace_connection_types ) ) {
                        $log_successful_connection .= ' The old relationship
                        metadata no longer exists because the connection type
                        has changed.';
                    }
                }

                $this->log[] = $log_successful_connection;

                $new_connection_count++;
                $total_new_connection_count++;
            endforeach;

            if ( $new_connection_count > 0 ) {
                $this->log[] = sprintf( '<b>Migrated %d connections from %s to %s.</b>',
                    $new_connection_count,
                    $merge_cluster_log_msg,
                    $target_cluster_log_msg
                );
            } else {
                $this->log[] = sprintf( '<b>No connections to make for %s.</b>',
                    $merge_cluster_log_msg
                );
            }

            wp_trash_post( $merge_cluster->get_id() );
            $this->log[] = sprintf( '<b>Deleted %s!</b>', $merge_cluster_log_msg );
        endforeach;

        if ( $total_new_connection_count > 0 ) {
            $this->log[] = sprintf( '<b>Migrated %d connections from %d clusters to %s!</b>',
                $total_new_connection_count,
                count( $merge_clusters ),
                $target_cluster_log_msg
            );
        } else {
            $this->log[] = sprintf( '<b>No connections migrated to %s.</b>',
                $target_cluster_log_msg
            );
        }
    }

    /**
     * Handle the log message for each new connection
     *
     * @TODO Note that the directional order in the log message may
     * not reflect the actual direction of the connection. Resolving
     * this is not presently worth the added complexity.
     *
     * @param  Post $post Post object
     * @return string [type] [title] [id]
     */
    private function handle_post_log_identifier( $post ) {
        if ( ! Types::is_post( $post ) ) {
            return false;
        }
        return sprintf( '%s "%s" [%s]',
            $post->get_type(),
            $post->get_title(),
            $post->get_id()
        );
    }
}
