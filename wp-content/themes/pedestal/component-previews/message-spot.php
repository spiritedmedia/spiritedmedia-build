<?php

use Timber\Timber;
use Pedestal\Message_Spot;

add_filter( 'show_admin_bar', '__return_false' );

$context = Timber::get_context();

$message_spot_data = get_option( 'pedestal_message_spot' ) ?: [];
$messages = array_filter( $message_spot_data, function( $value ) {
    $id = get_query_var( 'component-id' );
    return ( $value['id'] === $id );
} );
$message = reset( $messages );

if ( $message ) {
    $data = $message;
} else {
    $data = Message_Spot::get_model_defaults();
}
$context['message_spot'] = Message_Spot::prepare_timber_context( $data );

Timber::render( 'component-previews/message-spot.twig', $context );
