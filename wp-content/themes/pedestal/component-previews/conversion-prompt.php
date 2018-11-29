<?php

use Timber\Timber;
use Pedestal\Conversion_Prompts;
use Pedestal\Conversion_Prompt_Admin;

add_filter( 'show_admin_bar', '__return_false' );

// The full context is necessary to set up the page
$context = Timber::get_context();

$message = Conversion_Prompt_Admin::get_prompt_data_by_id( get_query_var( 'component-id' ) );
$data = $message ?: Conversion_Prompt_Admin::get_model_defaults();
$context['conversion_prompt'] = Conversion_Prompts::get_prompt( $data );
Timber::render( 'component-previews/conversion-prompt.twig', $context );
