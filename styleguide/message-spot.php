<?php

include 'include.php';

use Timber\Timber;
use Pedestal\Message_Spot;

$context = Timber::get_context();

// Disable the actual message spot because we don't want it interfering
$context['message_spot'] = [];

$message_data = [
    'standard' => [
        'type'         => 'standard',
        'url'          => '#',
        'icon'         => 'link',
        'body'         => '<p>A message to readers that <em>presumes</em> a subsequent action on their part.</p>',
    ],
    'with_title' => [
        'type'         => 'with_title',
        'url'          => '#',
        'icon'         => 'link',
        'title'        => 'The message title',
        'body'         => '<p>A message to readers that <em>presumes</em> a subsequent action on their part.</p>',
    ],
    'with_button' => [
        'type'         => 'with_button',
        'url'          => '#',
        'icon'         => 'link',
        'body'         => '<p>A message that supports the action that follows it.</p>',
        'button_label' => 'Take Action',
    ],
];
$context['messages'] = array_map( function( $item ) {
    return Message_Spot::prepare_timber_context( $item );
}, $message_data );

Timber::render( 'views/message-spot.twig', $context );
