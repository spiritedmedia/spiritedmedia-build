<?php

use Timber\Timber;
use Pedestal\Message_Spot;

add_filter( 'show_admin_bar', '__return_false' );

// The full context is necessary to set up the page
$context = Timber::get_context();

$message = [];
$component_id = get_query_var( 'component-id' );
$message_spot_data = get_option( 'pedestal_message_spot' ) ?: [];

$messages = $message_spot_data['messages'] ?? null;
$override_enabled = $message_spot_data['override']['enabled'] ?? null;
if ( 'true' === $override_enabled ) {
    $message = $message_spot_data['override'];
} elseif ( $messages ) {
    $messages = array_filter( $messages, function( $value ) use ( $component_id ) {
        return ( $value['id'] === $component_id );
    } );
    $message = reset( $messages );
}

if ( $message ) {
    $data = $message;
} else {
    $defaults = Message_Spot::get_model_defaults();
    $data = $defaults['standard'];
    if ( 'override' === $component_id ) {
        $data = $defaults['override'];
    }
}

$context['message_spot'] = Message_Spot::prepare_timber_context( $data );

Timber::render( 'component-previews/message-spot.twig', $context );
