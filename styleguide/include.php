<?php
require( '../wp-load.php' );

$dev_environment = defined( 'WP_ENV' ) && 'development' === WP_ENV;
if ( ! $dev_environment && ! is_user_logged_in() ) {
    auth_redirect();
}

add_action( 'wp_head', function() {
    ob_start(); ?>
    <link rel="stylesheet" href="/styleguide/src/highlightjs/styles/github.css">
    <script src="/styleguide/src/highlightjs/highlight.pack.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
    <style>
        pre {
            margin-top: 16px;
            margin-bottom: 16px;
        }

        ul li {
            padding-top: 8px;
        }
    </style>
    <?php echo ob_get_clean();
} );

// Initiate Pedestal
$pedestal = \Pedestal\Pedestal();
