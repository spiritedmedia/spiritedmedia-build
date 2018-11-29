<?php

include 'include.php';

use Timber\Timber;
use Pedestal\Conversion_Prompts;
use Pedestal\Email\Newsletter_Emails;

// Disable conversion prompt targeting so we can view
// the conversion prompts here on the styleuide
add_filter( 'body_class', function( $classes ) {
    $classes[] = 'is-target-audience--disabled';
    return $classes;
} );

$basic_prompt_args = [
    'title'       => 'This is a basic Conversion Prompt',
    'body'        => 'Here is a basic description to better help entice readers to take action and do something good.',
    'icon_name'   => 'sun',
    'button_text' => 'Button Text',
    'button_url'  => 'https://example.com',
];

$signup_form_args = [];

$prompt_with_form_args = [
    'title'       => 'This is a Conversion Prompt with a Newsletter Signup Form',
    'body'        => 'This signup form is just like any other but it includes a signup form',
    'icon_name'   => 'envelope-slant',
    'signup_form' => Newsletter_Emails::get_signup_form( $signup_form_args ),
];

$context = [
    'basic_prompt'            => Conversion_Prompts::get_prompt( $basic_prompt_args ),
    'prompt_with_signup_form' => Conversion_Prompts::get_prompt( $prompt_with_form_args ),
] + Timber::get_context();
Timber::render( 'views/conversion-prompts.twig', $context );
