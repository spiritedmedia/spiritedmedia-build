<?php

use Timber\Timber;
use Pedestal\Audience\Message_Spot;

add_filter( 'show_admin_bar', '__return_false' );

// The full context is necessary to set up the page
$context = Timber::get_context();

$id = get_query_var( 'component-id' );
$data = Message_Spot::get_message_data_by_id( $id );
$context['message_spot'] = Message_Spot::render( $data );
Timber::render( 'component-previews/message-spot.twig', $context );
