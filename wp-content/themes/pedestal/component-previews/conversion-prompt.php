<?php

use Timber\Timber;
use Pedestal\Audience\Conversion_Prompts;

add_filter( 'show_admin_bar', '__return_false' );

// The full context is necessary to set up the page
$context = Timber::get_context();

$id = get_query_var( 'component-id' );
$data = Conversion_Prompts::get_message_data_by_id( $id );
$context['conversion_prompt'] = Conversion_Prompts::render( $data );
Timber::render( 'component-previews/conversion-prompt.twig', $context );
