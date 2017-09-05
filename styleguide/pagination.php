<?php
include 'include.php';
use Timber\Timber;
use Pedestal\Objects\Stream;

$context = Timber::get_context();

$fake_query = new \WP_Query();
$fake_query->max_num_pages = 999;
$stream = new Stream( $fake_query );

$the_paginations = [];
$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => true,
        'show_nav' => true,
    ],
    [
        'current_page' => 1,
    ]
);

$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => true,
        'show_nav' => true,
    ],
    [
        'current_page' => 5,
    ]
);

$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => true,
        'show_nav' => true,
    ],
    [
        'current_page' => 100,
    ]
);

$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => true,
        'show_nav' => true,
    ],
    [
        'current_page' => 995,
    ]
);

$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => true,
        'show_nav' => true,
    ],
    [
        'current_page' => 998,
    ]
);

$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => true,
        'show_nav' => true,
    ],
    [
        'current_page' => 999,
    ]
);

// No navigation, just the text version
$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => true,
        'show_nav' => false,
    ],
    [
        'current_page' => 1,
    ]
);

// No text, just the navigation
$the_paginations[] = $stream->get_pagination(
    [
        'show_text' => false,
        'show_nav' => true,
    ],
    [
        'current_page' => 1,
    ]
);

$context['pagination'] = implode( '<p>&nbsp;</p>', $the_paginations);
Timber::render( 'views/pagination.twig', $context );
