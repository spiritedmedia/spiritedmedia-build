<?php
require( '../wp-load.php' );

if ( ! is_user_logged_in() ) {
    auth_redirect();
}

// Initiate Pedestal
$pedestal = \Pedestal\Pedestal();
