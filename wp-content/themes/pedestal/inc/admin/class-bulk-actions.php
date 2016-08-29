<?php

namespace Pedestal\Admin;

use \Pedestal\Registrations\Post_Types\Types;

/**
 * The custom bulk action class is largely based on others' work:
 *
 * @link https://www.skyverge.com/blog/add-custom-bulk-action/
 * @link https://github.com/Seravo/wp-custom-bulk-actions
 */
class Bulk_Action {

    public $bulk_action_post_type;

    private $actions = [];

    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct( $args = [] ) {

        // Define which post types these bulk actions affect.
        $defaults = [
            'post_type' => Types::get_post_types(),
        ];

        $args = wp_parse_args( $args, $defaults );
        extract( $args, EXTR_SKIP );

        $this->bulk_action_post_type = $post_type;
    }

    /**
     * Define custom bulk actions and corresponding callbacks
     *
     * @param array $args {
     *
     *     @type callback $callback     Provide the callback closure for
     *         execution of the bulk action. Required.
     *
     *     @type string   $menu_text    The text to display in the bulk actions
     *         dropdown menu. By default, this is converted to the action name
     *         unless `$action_name` is specified. Required.
     *
     *     @type string   $action_name  Provide a specific name for the action
     *
     *     @type string   $admin_notice Change the default admin notice after
     *         the action completes.
     *
     * }
     *
     */
    public function register_bulk_action( $args = [] ) {

        $defaults = [
            'action_name' => '',
        ];

        $args = wp_parse_args( $args, $defaults );
        extract( $args, EXTR_SKIP );

        $func = [];
        $func['callback'] = $callback;
        $func['menu_text'] = $menu_text;
        $func['admin_notice'] = $admin_notice;

        if ( '' === $action_name ) {
            // Convert menu text to action_name 'Mark as sold' => 'mark_as_sold'
            $action_name = strtolower( str_replace( ' ', '_', $menu_text ) );
        }

        $this->actions[ $action_name ] = $func;
    }

    /**
     * Callbacks need to be registered before add_actions
     */
    public function init() {
        if ( is_admin() ) {
            // admin actions/filters
            add_action( 'admin_footer-edit.php', [ &$this, 'bulk_actions_admin_footer' ] );
            add_action( 'load-edit.php',         [ &$this, 'handle_bulk_action' ] );
            add_action( 'admin_notices',         [ &$this, 'display_admin_notice' ] );
        }
    }


    /**
     * Add the bulk action to the select menus
     */
    public function bulk_actions_admin_footer() {
        global $post_type;

        // Only permit actions with defined post type
        if ( $post_type == $this->bulk_action_post_type ) {

            ob_start();
            ?>

<script type="text/javascript">
    jQuery(document).ready(function() {
        <?php
        foreach ( $this->actions as $action_name => $action ) { ?>
            jQuery('<option>').val('<?php echo $action_name ?>').text('<?php echo $action['menu_text'] ?>').appendTo("select[name='action']");
            jQuery('<option>').val('<?php echo $action_name ?>').text('<?php echo $action['menu_text'] ?>').appendTo("select[name='action2']");
        <?php } ?>
    });
</script>

            <?php
            echo ob_get_clean();

        }
    }



    /**
     * Handle the custom Bulk Action
     *
     * @link http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
     */
    public function handle_bulk_action() {
        global $typenow;
        $post_type = $typenow;

        if ( $post_type == $this->bulk_action_post_type ) {

            // get the action
            //
            // depending on your resource type this could be
            // WP_Users_List_Table, WP_Comments_List_Table, etc
            $wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
            $action = $wp_list_table->current_action();

            // allow only defined actions
            $allowed_actions = array_keys( $this->actions );
            if ( ! in_array( $action, $allowed_actions ) ) { return; }

            // security check
            check_admin_referer( 'bulk-posts' );

            // make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
            if ( isset( $_REQUEST['post'] ) ) {
                $post_ids = array_map( 'intval', $_REQUEST['post'] );
            }

            if ( empty( $post_ids ) ) { return; }

            // this is based on wp-admin/edit.php
            $sendback = remove_query_arg( [ 'exported', 'untrashed', 'deleted', 'ids' ], wp_get_referer() );
            if ( ! $sendback ) {
                $sendback = admin_url( "edit.php?post_type=$post_type" ); }

            $pagenum = $wp_list_table->get_pagenum();
            $sendback = add_query_arg( 'paged', $pagenum, $sendback );

            if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
                // check that we have anonymous function as a callback
                $anon_fns = array_filter( $this->actions[ $action ], function( $el ) {
                    return $el instanceof Closure;
                } );
                if ( 0 !== count( $anon_fns ) ) {
                    // Finally use the callback
                    $result = $this->actions[ $action ]['callback']($post_ids);
                } else {
                    $result = call_user_func( $this->actions[ $action ]['callback'], $post_ids );
                }
            } else {
                $result = call_user_func( $this->actions[ $action ]['callback'], $post_ids );
            }

            $sendback = add_query_arg( [ 'success_action' => $action, 'ids' => join( ',', $post_ids ) ], $sendback );
            $sendback = remove_query_arg( [ 'action', 'paged', 'mode', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view' ], $sendback );

            wp_redirect( $sendback );
            exit();
        }
    }


    /**
     * Display an admin notice after action
     */
    public function display_admin_notice() {
        global $post_type, $pagenow;

        if ( 'edit.php' === $pagenow && $post_type == $this->bulk_action_post_type ) {
            if ( isset( $_REQUEST['success_action'] ) ) {
                // Print notice in admin bar
                $message = $this->actions[ $_REQUEST['success_action'] ]['admin_notice'];
                if ( ! empty( $message ) ) {
                    echo "<div class=\"updated\"><p>$message</p></div>";
                }
            }
        }

    }
}
