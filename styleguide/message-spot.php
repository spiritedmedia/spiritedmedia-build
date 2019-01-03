<?php

include 'include.php';

use Timber\Timber;
use Pedestal\Audience\Message_Spot;

$context = Timber::get_context();

// Disable the actual message spot because we don't want it interfering
$context['message_spot'] = '';

$messages_data = [
    'standard' => [
        'type' => 'standard',
        'url'  => '#',
        'icon' => 'link',
        'body' => '<p>A message to readers that <em>presumes</em> a subsequent action on their part.</p>',
    ],
    'with_title' => [
        'type'  => 'with_title',
        'url'   => '#',
        'icon'  => 'link',
        'title' => 'The message title',
        'body'  => '<p>A message to readers that <em>presumes</em> a subsequent action on their part.</p>',
    ],
    'with_button' => [
        'type'         => 'with_button',
        'url'          => '#',
        'icon'         => 'link',
        'body'         => '<p>A message that supports the action that follows it.</p>',
        'button_label' => 'Take Action',
    ],
    'override' => [
        'type'               => 'override',
        'url'                => '#',
        'icon'               => 'bolt-solid',
        'title'              => 'Breaking News',
        'body'               => '<p>A handcrafted headline for maximum newsiness</p>',
        'additional_classes' => 'message-spot--override message-spot--with-title js-message-spot-override',
    ],
];

$context['messages'] = [];
foreach ( $messages_data as $message_type => $message_data ) {
    $context['messages'][ $message_type ] = Message_Spot::render( $message_data );
}

Timber::render( 'views/message-spot.twig', $context );
