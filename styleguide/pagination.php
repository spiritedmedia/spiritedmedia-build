<?php
include 'include.php';
use Timber\Timber;
use Pedestal\Objects\Stream;

$context = Timber::get_context();

$fake_query = new \WP_Query();
$fake_query->max_num_pages = 999;
$stream = new Stream( $fake_query );

$the_paginations = [];

// No navigation, just the text version
$the_paginations[] = [
    'content' => $stream->get_pagination(
        [
            'show_text' => true,
            'show_nav' => false,
        ],
        [
            'current_page' => 1,
        ]
    ),
    'usage' => [
        'Author profile streams',
        'Single cluster streams',
    ],
];

// Load More Stories button
$the_paginations[] = [
    'content' => $stream->get_load_more_button(),
    'usage' => [
        'Every stream',
    ],
];
$the_paginations[] = [
    'content' => $stream->get_load_more_button( [
        'text' => 'Load more other stuff',
    ] ),
    'description' => 'This button-style pagination can have customizable text.',
];

$context['the_paginations'] = $the_paginations;
Timber::render( 'views/pagination.twig', $context );
